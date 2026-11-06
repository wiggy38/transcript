<?php

namespace App\Services\Traduction;

use App\Contracts\LanguageModel;
use App\Enums\StatutSortieIa;
use App\Exceptions\ErreurModele;
use App\Exceptions\ErreurTraduction;
use App\Models\AiOutput;
use App\Services\Quota\QuotaService;
use App\Support\ConfigurationRecherche;
use Illuminate\Support\Facades\DB;

/**
 * Traduit une transcription dans une langue (V2). Une sortie par langue ; les segments sont ceux figés au lancement.
 *
 * - Les segments partent par morceaux, avec quelques segments de contexte avant et après.
 * - Chaque morceau doit renvoyer exactement les identifiants envoyés (ValidateurTraduction) ; sinon il est
 *   relancé une fois, puis l'opération échoue proprement.
 * - Les horodatages viennent du segment figé, jamais de la réponse du modèle.
 * - Une traduction trop longue pour sa durée est redemandée une fois, avec consigne de raccourcir. Si elle
 *   reste trop longue, elle est gardée telle quelle et signalée (trop_long) : rien n'est coupé en silence.
 * - Le crédit n'est décompté qu'à la réussite, dans la même transaction que l'enregistrement.
 */
final class ServiceTraduction
{
    public function __construct(
        private readonly LanguageModel $modele,
        private readonly ConstructeurRequeteTraduction $requetes,
        private readonly ValidateurTraduction $validateur,
        private readonly DecoupeSousTitres $decoupe,
        private readonly QuotaService $quotas,
    ) {}

    public function executer(AiOutput $sortie): void
    {
        $transcription = $sortie->transcription;
        $sortie->update([
            'statut' => StatutSortieIa::EnCours,
            'date_debut' => now(),
            'date_echec' => null,
        ]);

        $cout = 0.0;
        $modele = '';

        try {
            $segments = $this->traduire($sortie, $cout, $modele);
        } catch (ErreurModele $erreur) {
            $this->echouer($sortie, $cout + $erreur->coutUsd);

            return;
        } catch (ErreurTraduction) {
            $this->echouer($sortie, $cout);

            return;
        }

        $langue = $sortie->format;

        DB::transaction(function () use ($sortie, $transcription, $segments, $langue, $modele, $cout) {
            $sortie->update([
                'statut' => StatutSortieIa::Termine,
                'contenu' => [
                    'langue' => $langue,
                    'segments' => $segments,
                    'avertissements' => count(array_filter($segments, fn (array $s) => $s['trop_long'])),
                ],
                'texte_recherche' => implode("\n", array_column($segments, 'texte')),
                'config_recherche' => ConfigurationRecherche::pourLangue($langue),
                'fournisseur' => 'anthropic',
                'modele' => $modele,
                'cout_estime_usd' => $cout,
                'date_termine' => now(),
            ]);

            $this->quotas->consommerCredits($transcription->user, $sortie->credits, 'traduction', $transcription, $sortie);
        });
    }

    /**
     * @return list<array{id: string, debut_ms: int, fin_ms: int, texte_original: string, texte: string, trop_long: bool}>
     *
     * @throws ErreurModele
     * @throws ErreurTraduction
     */
    private function traduire(AiOutput $sortie, float &$cout, string &$modele): array
    {
        $segments = $sortie->contenu['segments'] ?? [];
        $source = (string) ($sortie->parametres['source'] ?? '');
        $cible = (string) $sortie->format;
        $morceau = max(1, (int) config('voxia.ia.traduction.segments_par_morceau'));
        $contexte = max(0, (int) config('voxia.ia.traduction.contexte_segments'));
        $traduits = [];

        for ($debut = 0, $total = count($segments); $debut < $total; $debut += $morceau) {
            $aTraduire = array_slice($segments, $debut, $morceau);
            $avant = array_map(
                fn (array $s) => $s + ['texte' => $traduits[$s['id']]],
                array_slice($segments, max(0, $debut - $contexte), min($contexte, $debut)),
            );
            $apres = array_slice($segments, $debut + $morceau, $contexte);

            $resultat = $this->morceau($sortie, $source, $cible, $aTraduire, $avant, $apres, [], $cout, $modele);

            // Un segment trop long est redemandé une fois ; la nouvelle version ne remplace que les segments qui tiennent.
            $trop = $this->identifiantsTropLongs($aTraduire, $resultat);

            if ($trop !== []) {
                try {
                    $nouveau = $this->morceau($sortie, $source, $cible, $aTraduire, $avant, $apres, $trop, $cout, $modele);

                    foreach ($trop as $id) {
                        $segment = $this->segment($aTraduire, $id);

                        if (! $this->decoupe->tropLong($nouveau[$id], $segment['debut_ms'], $segment['fin_ms'])) {
                            $resultat[$id] = $nouveau[$id];
                        }
                    }
                } catch (ErreurTraduction) {
                    // La version courte n'a pas abouti : la traduction d'origine reste, signalée plus bas.
                }
            }

            foreach ($aTraduire as $segment) {
                $traduits[$segment['id']] = $resultat[$segment['id']];
            }
        }

        $this->validateur->complet($segments, $traduits);

        return array_map(fn (array $segment) => [
            'id' => $segment['id'],
            'debut_ms' => $segment['debut_ms'],
            'fin_ms' => $segment['fin_ms'],
            'texte_original' => $segment['texte_original'],
            'texte' => $traduits[$segment['id']],
            'trop_long' => $this->decoupe->tropLong($traduits[$segment['id']], $segment['debut_ms'], $segment['fin_ms']),
        ], $segments);
    }

    /**
     * Un morceau : appel, contrôle d'alignement, une relance si besoin.
     *
     * @param  list<array{id: string, debut_ms: int, fin_ms: int, texte_original: string}>  $aTraduire
     * @param  list<array<string, mixed>>  $avant
     * @param  list<array<string, mixed>>  $apres
     * @param  list<string>  $raccourcir
     * @return array<string, string>
     *
     * @throws ErreurModele
     * @throws ErreurTraduction
     */
    private function morceau(AiOutput $sortie, string $source, string $cible, array $aTraduire, array $avant, array $apres, array $raccourcir, float &$cout, string &$modele): array
    {
        $identifiants = array_column($aTraduire, 'id');
        $relances = max(0, (int) config('voxia.ia.traduction.relances'));
        $relance = false;
        $derniere = null;

        for ($essai = 0; $essai <= $relances; $essai++) {
            $requete = $this->requetes->pour(
                $source,
                $cible,
                $aTraduire,
                $avant,
                $apres,
                $raccourcir,
                $relance,
                $sortie->transcription->user_id,
                $sortie->getKey(),
            );
            $reponse = $this->modele->genererStructure($requete, SchemaTraduction::pour($identifiants));
            $cout += $reponse->coutUsd;
            $modele = $reponse->modele;

            try {
                return $this->validateur->morceau($reponse->structure, $identifiants);
            } catch (ErreurTraduction $erreur) {
                $derniere = $erreur;
                $relance = true;
            }
        }

        throw $derniere;
    }

    /**
     * @param  list<array{id: string, debut_ms: int, fin_ms: int, texte_original: string}>  $aTraduire
     * @param  array<string, string>  $resultat
     * @return list<string>
     */
    private function identifiantsTropLongs(array $aTraduire, array $resultat): array
    {
        $trop = [];

        foreach ($aTraduire as $segment) {
            if ($this->decoupe->tropLong($resultat[$segment['id']], $segment['debut_ms'], $segment['fin_ms'])) {
                $trop[] = $segment['id'];
            }
        }

        return $trop;
    }

    /** @param  list<array{id: string, debut_ms: int, fin_ms: int, texte_original: string}>  $segments */
    private function segment(array $segments, string $id): array
    {
        foreach ($segments as $segment) {
            if ($segment['id'] === $id) {
                return $segment;
            }
        }

        throw new \LogicException("Segment {$id} absent du morceau.");
    }

    private function echouer(AiOutput $sortie, float $cout): void
    {
        $sortie->update([
            'statut' => StatutSortieIa::Echec,
            'date_echec' => now(),
            'cout_estime_usd' => $cout,
        ]);
    }
}

<?php

namespace App\Services\Resume;

use App\Contracts\LanguageModel;
use App\Data\Ia\RequeteModele;
use App\Data\Resume\ContenuResume;
use App\Data\Resume\ExigencesResume;
use App\Data\Resume\ParagrapheResume;
use App\Enums\StatutSortieIa;
use App\Exceptions\ErreurModele;
use App\Exceptions\ErreurResume;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Services\Quota\QuotaService;
use App\Support\ConfigurationRecherche;
use Illuminate\Support\Facades\DB;

/**
 * Génère le résumé d'une transcription (V2). Une vidéo courte part en un appel ; une vidéo longue est
 * découpée en morceaux, chacun résumé, puis une synthèse fusionne les résultats.
 *
 * Chaque sortie est validée (ValidateurResume) ; une sortie non conforme est relancée
 * voxia.ia.resume.relances fois. Un échec, technique ou de validation, ne décompte aucun crédit : le
 * décompte n'a lieu qu'à la réussite, dans la même transaction que l'enregistrement du contenu.
 */
final class ServiceResume
{
    public function __construct(
        private readonly LanguageModel $modele,
        private readonly DecoupeurTranscription $decoupeur,
        private readonly ConstructeurRequeteResume $requetes,
        private readonly ValidateurResume $validateur,
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

        try {
            [$contenu, $modele] = $this->generer($sortie, $transcription, $cout);
        } catch (ErreurModele $erreur) {
            $this->echouer($sortie, $cout + $erreur->coutUsd);

            return;
        } catch (ErreurResume) {
            $this->echouer($sortie, $cout);

            return;
        }

        DB::transaction(function () use ($sortie, $transcription, $contenu, $modele, $cout) {
            $sortie->update([
                'statut' => StatutSortieIa::Termine,
                'contenu' => $contenu->enTableau(),
                // Recherche : configuration de la langue du résumé (pas de la transcription), qui est celle des mots du texte.
                'texte_recherche' => $contenu->texteRecherche(),
                'config_recherche' => ConfigurationRecherche::pourLangue($sortie->langue),
                'fournisseur' => 'anthropic',
                'modele' => $modele,
                'cout_estime_usd' => $cout,
                'date_termine' => now(),
            ]);

            $this->quotas->consommerCredits($transcription->user, $sortie->credits, 'resume', $transcription, $sortie);
        });
    }

    /**
     * @return array{0: ContenuResume, 1: string}
     *
     * @throws ErreurModele
     * @throws ErreurResume
     */
    private function generer(AiOutput $sortie, Transcription $transcription, float &$cout): array
    {
        $paragraphes = $this->paragraphes($transcription);
        $texteComplet = implode("\n", array_map(fn (ParagrapheResume $p) => $p->texte, $paragraphes));
        $duree = (int) $transcription->duree_secondes;
        $langue = $sortie->langue;
        $utilisateur = $transcription->user_id;
        $sortieId = $sortie->getKey();
        $tailleMs = (int) config('voxia.ia.resume.taille_morceau_secondes') * 1000;
        $morceaux = $this->decoupeur->decouper($paragraphes, $tailleMs);

        if (count($morceaux) <= 1) {
            $requete = $this->requetes->complete($paragraphes, ExigencesResume::finales($duree), $langue, $utilisateur, $sortieId);

            return $this->appel($requete, ExigencesResume::finales($duree), $this->parNumero($paragraphes), $texteComplet, $duree, $cout);
        }

        $resultats = [];

        foreach ($morceaux as $index => $morceau) {
            $exigences = ExigencesResume::morceau($duree);
            $requete = $this->requetes->morceau($morceau, $index + 1, count($morceaux), $exigences, $langue, $utilisateur, $sortieId);
            [$contenu] = $this->appel($requete, $exigences, $this->parNumero($morceau), $texteComplet, $duree, $cout);

            $resultats[] = ['debut_ms' => $morceau[0]->debutMs, 'contenu' => $contenu->enTableau()];
        }

        $exigences = ExigencesResume::finales($duree);
        $requete = $this->requetes->synthese($resultats, $exigences, $langue, $utilisateur, $sortieId);

        return $this->appel($requete, $exigences, $this->parNumero($paragraphes), $texteComplet, $duree, $cout);
    }

    /**
     * Un appel, puis les relances prévues pour une sortie qui ne respecte pas les règles. Le coût de chaque
     * appel réussi (y compris une sortie rejetée, facturée) s'ajoute à $cout.
     *
     * @param  array<int, ParagrapheResume>  $parNumero
     * @return array{0: ContenuResume, 1: string}
     *
     * @throws ErreurModele
     * @throws ErreurResume
     */
    private function appel(RequeteModele $requete, ExigencesResume $exigences, array $parNumero, string $texteComplet, int $duree, float &$cout): array
    {
        $relances = max(0, (int) config('voxia.ia.resume.relances'));
        $derniere = null;

        for ($essai = 0; $essai <= $relances; $essai++) {
            $reponse = $this->modele->genererStructure($requete, SchemaResume::schema());
            $cout += $reponse->coutUsd;

            try {
                $contenu = $this->validateur->valider($reponse->structure ?? [], $parNumero, $exigences, $texteComplet, $duree);

                return [$contenu, $reponse->modele];
            } catch (ErreurResume $erreur) {
                $derniere = $erreur;
            }
        }

        throw $derniere;
    }

    private function echouer(AiOutput $sortie, float $cout): void
    {
        $sortie->update([
            'statut' => StatutSortieIa::Echec,
            'date_echec' => now(),
            'cout_estime_usd' => $cout,
        ]);
    }

    /** @return list<ParagrapheResume> */
    private function paragraphes(Transcription $transcription): array
    {
        return $transcription->paragraphs()->orderBy('position')->get()
            ->map(fn (TranscriptionParagraph $p) => new ParagrapheResume((int) $p->position, (int) $p->debut_ms, (string) $p->texte))
            ->values()
            ->all();
    }

    /**
     * @param  list<ParagrapheResume>  $paragraphes
     * @return array<int, ParagrapheResume>
     */
    private function parNumero(array $paragraphes): array
    {
        $indexes = [];

        foreach ($paragraphes as $paragraphe) {
            $indexes[$paragraphe->numero] = $paragraphe;
        }

        return $indexes;
    }
}

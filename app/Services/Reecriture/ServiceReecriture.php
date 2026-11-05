<?php

namespace App\Services\Reecriture;

use App\Contracts\LanguageModel;
use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Enums\StatutSortieIa;
use App\Enums\TonReecriture;
use App\Exceptions\ErreurModele;
use App\Exceptions\ErreurReecriture;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Services\Quota\QuotaService;
use Illuminate\Support\Facades\DB;

/**
 * Génère un format de réécriture pour une transcription (V2). Une sortie par format, un appel par sortie.
 *
 * Une sortie non conforme est relancée voxia.ia.reecriture.relances fois, avec la consigne de corriger.
 * Le post LinkedIn trop long fait un dernier essai en retirant des paragraphes entiers. Un échec,
 * technique ou de validation, ne décompte aucun crédit : le décompte n'a lieu qu'à la réussite, dans la
 * même transaction que l'enregistrement du contenu.
 */
final class ServiceReecriture
{
    public function __construct(
        private readonly LanguageModel $modele,
        private readonly ConstructeurRequeteReecriture $requetes,
        private readonly ValidateurReecriture $validateur,
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
        } catch (ErreurReecriture) {
            $this->echouer($sortie, $cout);

            return;
        }

        DB::transaction(function () use ($sortie, $transcription, $contenu, $modele, $cout) {
            $sortie->update([
                'statut' => StatutSortieIa::Termine,
                'contenu' => $contenu,
                'fournisseur' => 'anthropic',
                'modele' => $modele,
                'cout_estime_usd' => $cout,
                'date_termine' => now(),
            ]);

            $this->quotas->consommerCredits($transcription->user, $sortie->credits, 'reecriture', $transcription, $sortie);
        });
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     *
     * @throws ErreurModele
     * @throws ErreurReecriture
     */
    private function generer(AiOutput $sortie, Transcription $transcription, float &$cout): array
    {
        $format = FormatReecriture::from($sortie->format);
        $ton = TonReecriture::from($sortie->parametres['ton'] ?? config('voxia.ia.reecriture.ton_defaut'));
        $longueur = LongueurReecriture::from($sortie->parametres['longueur'] ?? config('voxia.ia.reecriture.longueur_defaut'));
        $texteComplet = $this->texteComplet($transcription);
        $relances = max(0, (int) config('voxia.ia.reecriture.relances'));
        $relance = false;
        $derniere = null;
        $derniereStructure = [];
        $modele = '';

        for ($essai = 0; $essai <= $relances; $essai++) {
            $requete = $this->requetes->pour($format, $ton, $longueur, $sortie->langue, $texteComplet, $transcription->user_id, $sortie->getKey(), $relance);
            $reponse = $this->modele->genererStructure($requete, SchemaReecriture::pour($format));
            $cout += $reponse->coutUsd;
            $derniereStructure = $reponse->structure ?? [];
            $modele = $reponse->modele;

            try {
                return [$this->validateur->valider($format, $derniereStructure, $texteComplet, $longueur), $modele];
            } catch (ErreurReecriture $erreur) {
                $derniere = $erreur;
                $relance = true;
            }
        }

        // Dernier recours, LinkedIn seulement : un post trop long est raccourci en retirant des paragraphes entiers.
        if ($format === FormatReecriture::LinkedIn && $derniere?->motif === 'trop_long') {
            return [$this->validateur->valider($format, $derniereStructure, $texteComplet, $longueur, tronquer: true), $modele];
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

    /** Toute la transcription, un paragraphe par ligne, dans l'ordre. */
    private function texteComplet(Transcription $transcription): string
    {
        return $transcription->paragraphs()->orderBy('position')->get()
            ->map(fn (TranscriptionParagraph $p) => (string) $p->texte)
            ->implode("\n\n");
    }
}

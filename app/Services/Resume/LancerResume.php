<?php

namespace App\Services\Resume;

use App\Enums\StatutSortieIa;
use App\Enums\StatutTranscription;
use App\Exceptions\ResumeRefuse;
use App\Jobs\GenererResume;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\QuotaService;

/**
 * Lance le résumé d'une transcription. Les règles sont vérifiées ici, côté serveur : propriétaire,
 * transcription terminée, durée connue, plan avec crédits IA, langue autorisée, solde suffisant.
 *
 * - Un résumé terminé se rouvre sans coût.
 * - Le régénérer coûte de nouveaux crédits : il faut la confirmation de l'utilisateur.
 * - Les crédits sont réservés (ai_outputs.credits) pendant la génération, décomptés à la réussite seulement.
 */
final class LancerResume
{
    public function __construct(
        private readonly QuotaService $quotas,
        private readonly PlanFeatures $droits,
    ) {}

    /**
     * Crédits demandés pour une vidéo : un par heure entamée, sur la base de voxia.ia.resume.credits_par_heure.
     */
    public function coutPour(Transcription $transcription): int
    {
        $secondes = max(1, (int) $transcription->duree_secondes);
        $heures = intdiv($secondes + PlanFeatures::SECONDES_PAR_HEURE - 1, PlanFeatures::SECONDES_PAR_HEURE);

        return $heures * (int) config('voxia.ia.resume.credits_par_heure');
    }

    /** Langue proposée par défaut : celle de la transcription si elle est autorisée, sinon le français. */
    public function langueParDefaut(Transcription $transcription): string
    {
        $langues = (array) config('voxia.langues_forcables');
        $candidate = $transcription->langue_forcee ?? $transcription->langue_detectee;

        return in_array($candidate, $langues, true) ? (string) $candidate : (string) $langues[0];
    }

    /**
     * @throws ResumeRefuse
     */
    public function lancer(User $utilisateur, Transcription $transcription, ?string $langue = null, bool $regenerer = false, bool $confirme = false): AiOutput
    {
        if ($transcription->user_id !== $utilisateur->getKey() || $transcription->statut !== StatutTranscription::Termine) {
            throw new ResumeRefuse(ResumeRefuse::NON_TERMINEE);
        }

        if ($transcription->duree_secondes === null) {
            throw new ResumeRefuse(ResumeRefuse::DUREE_INCONNUE);
        }

        $plan = $this->droits->planDe($utilisateur);

        if ($this->droits->creditsIaMensuels($plan) <= 0) {
            throw new ResumeRefuse(ResumeRefuse::PLAN_SANS_ACCES);
        }

        $langue ??= $this->langueParDefaut($transcription);

        if (! in_array($langue, (array) config('voxia.langues_forcables'), true)) {
            throw new ResumeRefuse(ResumeRefuse::LANGUE_INVALIDE);
        }

        $existante = AiOutput::query()
            ->where('transcription_id', $transcription->getKey())
            ->where('fonctionnalite', 'resume')
            ->first();

        if ($existante !== null && $existante->statut->enCours()) {
            return $existante;
        }

        if ($existante !== null && $existante->statut === StatutSortieIa::Termine) {
            if (! $regenerer) {
                return $existante; // Réouverture : gratuite.
            }

            if (! $confirme) {
                throw new ResumeRefuse(ResumeRefuse::CONFIRMATION_REQUISE);
            }
        }

        $cout = $this->coutPour($transcription);

        if (! $this->quotas->creditsIaSuffisants($utilisateur, $cout)) {
            throw new ResumeRefuse(ResumeRefuse::CREDIT_INSUFFISANT);
        }

        $sortie = AiOutput::updateOrCreate(
            ['transcription_id' => $transcription->getKey(), 'fonctionnalite' => 'resume'],
            [
                'statut' => StatutSortieIa::EnAttente,
                'langue' => $langue,
                'credits' => $cout,
                'date_debut' => null,
                'date_termine' => null,
                'date_echec' => null,
            ],
        );

        GenererResume::dispatch($sortie->getKey())->onQueue($this->droits->fileDe($plan));

        return $sortie;
    }
}

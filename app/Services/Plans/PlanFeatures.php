<?php

namespace App\Services\Plans;

use App\Enums\FormatExport;
use App\Enums\FormatExportReecriture;
use App\Enums\Plan;
use App\Models\User;

/**
 * Droits par plan, lus dans config/voxia.php : volumes, durée max par vidéo, exports, historique,
 * recherche, file de traitement, crédits IA. Tout écran et toute commande passent par ce service,
 * jamais par users.plan directement.
 *
 * Le plan d'un compte est lu par planDe(). Tant que la facturation (étape 4) n'est pas branchée,
 * voxia.droits.plan_reel reste à false et tout le monde est au plan Gratuit ; à true, c'est le plan
 * enregistré sur le compte qui compte (tests, démo, comptes réglés à la main).
 */
class PlanFeatures
{
    public const SECONDES_PAR_HEURE = 3600;

    /** Arrondi de facturation : la durée est comptée à la minute supérieure (offre). */
    public const SECONDES_PAR_MINUTE = 60;

    public function planDe(User $utilisateur): Plan
    {
        if (! (bool) config('voxia.droits.plan_reel')) {
            return Plan::Free;
        }

        return $utilisateur->plan instanceof Plan ? $utilisateur->plan : Plan::Free;
    }

    /** Vrai si le plan peut être vendu dans le MVP (Business reste défini mais non vendable). */
    public function estVendable(Plan $plan): bool
    {
        return (bool) config("voxia.plans.{$plan->value}.sellable", false);
    }

    /** @return list<Plan> */
    public function plansVendables(): array
    {
        return array_values(array_filter(Plan::cases(), fn (Plan $plan) => $this->estVendable($plan)));
    }

    /** Vidéos incluses par mois (Gratuit), ou null si le plan se compte en durée. */
    public function videosMensuelles(Plan $plan): ?int
    {
        $volume = config("voxia.plans.{$plan->value}.videos_per_month");

        return $volume === null ? null : (int) $volume;
    }

    /** Secondes de transcription incluses par mois (Pro, Business), ou null si le plan n'en a pas. */
    public function secondesMensuelles(Plan $plan): ?int
    {
        $heures = config("voxia.plans.{$plan->value}.hours_per_month");

        return $heures === null ? null : (int) $heures * self::SECONDES_PAR_HEURE;
    }

    /** Secondes achetées avec un Pack. */
    public function secondesPack(): int
    {
        return (int) config('voxia.plans.pack.hours') * self::SECONDES_PAR_HEURE;
    }

    /** Mois de validité d'un Pack, à partir de l'achat. */
    public function validitePackMois(): int
    {
        return (int) config('voxia.plans.pack.validity_months');
    }

    /** Durée max d'une vidéo, en secondes. */
    public function dureeMaxSecondes(Plan $plan): int
    {
        return (int) config("voxia.plans.{$plan->value}.max_video_seconds");
    }

    /** Crédits IA inclus par mois : 0 si le plan n'y a pas accès. */
    public function creditsIaMensuels(Plan $plan): int
    {
        return (int) config("voxia.plans.{$plan->value}.ai_credits_per_month", 0);
    }

    /** Coût d'une analyse de script, en crédits. */
    public function creditsParAnalyse(): int
    {
        return (int) config('voxia.script_analysis.credits_per_analysis');
    }

    /** File Horizon de la tâche : « high » pour le Pro, « default » pour les autres. */
    public function fileDe(Plan $plan): string
    {
        $prioritaires = (array) config('voxia.traitement.plans_prioritaires');

        return in_array($plan->value, $prioritaires, true)
            ? (string) config('voxia.traitement.files.prioritaire')
            : (string) config('voxia.traitement.files.standard');
    }

    /** Recherche plein texte dans l'historique (Pro et Pack, cahier F5). */
    public function peutRechercher(User $utilisateur): bool
    {
        return (bool) config("voxia.plans.{$this->cle($utilisateur)}.recherche", false);
    }

    /** Jours de conservation des transcriptions, ou null si l'historique est illimité. */
    public function joursConservation(User $utilisateur): ?int
    {
        $jours = config("voxia.plans.{$this->cle($utilisateur)}.history_days");

        return $jours === null ? null : (int) $jours;
    }

    /** Jours avant la suppression où l'email de rappel part (plan Gratuit : 3). */
    public function joursAvantRappel(User $utilisateur): int
    {
        return (int) config("voxia.plans.{$this->cle($utilisateur)}.history_reminder_days_before", 0);
    }

    /**
     * Formats d'export du plan (voxia.plans.*.exports), dans l'ordre de FormatExport.
     *
     * @return list<FormatExport>
     */
    public function formatsExport(User $utilisateur): array
    {
        $autorises = config("voxia.plans.{$this->cle($utilisateur)}.exports", []);

        return array_values(array_filter(
            FormatExport::cases(),
            fn (FormatExport $format) => in_array($format->value, $autorises, true),
        ));
    }

    public function peutExporter(User $utilisateur, FormatExport $format): bool
    {
        return in_array($format, $this->formatsExport($utilisateur), true);
    }

    /**
     * Exports d'une réécriture (voxia.plans.*.exports_reecriture) : TXT en Pro, Markdown et DOCX en Business.
     *
     * @return list<FormatExportReecriture>
     */
    public function formatsExportReecriture(User $utilisateur): array
    {
        $autorises = config("voxia.plans.{$this->cle($utilisateur)}.exports_reecriture", []);

        return array_values(array_filter(
            FormatExportReecriture::cases(),
            fn (FormatExportReecriture $format) => in_array($format->value, $autorises, true),
        ));
    }

    public function peutExporterReecriture(User $utilisateur, FormatExportReecriture $format): bool
    {
        return in_array($format, $this->formatsExportReecriture($utilisateur), true);
    }

    /**
     * Plan vendable le moins cher qui donne ce format : c'est le plan à nommer quand un format est verrouillé.
     */
    public function planMinimalPour(FormatExport $format): ?Plan
    {
        foreach (Plan::cases() as $plan) {
            $formats = config("voxia.plans.{$plan->value}.exports", []);

            if ($this->estVendable($plan) && in_array($format->value, $formats, true)) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Plans vendables qui donnent ce format : ce sont les plans à proposer quand un format est verrouillé.
     *
     * @return list<Plan>
     */
    public function plansAvec(FormatExport $format): array
    {
        return array_values(array_filter(
            $this->plansVendables(),
            fn (Plan $plan) => in_array($format->value, (array) config("voxia.plans.{$plan->value}.exports", []), true),
        ));
    }

    /**
     * Minutes d'une vidéo que l'aperçu gratuit ne transcrit pas (au-delà de la durée max du Gratuit),
     * arrondies à la minute supérieure.
     */
    public function minutesNonTranscrites(int $dureeSecondes): int
    {
        return (int) ceil(max(0, $dureeSecondes - $this->dureeMaxSecondes(Plan::Free)) / self::SECONDES_PAR_MINUTE);
    }

    public function libellePlan(Plan $plan): string
    {
        return (string) config("voxia.plans.{$plan->value}.label", $plan->name);
    }

    /** Vrai pour le plan Gratuit : quota en nombre de vidéos, aperçu au-delà de la durée max. */
    public function estGratuit(Plan $plan): bool
    {
        return $plan === Plan::Free;
    }

    /** Vrai pour les formules à abonnement (Pro et Business) : quota mensuel en heures. */
    public function estAbonnement(Plan $plan): bool
    {
        return in_array($plan, [Plan::Pro, Plan::Business], true);
    }

    /** Plans à abonnement, pour les contrôles qui recalculent le plan (voxia:recalculer-plans). */
    public function plansAbonnement(): array
    {
        return [Plan::Pro, Plan::Business];
    }

    /** Nombre de liens acceptés dans un lot (Business), ou 0 si le plan n'a pas de traitement par lots. */
    public function limiteLots(Plan $plan): int
    {
        return (int) config("voxia.plans.{$plan->value}.batch_links", 0);
    }

    private function cle(User $utilisateur): string
    {
        return $this->planDe($utilisateur)->value;
    }
}

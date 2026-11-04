<?php

namespace App\Services\Plans;

use App\Enums\FormatExport;
use App\Enums\Plan;
use App\Models\User;

/**
 * Droits par plan : recherche, durée de conservation de l'historique, rappel avant suppression.
 *
 * Tant que la facturation (étape 4) n'est pas branchée, tout le monde est au plan Gratuit.
 * Le drapeau voxia.droits.plan_reel (false par défaut) rend le plan enregistré sur le compte.
 * Tout écran et toute commande passent par ce service, jamais par users.plan directement.
 */
class PlanFeatures
{
    public function planDe(User $utilisateur): Plan
    {
        if (! (bool) config('voxia.droits.plan_reel')) {
            return Plan::Free;
        }

        return $utilisateur->plan instanceof Plan ? $utilisateur->plan : Plan::Free;
    }

    /** La recherche plein texte dans l'historique (Pro et Pack, cahier F5). */
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
     * Plan vendable le moins cher qui donne ce format : c'est le plan à nommer quand un format est verrouillé.
     */
    public function planMinimalPour(FormatExport $format): ?Plan
    {
        foreach (Plan::cases() as $plan) {
            $vendable = (bool) config("voxia.plans.{$plan->value}.sellable", false);
            $formats = config("voxia.plans.{$plan->value}.exports", []);

            if ($vendable && in_array($format->value, $formats, true)) {
                return $plan;
            }
        }

        return null;
    }

    public function libellePlan(Plan $plan): string
    {
        return (string) config("voxia.plans.{$plan->value}.label", $plan->name);
    }

    private function cle(User $utilisateur): string
    {
        return $this->planDe($utilisateur)->value;
    }
}

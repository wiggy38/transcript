<?php

namespace App\Services\Traitement;

use App\Enums\Plan;

/** Choisit la file Horizon d'une tâche selon le plan (voxia.traitement.plans_prioritaires). */
final class FileTraitement
{
    public static function pour(Plan $plan): string
    {
        $prioritaires = (array) config('voxia.traitement.plans_prioritaires');

        return in_array($plan->value, $prioritaires, true)
            ? (string) config('voxia.traitement.files.prioritaire')
            : (string) config('voxia.traitement.files.standard');
    }
}

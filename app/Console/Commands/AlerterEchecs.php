<?php

namespace App\Console\Commands;

use App\Services\Administration\AlerteEchecsTelechargement;
use Illuminate\Console\Command;

/** Vérifie le taux d'échec du téléchargement et alerte les administrateurs. Planifiée toutes les cinq minutes. */
class AlerterEchecs extends Command
{
    protected $signature = 'voxia:alerter-echecs';

    protected $description = 'Alerte les administrateurs quand le taux d\'échec du téléchargement dépasse le seuil';

    public function handle(AlerteEchecsTelechargement $alerte): int
    {
        $this->info($alerte->verifier()
            ? __('administration.commandes.alerte_envoyee')
            : __('administration.commandes.alerte_non_envoyee'));

        return self::SUCCESS;
    }
}

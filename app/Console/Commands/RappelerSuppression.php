<?php

namespace App\Console\Commands;

use App\Services\Conservation\Conservation;
use Illuminate\Console\Command;

/** Envoie le rappel avant suppression des transcriptions du plan Gratuit. Planifiée chaque matin. */
class RappelerSuppression extends Command
{
    protected $signature = 'voxia:rappeler-suppression';

    protected $description = 'Envoie le rappel avant suppression des transcriptions expirant bientôt';

    public function handle(Conservation $conservation): int
    {
        $signalees = $conservation->rappelerSuppressions();

        $this->info(__('historique.commandes.rappel', ['nombre' => $signalees]));

        return self::SUCCESS;
    }
}

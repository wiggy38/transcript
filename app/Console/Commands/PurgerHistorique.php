<?php

namespace App\Console\Commands;

use App\Services\Conservation\Conservation;
use Illuminate\Console\Command;

/** Supprime les transcriptions expirées des plans à durée limitée (Gratuit, 30 jours). Planifiée chaque nuit. */
class PurgerHistorique extends Command
{
    protected $signature = 'voxia:purger-historique';

    protected $description = 'Supprime les transcriptions dont la durée de conservation est dépassée';

    public function handle(Conservation $conservation): int
    {
        $supprimees = $conservation->purgerHistorique();

        $this->info(__('historique.commandes.purge', ['nombre' => $supprimees]));

        return self::SUCCESS;
    }
}

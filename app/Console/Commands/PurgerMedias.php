<?php

namespace App\Console\Commands;

use App\Services\Conservation\Conservation;
use Illuminate\Console\Command;

/** Retire les MP3 et les fichiers importés au-delà de la durée de conservation (tous les plans). Planifiée chaque nuit. */
class PurgerMedias extends Command
{
    protected $signature = 'voxia:purger-medias';

    protected $description = 'Retire les MP3 et les fichiers importés au-delà de la durée de conservation';

    public function handle(Conservation $conservation): int
    {
        $resultat = $conservation->purgerMedias();

        $this->info(__('historique.commandes.medias', $resultat));

        return self::SUCCESS;
    }
}

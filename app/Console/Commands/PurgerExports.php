<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Retire les exports ZIP des données (réglages, « Vos données ») plus vieux que
 * voxia.donnees.conservation_heures. Planifiée chaque nuit, avec les autres purges.
 */
class PurgerExports extends Command
{
    protected $signature = 'voxia:purger-exports';

    protected $description = 'Retire les exports ZIP des données au-delà de la durée de conservation';

    public function handle(): int
    {
        $disque = Storage::disk((string) config('voxia.stockage.disque'));
        $limite = now()->subHours((int) config('voxia.donnees.conservation_heures'))->getTimestamp();
        $supprimes = 0;

        foreach ($disque->allFiles('exports') as $fichier) {
            if ($disque->lastModified($fichier) < $limite) {
                $disque->delete($fichier);
                $supprimes++;
            }
        }

        $this->info("Exports supprimés : {$supprimes}.");

        return self::SUCCESS;
    }
}

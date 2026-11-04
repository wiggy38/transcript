<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('voxia:ytdlp-update')
    ->dailyAt(config('voxia.tools.ytdlp_update_at'))
    ->withoutOverlapping();

// Conservation (cahier F5) : purge de l'historique Gratuit, rappel avant suppression, MP3 et fichiers importés.
Schedule::command('voxia:purger-historique')
    ->dailyAt(config('voxia.conservation.heure_purge'))
    ->withoutOverlapping();

Schedule::command('voxia:purger-medias')
    ->dailyAt(config('voxia.conservation.heure_purge'))
    ->withoutOverlapping();

Schedule::command('voxia:rappeler-suppression')
    ->dailyAt(config('voxia.conservation.heure_rappel'))
    ->withoutOverlapping();

// Paiement (cahier §5) : les Packs expirent et les abonnements résiliés prennent fin sans événement ;
// ce recalcul ramène chaque compte au plan qui lui reste.
Schedule::command('voxia:recalculer-plans')
    ->dailyAt(config('voxia.paiement.heure_recalcul'))
    ->withoutOverlapping();

<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Process;

// Critère : yt-dlp est mis à jour chaque jour. Aucun appel réseau : Process::fake.

beforeEach(function () {
    Process::preventStrayProcesses();
    config(['voxia.tools.ytdlp_binary' => 'yt-dlp-test']);
});

it('met yt-dlp à jour et affiche la version obtenue', function () {
    Process::fake([
        '*--update*' => Process::result(output: 'Updated yt-dlp to 2026.10.01'),
        '*--version*' => Process::result(output: "2026.10.01\n"),
    ]);

    $this->artisan('voxia:ytdlp-update')
        ->expectsOutputToContain('2026.10.01')
        ->assertSuccessful();
});

it('signale l’échec de la mise à jour sans afficher la sortie brute', function () {
    Process::fake([
        '*--update*' => Process::result(errorOutput: 'ERROR: unable to write binary', exitCode: 1),
    ]);

    $this->artisan('voxia:ytdlp-update')
        ->expectsOutputToContain(__('videos.maj.echec'))
        ->doesntExpectOutputToContain('unable to write binary')
        ->assertFailed();
});

it('planifie la mise à jour chaque jour', function () {
    $evenement = collect(app(Schedule::class)->events())
        ->first(fn ($evenement) => str_contains((string) $evenement->command, 'voxia:ytdlp-update'));

    expect($evenement)->not->toBeNull()
        ->and($evenement->expression)->toBe('0 4 * * *');
});

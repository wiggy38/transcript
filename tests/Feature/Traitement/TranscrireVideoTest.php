<?php

use App\Data\MotTranscrit;
use App\Data\TranscriptionBrute;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use App\Models\User;
use App\Services\Video\FauxVideoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

// Les tests de commande utilisent la file synchrone (phpunit.xml) : la tâche s'exécute
// dans le même processus, sans Redis ni worker.

beforeEach(function () {
    User::factory()->create(['email' => 'lea@example.test']);
});

it('lance la transcription, affiche la progression et les paragraphes', function () {
    $moteur = preparerTraitement();
    $moteur->repondre(transcriptionBruteTest());

    $this->artisan('voxia:transcribe', [
        'source' => 'https://www.youtube.com/watch?v=abcdefghijk',
        '--user' => 'lea@example.test',
    ])
        ->expectsOutputToContain(__('traitement.statut', ['statut' => __('traitement.statuts.termine')]))
        ->expectsOutputToContain('[00:00] Bonjour monde.')
        ->expectsOutputToContain('[00:03] Suite')
        ->assertSuccessful();
});

it('n’affiche que les 10 premiers paragraphes', function () {
    $mots = [];

    for ($i = 1; $i <= 12; $i++) {
        // Une pause de 1,5 s entre chaque mot : chaque mot forme son propre paragraphe.
        $mots[] = new MotTranscrit('mot'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'.', $i * 2000, $i * 2000 + 500);
    }

    preparerTraitement()->repondre(new TranscriptionBrute('fr', $mots, 'texte', 0.01));

    $this->artisan('voxia:transcribe', [
        'source' => 'https://www.youtube.com/watch?v=abcdefghijk',
        '--user' => 'lea@example.test',
    ])
        ->expectsOutputToContain('[00:20] mot10.')
        ->doesntExpectOutputToContain('mot11.')
        ->assertSuccessful();
});

it('échoue avec le motif affiché quand la vidéo est privée', function () {
    preparerTraitement(new FauxVideoFetcher(
        erreurMetadonnees: new ErreurVideo(MotifEchec::VideoPrivee),
    ));

    $this->artisan('voxia:transcribe', [
        'source' => 'https://www.youtube.com/watch?v=abcdefghijk',
        '--user' => 'lea@example.test',
    ])
        ->expectsOutputToContain(__('videos.motifs.video_privee'))
        ->assertFailed();
});

it('demande le compte quand --user est absent', function () {
    preparerTraitement();

    $this->artisan('voxia:transcribe', ['source' => 'https://www.youtube.com/watch?v=abcdefghijk'])
        ->expectsOutputToContain(__('traitement.utilisateur_requis'))
        ->assertFailed();
});

it('accepte un chemin de fichier local', function () {
    $moteur = preparerTraitement();
    $moteur->repondre(transcriptionBruteTest());

    $chemin = storage_path('framework/testing/traitement/entretien.mp3');
    File::ensureDirectoryExists(dirname($chemin));
    File::put($chemin, 'audio');

    $this->artisan('voxia:transcribe', [
        'source' => $chemin,
        '--user' => 'lea@example.test',
    ])
        ->expectsOutputToContain('[00:00] Bonjour monde.')
        ->assertSuccessful();
});

it('refuse un fichier introuvable', function () {
    preparerTraitement();

    $this->artisan('voxia:transcribe', [
        'source' => storage_path('framework/testing/inexistant.mp3'),
        '--user' => 'lea@example.test',
    ])
        ->expectsOutputToContain(__('traitement.fichier_introuvable'))
        ->assertFailed();
});

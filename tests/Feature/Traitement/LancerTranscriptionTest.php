<?php

use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Exceptions\ErreurVideo;
use App\Jobs\ProcessTranscription;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Quota\AchatPack;
use App\Services\Traitement\LancerTranscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('met la tâche dans la file du plan : high pour Pro, default pour les autres', function (string $plan, string $file) {
    Queue::fake();
    preparerTraitement();

    $utilisateur = User::factory()->create(['plan' => Plan::from($plan)]);

    // Un Pack sans heures achetées ne peut rien lancer : on l'achète avant le lancement.
    if ($plan === 'pack') {
        app(AchatPack::class)->enregistrer($utilisateur);
    }

    app(LancerTranscription::class)->lancer($utilisateur, 'https://www.youtube.com/watch?v=abcdefghijk');

    Queue::assertPushedOn($file, ProcessTranscription::class);
})->with([
    'Pro' => ['pro', 'high'],
    'Gratuit' => ['free', 'default'],
    'Pack' => ['pack', 'default'],
]);

it('renvoie la transcription existante pour un lien déjà transcrit par le même utilisateur', function () {
    Queue::fake();
    preparerTraitement();

    $utilisateur = User::factory()->create();
    $lancement = app(LancerTranscription::class);

    $premiere = $lancement->lancer($utilisateur, 'https://www.youtube.com/watch?v=abcdefghijk');
    $seconde = $lancement->lancer($utilisateur, 'https://youtu.be/abcdefghijk');

    expect($seconde->getKey())->toBe($premiere->getKey())
        ->and(Transcription::count())->toBe(1);

    Queue::assertPushed(ProcessTranscription::class, 1);
});

it('crée une transcription distincte pour un autre utilisateur', function () {
    Queue::fake();
    preparerTraitement();

    $lancement = app(LancerTranscription::class);
    $lancement->lancer(User::factory()->create(), 'https://www.youtube.com/watch?v=abcdefghijk');
    $lancement->lancer(User::factory()->create(), 'https://www.youtube.com/watch?v=abcdefghijk');

    expect(Transcription::count())->toBe(2);
});

it('refuse un lien qui n’est ni YouTube ni TikTok, sans rien créer', function () {
    Queue::fake();
    preparerTraitement();

    expect(fn () => app(LancerTranscription::class)->lancer(User::factory()->create(), 'https://example.com/video'))
        ->toThrow(ErreurVideo::class);

    expect(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('refuse un fichier dans un format non accepté', function () {
    Queue::fake();
    preparerTraitement();

    $chemin = storage_path('framework/testing/traitement/document.pdf');
    File::ensureDirectoryExists(dirname($chemin));
    File::put($chemin, 'pdf');

    try {
        app(LancerTranscription::class)->lancer(User::factory()->create(), $chemin);
        $this->fail('Un PDF doit être refusé.');
    } catch (ErreurVideo $erreur) {
        expect($erreur->motif)->toBe(MotifEchec::FormatNonSupporte);
    }

    expect(Transcription::count())->toBe(0);
});

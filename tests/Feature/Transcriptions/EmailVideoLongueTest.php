<?php

use App\Enums\StatutTranscription;
use App\Events\TranscriptionCompleted;
use App\Mail\TranscriptionPrete;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Critère F1 : email quand une vidéo de plus de 15 minutes est prête.
it('envoie un email quand une vidéo de plus de 15 minutes est prête', function () {
    Mail::fake();
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'duree_secondes' => 901,
        'statut' => StatutTranscription::Termine,
    ]);

    event(new TranscriptionCompleted($transcription));

    Mail::assertQueued(TranscriptionPrete::class, fn (TranscriptionPrete $courriel) => $courriel->hasTo($utilisateur->email));
});

it('n\'envoie rien pour une vidéo de 15 minutes ou moins', function (int $duree) {
    Mail::fake();
    $transcription = Transcription::factory()->for(User::factory())->create([
        'duree_secondes' => $duree,
        'statut' => StatutTranscription::Termine,
    ]);

    event(new TranscriptionCompleted($transcription));

    Mail::assertNothingQueued();
})->with([
    'exactement 15 minutes' => [900],
    'moins de 15 minutes' => [600],
]);

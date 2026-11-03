<?php

use App\Enums\MotifEchec;
use App\Enums\StatutTranscription;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('n\'affiche qu\'au propriétaire ; un autre compte reçoit 404', function () {
    $transcription = Transcription::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('transcriptions.show', $transcription))->assertNotFound();
});

it('interroge le statut tant que le traitement est en cours', function (StatutTranscription $statut) {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->create(['statut' => $statut]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('wire:poll.3s', false)
        ->assertSee(__('transcriptions.page.en_cours'));
})->with([
    'en attente' => [StatutTranscription::EnAttente],
    'téléchargement' => [StatutTranscription::Telechargement],
    'transcription' => [StatutTranscription::Transcription],
]);

it('n\'interroge plus le statut une fois le traitement terminé ou en échec', function (StatutTranscription $statut) {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'statut' => $statut,
        'motif_echec' => $statut === StatutTranscription::Echec ? MotifEchec::VideoPrivee : null,
    ]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertDontSee('wire:poll', false);
})->with([
    'terminé' => [StatutTranscription::Termine],
    'échec' => [StatutTranscription::Echec],
]);

it('affiche le motif d\'un échec', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'statut' => StatutTranscription::Echec,
        'motif_echec' => MotifEchec::VideoPrivee,
    ]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertSee(__('videos.motifs.video_privee'));
});

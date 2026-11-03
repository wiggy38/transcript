<?php

use App\Jobs\ProcessTranscription;
use App\Livewire\NouvelleTranscription;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('refuse aussi le lancement d\'un fichier tant que l\'email n\'est pas vérifié', function () {
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = User::factory()->unverified()->create();

    Livewire::actingAs($utilisateur)
        ->test(NouvelleTranscription::class)
        ->call('fichierTermine', '4f1c2a9e-1b2c-4d3e-8f40-5a6b7c8d9e0f', 'cours.mp3', 'cours.mp3')
        ->call('lancerFichier')
        ->assertRedirect(route('verification.notice'));

    expect(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

<?php

use App\Contracts\VideoFetcher;
use App\Jobs\ProcessTranscription;
use App\Livewire\NouvelleTranscription;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Video\FauxVideoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function lienVerificationSigne(User $utilisateur): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $utilisateur->id,
        'hash' => sha1($utilisateur->email),
    ]);
}

it('refuse le lancement d\'une transcription tant que l\'email n\'est pas vérifié', function () {
    Queue::fake();
    $utilisateur = User::factory()->unverified()->create();

    Livewire::actingAs($utilisateur)
        ->test(NouvelleTranscription::class)
        ->set('lien', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->call('lancerLien')
        ->assertRedirect(route('verification.notice'));

    expect(Transcription::count())->toBe(0);
    // L'aperçu peut être lu en file ; aucun traitement ne doit partir.
    Queue::assertNotPushed(ProcessTranscription::class);
});

it('lance la transcription d\'un compte dont l\'email est vérifié', function () {
    app()->instance(VideoFetcher::class, new FauxVideoFetcher);
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = User::factory()->create();

    Livewire::actingAs($utilisateur)
        ->test(NouvelleTranscription::class)
        ->set('lien', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->call('lancerLien');

    expect(Transcription::where('user_id', $utilisateur->id)->count())->toBe(1);
    Queue::assertPushed(ProcessTranscription::class);
});

it('vérifie l\'email par le lien signé reçu, sans autre étape', function () {
    $utilisateur = User::factory()->unverified()->create();

    $this->actingAs($utilisateur)
        ->get(lienVerificationSigne($utilisateur))
        ->assertRedirect();

    expect($utilisateur->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('refuse un lien de vérification non signé', function () {
    $utilisateur = User::factory()->unverified()->create();

    $this->actingAs($utilisateur)
        ->get(route('verification.verify', ['id' => $utilisateur->id, 'hash' => sha1($utilisateur->email)]))
        ->assertForbidden();

    expect($utilisateur->fresh()->hasVerifiedEmail())->toBeFalse();
});

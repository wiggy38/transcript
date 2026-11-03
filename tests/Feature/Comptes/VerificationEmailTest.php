<?php

use App\Jobs\ProcessTranscription;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

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

    $this->actingAs($utilisateur)
        ->post('/transcriptions', ['lien' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])
        ->assertRedirect(route('verification.notice'));

    expect(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('lance la transcription d\'un compte dont l\'email est vérifié', function () {
    Queue::fake();
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->post('/transcriptions', ['lien' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])
        ->assertRedirect(route('tableau-de-bord'))
        ->assertSessionHas('statut', 'transcription-lancee');

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

<?php

use App\Jobs\SupprimerFichiersTranscriptions;
use App\Mail\CompteSupprime;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** Transcription terminée avec son MP3 posé sur le disque de stockage. */
function transcriptionAvecMp3(User $utilisateur): Transcription
{
    $transcription = Transcription::factory()->create(['user_id' => $utilisateur->id]);

    $chemin = 'transcriptions/'.$transcription->id.'/audio.mp3';
    Storage::disk(config('voxia.stockage.disque'))->put($chemin, 'mp3');
    Storage::disk(config('voxia.stockage.disque'))->put('transcriptions/'.$transcription->id.'/export.srt', 'srt');

    $transcription->update(['mp3_chemin' => $chemin]);

    return $transcription;
}

beforeEach(function () {
    Storage::fake(config('voxia.stockage.disque'));
});

it('change le nom et l\'adresse, et remet l\'adresse modifiée à vérifier', function () {
    Notification::fake();
    $utilisateur = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

    $this->actingAs($utilisateur)
        ->put('/user/profile-information', ['name' => 'Ada Lovelace', 'email' => 'nouvelle@example.com'])
        ->assertSessionHasNoErrors();

    $utilisateur->refresh();

    expect($utilisateur->name)->toBe('Ada Lovelace')
        ->and($utilisateur->email)->toBe('nouvelle@example.com')
        ->and($utilisateur->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($utilisateur, VerifyEmail::class);
});

it('change le mot de passe après vérification du mot de passe actuel', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'Nouveau-mot-de-passe-9',
            'password_confirmation' => 'Nouveau-mot-de-passe-9',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('Nouveau-mot-de-passe-9', $utilisateur->fresh()->password))->toBeTrue();
});

it('refuse le changement de mot de passe si le mot de passe actuel est faux', function () {
    $utilisateur = User::factory()->create();

    $this->actingAs($utilisateur)
        ->from('/reglages')
        ->put('/user/password', [
            'current_password' => 'faux',
            'password' => 'Nouveau-mot-de-passe-9',
            'password_confirmation' => 'Nouveau-mot-de-passe-9',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'current_password');

    expect(Hash::check('password', $utilisateur->fresh()->password))->toBeTrue();
});

it('enregistre la langue du compte et affiche l\'interface dans cette langue', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($utilisateur)
        ->put('/reglages/langue', ['locale' => 'en'])
        ->assertRedirect(route('reglages'));

    expect($utilisateur->fresh()->locale)->toBe('en');

    $this->actingAs($utilisateur->fresh())
        ->get('/reglages')
        ->assertOk()
        ->assertSee('Account settings');
});

it('refuse une langue hors de la liste prévue', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($utilisateur)
        ->put('/reglages/langue', ['locale' => 'de'])
        ->assertSessionHasErrors('locale');

    expect($utilisateur->fresh()->locale)->toBe('fr');
});

it('ne supprime rien tant que l\'adresse de confirmation ne correspond pas', function () {
    Mail::fake();
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);
    $transcription = transcriptionAvecMp3($utilisateur);

    $this->actingAs($utilisateur)
        ->delete('/reglages/compte', ['confirmation_email' => 'autre@example.com'])
        ->assertSessionHasErrors('confirmation_email');

    expect(User::whereKey($utilisateur->id)->exists())->toBeTrue()
        ->and(Transcription::whereKey($transcription->id)->exists())->toBeTrue();
    Storage::disk(config('voxia.stockage.disque'))->assertExists('transcriptions/'.$transcription->id.'/audio.mp3');
    Mail::assertNothingQueued();
});

it('supprime le compte, ses transcriptions et tous ses fichiers MP3 après confirmation', function () {
    Mail::fake();
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);
    $premiere = transcriptionAvecMp3($utilisateur);
    $seconde = transcriptionAvecMp3($utilisateur);
    UsageRecord::factory()->create(['user_id' => $utilisateur->id, 'transcription_id' => $premiere->id]);

    $this->actingAs($utilisateur)
        ->delete('/reglages/compte', ['confirmation_email' => 'ADA@example.com'])
        ->assertRedirect(route('accueil'))
        ->assertSessionHas('statut', 'compte-supprime');

    expect(User::whereKey($utilisateur->id)->exists())->toBeFalse()
        ->and(Transcription::whereIn('id', [$premiere->id, $seconde->id])->exists())->toBeFalse()
        ->and(UsageRecord::where('user_id', $utilisateur->id)->exists())->toBeFalse();

    // Aucun fichier de transcription ne subsiste sur le disque.
    expect(Storage::disk(config('voxia.stockage.disque'))->allFiles('transcriptions'))->toBe([]);

    $this->assertGuest();
    Mail::assertQueued(CompteSupprime::class, fn (CompteSupprime $mail) => $mail->hasTo('ada@example.com'));
});

it('ne touche ni aux fichiers ni aux transcriptions d\'un autre compte', function () {
    Mail::fake();
    $supprime = User::factory()->create(['email' => 'ada@example.com']);
    $voisin = User::factory()->create();
    transcriptionAvecMp3($supprime);
    $transcriptionVoisine = transcriptionAvecMp3($voisin);

    $this->actingAs($supprime)
        ->delete('/reglages/compte', ['confirmation_email' => 'ada@example.com']);

    expect(Transcription::whereKey($transcriptionVoisine->id)->exists())->toBeTrue();
    Storage::disk(config('voxia.stockage.disque'))
        ->assertExists('transcriptions/'.$transcriptionVoisine->id.'/audio.mp3');
});

it('confie la suppression des fichiers à une tâche de file, avec les identifiants supprimés', function () {
    Queue::fake();
    Mail::fake();
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);
    $transcription = transcriptionAvecMp3($utilisateur);

    $this->actingAs($utilisateur)
        ->delete('/reglages/compte', ['confirmation_email' => 'ada@example.com']);

    Queue::assertPushed(
        SupprimerFichiersTranscriptions::class,
        fn (SupprimerFichiersTranscriptions $job) => $job->transcriptionIds === [$transcription->id],
    );
});

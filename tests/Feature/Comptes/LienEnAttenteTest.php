<?php

use App\Jobs\ProcessTranscription;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

const LIEN_TEST = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

it('garde le lien d\'un visiteur non connecté en session et l\'envoie à l\'inscription', function () {
    Queue::fake();

    $this->post('/lien', ['lien' => LIEN_TEST])
        ->assertRedirect(route('register'));

    $this->assertGuest();
    expect(session('lien_en_attente'))->toBe(LIEN_TEST);
    expect(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('refuse un lien non reconnu sans le garder', function () {
    $this->post('/lien', ['lien' => 'https://example.com/video.mp4'])
        ->assertSessionHasErrors('lien');

    expect(session()->has('lien_en_attente'))->toBeFalse();
});

it('n\'accepte jamais un chemin de fichier local comme lien', function () {
    // Un chemin local lu par le serveur serait une faille : seules les adresses http(s) passent.
    $this->post('/lien', ['lien' => 'C:\\Users\\HP\\secret.mp3'])
        ->assertSessionHasErrors('lien');

    $this->post('/lien', ['lien' => '/etc/passwd'])
        ->assertSessionHasErrors('lien');

    expect(session()->has('lien_en_attente'))->toBeFalse();
});

it('conserve le lien pendant l\'inscription et la vérification, puis le lance à l\'arrivée dans l\'application', function () {
    Queue::fake();
    Notification::fake();

    $this->post('/lien', ['lien' => LIEN_TEST]);

    $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'Mot2passe-solide',
        'password_confirmation' => 'Mot2passe-solide',
    ])->assertRedirect();

    $utilisateur = User::where('email', 'ada@example.com')->firstOrFail();

    // Pendant la vérification : le lien est gardé sur le compte, rien n'est lancé.
    expect($utilisateur->lien_en_attente)->toBe(LIEN_TEST);
    expect(Transcription::count())->toBe(0);

    $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $utilisateur->id,
        'hash' => sha1($utilisateur->email),
    ]))->assertRedirect();

    // Arrivée dans l'application : le lien est repris et la transcription part en file.
    $this->get('/tableau-de-bord')
        ->assertRedirect(route('tableau-de-bord'))
        ->assertSessionHas('statut', 'lien-repris');

    $transcription = Transcription::where('user_id', $utilisateur->id)->firstOrFail();

    expect($transcription->url_origine)->toBe(LIEN_TEST)
        ->and($utilisateur->fresh()->lien_en_attente)->toBeNull();

    Queue::assertPushed(ProcessTranscription::class);
});

it('reporte le lien gardé sur le compte d\'un utilisateur qui se connecte', function () {
    Queue::fake();
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/lien', ['lien' => LIEN_TEST]);

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'password'])
        ->assertRedirect();

    expect($utilisateur->fresh()->lien_en_attente)->toBe(LIEN_TEST)
        ->and(session()->has('lien_en_attente'))->toBeFalse();

    $this->get('/tableau-de-bord');

    expect(Transcription::where('user_id', $utilisateur->id)->where('url_origine', LIEN_TEST)->exists())->toBeTrue();
});

it('garde le lien d\'un compte non vérifié sans le lancer', function () {
    Queue::fake();
    $utilisateur = User::factory()->unverified()->create();

    $this->actingAs($utilisateur)
        ->post('/lien', ['lien' => LIEN_TEST])
        ->assertRedirect(route('tableau-de-bord'));

    $this->get('/tableau-de-bord')->assertOk()->assertSee(__('comptes.tableau.non_verifie'));

    expect($utilisateur->fresh()->lien_en_attente)->toBe(LIEN_TEST)
        ->and(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('ne relance jamais deux fois le même lien gardé', function () {
    Queue::fake();
    $utilisateur = User::factory()->create(['lien_en_attente' => LIEN_TEST]);

    $this->actingAs($utilisateur)->get('/tableau-de-bord');
    $this->actingAs($utilisateur)->get('/tableau-de-bord');

    expect(Transcription::where('user_id', $utilisateur->id)->count())->toBe(1);
    Queue::assertPushed(ProcessTranscription::class, 1);
});

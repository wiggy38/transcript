<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('connecte un compte avec son email et son mot de passe', function () {
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'password'])
        ->assertRedirect(route('tableau-de-bord'));

    $this->assertAuthenticatedAs($utilisateur);
});

it('refuse une connexion avec un mot de passe incorrect', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'faux'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('limite les tentatives de connexion à cinq par minute', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    for ($essai = 0; $essai < 5; $essai++) {
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'faux']);
    }

    // Même avec le bon mot de passe, la sixième tentative est refusée (429) avant toute vérification.
    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'password'])
        ->assertStatus(429);

    $this->assertGuest();
});

it('envoie un lien de réinitialisation puis change le mot de passe avec ce lien', function () {
    Notification::fake();
    $utilisateur = User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/forgot-password', ['email' => 'ada@example.com'])->assertSessionHas('status');

    Notification::assertSentTo($utilisateur, ResetPassword::class);

    /** @var ResetPassword $notification */
    $notification = Notification::sent($utilisateur, ResetPassword::class)->first();

    $this->post('/reset-password', [
        'token' => $notification->token,
        'email' => $utilisateur->email,
        'password' => 'Nouveau-mot-de-passe-9',
        'password_confirmation' => 'Nouveau-mot-de-passe-9',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('Nouveau-mot-de-passe-9', $utilisateur->fresh()->password))->toBeTrue();
});

it('limite les demandes de réinitialisation à cinq par heure et par adresse', function () {
    for ($essai = 0; $essai < 5; $essai++) {
        $this->post('/forgot-password', ['email' => 'ada@example.com']);
    }

    $this->post('/forgot-password', ['email' => 'ada@example.com'])->assertStatus(429);
});

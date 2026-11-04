<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche chaque écran de compte sans erreur, sans police ni script externe', function (string $adresse) {
    $html = $this->get($adresse)->assertOk()->getContent();

    expect($html)
        ->not->toContain('fonts.googleapis')
        ->not->toContain('fonts.bunny')
        ->not->toContain('cdn.jsdelivr');
})->with([
    'accueil' => ['/fr'],
    'connexion' => ['/login'],
    'inscription' => ['/register'],
    'mot de passe oublié' => ['/forgot-password'],
    'réinitialisation' => ['/reset-password/jeton-de-test?email=ada%40example.com'],
]);

it('affiche les écrans de l\'application aux comptes connectés', function (string $adresse, bool $verifie) {
    $utilisateur = User::factory()->create(['email_verified_at' => $verifie ? now() : null]);

    $this->actingAs($utilisateur)->get($adresse)->assertOk();
})->with([
    'tableau de bord vérifié' => ['/tableau-de-bord', true],
    'tableau de bord non vérifié' => ['/tableau-de-bord', false],
    'réglages' => ['/reglages', true],
    'vérification de l\'email' => ['/email/verify', false],
]);

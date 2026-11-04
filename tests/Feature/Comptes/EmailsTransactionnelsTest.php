<?php

use App\Mail\CompteSupprime;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('utilise le thème Surligneur pour tous les emails transactionnels', function () {
    expect(config('mail.markdown.theme'))->toBe('surligneur')
        ->and(file_exists(resource_path('views/vendor/mail/html/themes/surligneur.css')))->toBeTrue();
});

it('rédige l\'email de vérification en français avec le bouton d\'accent', function () {
    $utilisateur = User::factory()->unverified()->create(['name' => 'Ada']);
    app()->setLocale('fr');

    $html = (string) (new VerifyEmail)->toMail($utilisateur)->render();

    expect($html)
        ->toContain('Vérifie ton adresse')
        ->toContain('Bonjour Ada')
        ->toContain('#E4521F')
        ->toContain('/email/verify/');
});

it('rédige l\'email de réinitialisation avec le lien de choix du mot de passe', function () {
    $utilisateur = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    app()->setLocale('fr');

    $html = (string) (new ResetPassword('jeton-de-test'))->toMail($utilisateur)->render();

    expect($html)
        ->toContain('Nouveau mot de passe')
        ->toContain('/reset-password/jeton-de-test')
        ->toContain('email=ada%40example.com');
});

it('rédige la confirmation de suppression en anglais quand l\'interface est en anglais', function () {
    app()->setLocale('en');

    $html = (string) (new CompteSupprime('Ada', 'en'))->render();

    expect($html)
        ->toContain('Account deleted')
        ->toContain('Hello Ada');
});

it('n\'appelle aucune police externe dans les emails', function () {
    $utilisateur = User::factory()->unverified()->create();

    $html = (string) (new VerifyEmail)->toMail($utilisateur)->render();

    expect($html)->not->toContain('fonts.googleapis')
        ->and($html)->not->toContain('fonts.bunny');
});

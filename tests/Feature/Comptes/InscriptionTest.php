<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function donneesInscription(string $email = 'ada@example.com'): array
{
    return [
        'name' => 'Ada Lovelace',
        'email' => $email,
        'password' => 'Mot2passe-solide',
        'password_confirmation' => 'Mot2passe-solide',
    ];
}

it('crée un compte gratuit et envoie le lien de vérification', function () {
    Notification::fake();

    $this->post('/register', donneesInscription())->assertRedirect();

    $utilisateur = User::where('email', 'ada@example.com')->firstOrFail();

    expect($utilisateur->plan->value)->toBe('free')
        ->and($utilisateur->hasVerifiedEmail())->toBeFalse()
        ->and($utilisateur->inscription_ip)->toBe('127.0.0.1');

    Notification::assertSentTo($utilisateur, VerifyEmail::class);
});

it('refuse l\'inscription si la confirmation du mot de passe ne correspond pas', function () {
    $donnees = donneesInscription();
    $donnees['password_confirmation'] = 'autre-chose-du-tout';

    $this->post('/register', $donnees)->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

it('refuse une création gratuite quand l\'IP a atteint la limite configurée, avec un message neutre', function () {
    config(['voxia.comptes.gratuits_par_ip' => 2]);

    User::factory()->count(2)->create(['inscription_ip' => '127.0.0.1']);

    $this->post('/register', donneesInscription('nouvelle@example.com'))
        ->assertSessionHasErrors([
            'email' => __('comptes.inscription.limite_atteinte'),
        ]);

    expect(User::where('email', 'nouvelle@example.com')->exists())->toBeFalse();
});

it('accepte une création tant que la limite par IP n\'est pas atteinte', function () {
    config(['voxia.comptes.gratuits_par_ip' => 2]);

    User::factory()->create(['inscription_ip' => '127.0.0.1']);

    $this->post('/register', donneesInscription('deuxieme@example.com'))->assertRedirect();

    expect(User::where('email', 'deuxieme@example.com')->exists())->toBeTrue();
});

it('ne bloque jamais l\'inscription quand la limite par IP n\'est pas fixée', function () {
    config(['voxia.comptes.gratuits_par_ip' => null]);

    User::factory()->count(5)->create(['inscription_ip' => '127.0.0.1']);

    $this->post('/register', donneesInscription('sans-limite@example.com'))->assertRedirect();

    expect(User::where('email', 'sans-limite@example.com')->exists())->toBeTrue();
});

it('ne compte pas les comptes Pro dans la limite des comptes gratuits', function () {
    config(['voxia.comptes.gratuits_par_ip' => 1]);

    User::factory()->create(['inscription_ip' => '127.0.0.1', 'plan' => 'pro']);

    $this->post('/register', donneesInscription('gratuit@example.com'))->assertRedirect();

    expect(User::where('email', 'gratuit@example.com')->exists())->toBeTrue();
});

it('limite les tentatives d\'inscription par IP', function () {
    // Dix tentatives invalides passent le limiteur, la onzième est refusée.
    for ($essai = 0; $essai < 10; $essai++) {
        $this->post('/register', ['email' => 'pas-un-email'])->assertRedirect();
    }

    $this->post('/register', ['email' => 'pas-un-email'])->assertStatus(429);
});

it('fixe la limite de comptes gratuits par IP à deux par défaut', function () {
    expect(config('voxia.comptes.gratuits_par_ip'))->toBe(2);
});

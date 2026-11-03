<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUtilisateur;

uses(RefreshDatabase::class);

/** Faux utilisateur Google renvoyé par Socialite, sans aucun appel réseau. */
function utilisateurGoogle(string $email = 'ada@example.com', bool $emailVerifie = true): GoogleUtilisateur
{
    $utilisateur = new GoogleUtilisateur;
    $utilisateur->map(['id' => 'google-123', 'name' => 'Ada Lovelace', 'email' => $email]);
    $utilisateur->setRaw(['email_verified' => $emailVerifie]);

    return $utilisateur;
}

/** Remplace le pilote Google de Socialite par un faux qui renvoie $utilisateur. */
function simulerGoogle(GoogleUtilisateur $utilisateur): void
{
    $pilote = Mockery::mock();
    $pilote->shouldReceive('user')->andReturn($utilisateur);
    Socialite::shouldReceive('driver')->with('google')->andReturn($pilote);
}

beforeEach(function () {
    Http::preventStrayRequests();
});

it('reste désactivée par défaut : les routes répondent 404 et le bouton est absent', function () {
    expect(config('voxia.comptes.google_actif'))->toBeFalse();

    $this->get('/auth/google')->assertNotFound();
    $this->get('/auth/google/callback')->assertNotFound();
    $this->get('/login')->assertOk()->assertDontSee(__('comptes.google.bouton'));
});

it('redirige vers Google quand la connexion est activée par configuration', function () {
    config(['voxia.comptes.google_actif' => true]);

    $pilote = Mockery::mock();
    $pilote->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($pilote);

    $this->get('/auth/google')->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    $this->get('/login')->assertSee(__('comptes.google.bouton'));
});

it('crée un compte gratuit vérifié à partir d\'un compte Google', function () {
    config(['voxia.comptes.google_actif' => true]);
    simulerGoogle(utilisateurGoogle());

    $this->get('/auth/google/callback')->assertRedirect();

    $utilisateur = User::where('email', 'ada@example.com')->firstOrFail();

    expect($utilisateur->google_id)->toBe('google-123')
        ->and($utilisateur->hasVerifiedEmail())->toBeTrue()
        ->and($utilisateur->plan->value)->toBe('free')
        ->and($utilisateur->password)->toBeNull();

    $this->assertAuthenticatedAs($utilisateur);
});

it('refuse une adresse Google que Google n\'a pas vérifiée', function () {
    config(['voxia.comptes.google_actif' => true]);
    simulerGoogle(utilisateurGoogle('ada@example.com', emailVerifie: false));

    $this->get('/auth/google/callback')->assertRedirect(route('login'));

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

it('rattache un compte existant vérifié qui a la même adresse', function () {
    config(['voxia.comptes.google_actif' => true]);
    $existant = User::factory()->create(['email' => 'ada@example.com']);
    simulerGoogle(utilisateurGoogle());

    $this->get('/auth/google/callback');

    expect($existant->fresh()->google_id)->toBe('google-123')
        ->and(User::count())->toBe(1);
    $this->assertAuthenticatedAs($existant);
});

it('ne rattache jamais un compte non vérifié à Google, pour éviter une prise de contrôle', function () {
    config(['voxia.comptes.google_actif' => true]);
    $existant = User::factory()->unverified()->create(['email' => 'ada@example.com']);
    simulerGoogle(utilisateurGoogle());

    $this->get('/auth/google/callback')->assertRedirect(route('login'));

    expect($existant->fresh()->google_id)->toBeNull();
    $this->assertGuest();
});

it('applique la limite de comptes gratuits par IP aussi à la connexion Google', function () {
    config([
        'voxia.comptes.google_actif' => true,
        'voxia.comptes.gratuits_par_ip' => 1,
    ]);
    User::factory()->create(['inscription_ip' => '127.0.0.1']);
    simulerGoogle(utilisateurGoogle());

    $this->get('/auth/google/callback')->assertRedirect(route('login'));

    expect(User::where('email', 'ada@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

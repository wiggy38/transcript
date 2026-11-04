<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => app()->setLocale('fr'));

it('renvoie un visiteur anonyme vers la connexion du back-office', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('répond 403 à un utilisateur connecté qui n\'est pas administrateur', function () {
    $utilisateur = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($utilisateur)->get('/admin')->assertForbidden();
    $this->actingAs($utilisateur)->get('/admin/users')->assertForbidden();
    $this->actingAs($utilisateur)->get('/admin/transcriptions')->assertForbidden();
    $this->actingAs($utilisateur)->get('/admin/journal-administrations')->assertForbidden();
});

it('ouvre le tableau de bord à un administrateur', function () {
    $administrateur = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

    $this->actingAs($administrateur)->get('/admin')->assertOk();
});

it('donne l\'accès au back-office avec la commande voxia:make-admin', function () {
    $utilisateur = User::factory()->create(['email' => 'equipe@voxia.test', 'is_admin' => false]);

    $this->artisan('voxia:make-admin', ['email' => 'EQUIPE@voxia.test'])->assertSuccessful();

    expect($utilisateur->fresh()->is_admin)->toBeTrue();
    $this->actingAs($utilisateur->fresh())->get('/admin')->assertOk();
});

it('refuse voxia:make-admin pour une adresse sans compte', function () {
    $this->artisan('voxia:make-admin', ['email' => 'inconnu@voxia.test'])->assertFailed();
});

<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un nouveau compte a la langue français et aucun droit d\'administration', function () {
    $utilisateur = User::factory()->create();

    expect($utilisateur->locale)->toBe('fr')
        ->and($utilisateur->is_admin)->toBeFalse();
});

test('is_admin ne peut pas être réglé par saisie de masse', function () {
    $utilisateur = User::create([
        'name' => 'Intrus',
        'email' => 'intrus@example.test',
        'password' => 'mot-de-passe-long',
        'is_admin' => true,
    ]);

    expect($utilisateur->fresh()->is_admin)->toBeFalse();
});

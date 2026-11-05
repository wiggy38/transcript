<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('masque le bouton Facebook sur la connexion et l\'inscription par défaut', function () {
    expect(config('voxia.comptes.facebook_actif'))->toBeFalse();

    $this->get('/login')->assertOk()->assertDontSee(__('comptes.connexion.facebook_bouton'));
    $this->get('/register')->assertOk()->assertDontSee(__('comptes.inscription.facebook_bouton'));
});

it('affiche le bouton Facebook sur la connexion et l\'inscription quand VOXIA_FACEBOOK_LOGIN=true', function () {
    config(['voxia.comptes.facebook_actif' => true]);

    $this->get('/login')->assertOk()->assertSee(__('comptes.connexion.facebook_bouton'));
    $this->get('/register')->assertOk()->assertSee(__('comptes.inscription.facebook_bouton'));
});

it('n\'affiche pas le bloc social quand Google et Facebook sont désactivés', function () {
    $this->get('/login')->assertOk()->assertDontSee(__('comptes.connexion.ou_email'));
    $this->get('/register')->assertOk()->assertDontSee(__('comptes.inscription.ou_email'));
});

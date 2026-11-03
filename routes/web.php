<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Comptes\GoogleController;
use App\Http\Controllers\Comptes\LienController;
use App\Http\Controllers\Comptes\ReglagesController;
use App\Http\Controllers\Comptes\TableauDeBordController;
use App\Http\Controllers\Comptes\TranscriptionController;
use Illuminate\Support\Facades\Route;

// Parcours principal (cahier §2) : la page d'accueil reçoit le lien, les comptes le gardent.
Route::get('/', AccueilController::class)->name('accueil');
Route::post('/lien', [LienController::class, 'store'])->name('lien.store');

// Connexion Google : répond 404 tant que VOXIA_GOOGLE_LOGIN n'est pas à true.
Route::get('/auth/google', [GoogleController::class, 'rediriger'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'retour'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/tableau-de-bord', TableauDeBordController::class)->name('tableau-de-bord');

    // Lancement d'une transcription : email vérifié exigé (route « verified » de Fortify).
    Route::post('/transcriptions', [TranscriptionController::class, 'store'])
        ->middleware('verified')
        ->name('transcriptions.store');

    Route::get('/reglages', [ReglagesController::class, 'afficher'])->name('reglages');
    Route::put('/reglages/langue', [ReglagesController::class, 'langue'])->name('reglages.langue');
    Route::delete('/reglages/compte', [ReglagesController::class, 'supprimer'])->name('reglages.supprimer');
});

// Guide de style : visible seulement en local, jamais en production.
if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}

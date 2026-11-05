<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Comptes\DonneesController;
use App\Http\Controllers\Comptes\ExportAnalyseController;
use App\Http\Controllers\Comptes\ExportController;
use App\Http\Controllers\Comptes\ExportReecritureController;
use App\Http\Controllers\Comptes\ExportResumeController;
use App\Http\Controllers\Comptes\GoogleController;
use App\Http\Controllers\Comptes\HistoriqueController;
use App\Http\Controllers\Comptes\LienController;
use App\Http\Controllers\Comptes\ReglagesController;
use App\Http\Controllers\Comptes\TableauDeBordController;
use App\Http\Controllers\Comptes\TelechargementMp3Controller;
use App\Http\Controllers\Comptes\TeleversementController;
use App\Http\Controllers\Comptes\TranscriptionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\Paiement\AbonnementController;
use App\Http\Controllers\Paiement\PaiementController;
use App\Http\Controllers\Paiement\WebhookPaddleController;
use Illuminate\Support\Facades\Route;

// Pages publiques : une adresse par langue (/fr, /en), avec balises hreflang. La racine redirige
// vers la langue du visiteur (App\Support\Langue::resoudre).
Route::get('/', fn () => redirect()->route('accueil'))->name('racine');

// Parcours principal (cahier §2) : la page d'accueil reçoit le lien, les comptes le gardent.
Route::post('/lien', [LienController::class, 'store'])->name('lien.store');

Route::prefix('{locale}')->whereIn('locale', ['en', 'fr'])->group(function () {
    Route::get('/', AccueilController::class)->name('accueil');

    // Pages du pied de page et de la page d'accueil. Textes juridiques : brouillons à relire (cahier §9).
    Route::view('/tarifs', 'pages.tarifs')->name('tarifs');
    Route::view('/questions', 'pages.questions')->name('questions');
    Route::view('/conditions', 'pages.juridique', ['cle' => 'conditions'])->name('conditions');
    Route::view('/confidentialite', 'pages.juridique', ['cle' => 'confidentialite'])->name('confidentialite');

    // Formulaire de contact : protégé par un champ piège, un délai minimal et une limite par IP (ContactController).
    Route::get('/contact', [ContactController::class, 'afficher'])->name('contact');
    Route::post('/contact', [ContactController::class, 'envoyer'])->name('contact.envoyer');
});

// Choix de langue (sélecteur) : mémorise le choix, l'enregistre sur le compte s'il y en a un,
// et renvoie sur la même page dans la nouvelle langue.
Route::post('/langue', [LangueController::class, 'changer'])->name('langue.changer');

// Connexion Google : répond 404 tant que VOXIA_GOOGLE_LOGIN n'est pas à true.
Route::get('/auth/google', [GoogleController::class, 'rediriger'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'retour'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/tableau-de-bord', TableauDeBordController::class)->name('tableau-de-bord');

    // Lancement : depuis l'écran Livewire NouvelleTranscription. Email vérifié exigé.
    Route::get('/transcriptions/{transcription}', [TranscriptionController::class, 'show'])
        ->name('transcriptions.show');

    // Historique (F5) : liste, tri par date, pagination, recherche plein texte (Pro et Pack).
    Route::get('/historique', HistoriqueController::class)->name('historique');

    // Suppression : une page de confirmation, puis la suppression elle-même (DELETE).
    Route::get('/transcriptions/{transcription}/supprimer', [TranscriptionController::class, 'confirmerSuppression'])
        ->name('transcriptions.supprimer');
    Route::delete('/transcriptions/{transcription}', [TranscriptionController::class, 'supprimer'])
        ->name('transcriptions.destroy');

    // MP3 de la transcription, pour le lecteur audio (TikTok et fichiers). Propriétaire seulement.
    Route::get('/transcriptions/{transcription}/audio', [TranscriptionController::class, 'audio'])
        ->name('transcriptions.audio');

    // Téléchargement du MP3 (cahier F3) : lien signé et temporaire, émis par la page de transcription.
    Route::get('/transcriptions/{transcription}/mp3', TelechargementMp3Controller::class)
        ->middleware('signed')
        ->name('transcriptions.mp3');

    // Export d'une transcription (cahier F4) : TXT, PDF, SRT, VTT selon le plan. Lien signé et temporaire.
    Route::get('/transcriptions/{transcription}/exports/{format}', ExportController::class)
        ->whereIn('format', ['txt', 'pdf', 'srt', 'vtt', 'docx', 'json'])
        ->middleware('signed')
        ->name('transcriptions.export');

    // Export de l'analyse de script (cahier F7) : TXT et PDF selon le plan. Lien signé et temporaire.
    Route::get('/transcriptions/{transcription}/analyse/{format}', ExportAnalyseController::class)
        ->whereIn('format', ['txt', 'pdf'])
        ->middleware('signed')
        ->name('transcriptions.analyse.export');

    // Export du résumé (V2) : TXT et PDF selon le plan, DOCX réservé au Business. Lien signé et temporaire.
    Route::get('/transcriptions/{transcription}/resume/{format}', ExportResumeController::class)
        ->whereIn('format', ['txt', 'pdf', 'docx'])
        ->middleware('signed')
        ->name('transcriptions.resume.export');

    // Export d'une réécriture (V2) : format de contenu (article, newsletter, linkedin, thread) et format de fichier. Lien signé.
    Route::get('/transcriptions/{transcription}/reecriture/{format}/{export}', ExportReecritureController::class)
        ->whereIn('format', ['article', 'newsletter', 'linkedin', 'thread'])
        ->whereIn('export', ['txt', 'md', 'docx'])
        ->middleware('signed')
        ->name('transcriptions.reecriture.export');

    // Morceaux d'un fichier importé (2 Go par défaut, envoi découpé). Email vérifié exigé.
    Route::post('/televersements/morceaux', [TeleversementController::class, 'morceau'])
        ->middleware('verified')
        ->name('televersements.morceau');

    Route::get('/reglages', [ReglagesController::class, 'afficher'])->name('reglages');
    Route::put('/reglages/langue', [ReglagesController::class, 'langue'])->name('reglages.langue');
    Route::delete('/reglages/compte', [ReglagesController::class, 'supprimer'])->name('reglages.supprimer');

    // Export de toutes les données (ZIP) : préparé par la file, puis livré par un lien signé reçu par email.
    Route::post('/reglages/donnees', [DonneesController::class, 'demander'])->name('reglages.donnees.demander');
    Route::get('/reglages/donnees/{utilisateur}/telecharger', [DonneesController::class, 'telecharger'])
        ->middleware('signed')
        ->name('reglages.donnees.telecharger');

    // Paiement (cahier §5) : ouverture d'une formule, espace Abonnement. Email vérifié exigé pour payer.
    Route::get('/paiement/{produit}', [PaiementController::class, 'afficher'])
        ->whereIn('produit', ['pro_mensuel', 'pro_annuel', 'business_mensuel', 'business_annuel', 'pack'])
        ->middleware('verified')
        ->name('paiement.show');

    Route::get('/abonnement', [AbonnementController::class, 'afficher'])->name('abonnement');
    Route::put('/abonnement/formule', [AbonnementController::class, 'changerFormule'])->name('abonnement.formule');
    Route::post('/abonnement/resiliation', [AbonnementController::class, 'resilier'])->name('abonnement.resilier');
    Route::post('/abonnement/resiliation/annuler', [AbonnementController::class, 'annulerResiliation'])->name('abonnement.reprendre');
    Route::get('/abonnement/factures/{facture}', [AbonnementController::class, 'facture'])->name('abonnement.facture');
});

// Webhooks du prestataire : sans session ni CSRF, signature vérifiée (voir WebhookPaddleController).
Route::post(config('cashier.path').'/webhook', WebhookPaddleController::class)->name('paddle.webhook');

// Guide de style : visible seulement en local, jamais en production.
if (app()->isLocal()) {
    Route::view('/styleguide', 'styleguide')->name('styleguide');
}

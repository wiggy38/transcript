<?php

use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Services\Recherche\RechercheTranscriptions;
use App\Support\ConfigurationRecherche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// La recherche plein texte est propre à PostgreSQL : ces tests sont ignorés sur SQLite.
uses(RefreshDatabase::class);

$pasPostgres = fn () => DB::getDriverName() !== 'pgsql';

function rechercheResumeCompte(): User
{
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    app()->setLocale('fr');

    return $utilisateur;
}

/** Transcription française avec un paragraphe qui ne contient pas le mot cherché. */
function rechercheResumeTranscription(User $utilisateur, string $titre): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create([
        'titre' => $titre,
        'langue_detectee' => 'fr',
    ]);

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 10_000,
        'texte' => 'Bienvenue dans l’atelier, on parle de poterie.',
        'config_recherche' => ConfigurationRecherche::pourLangue('fr'),
    ]);

    return $transcription;
}

/** Résumé avec son texte de recherche, dans l'état donné. */
function rechercheResumeSortie(Transcription $transcription, string $texte, StatutSortieIa $statut = StatutSortieIa::Termine): AiOutput
{
    return AiOutput::create([
        'transcription_id' => $transcription->getKey(),
        'fonctionnalite' => 'resume',
        'statut' => $statut,
        'langue' => 'fr',
        'credits' => 1,
        'texte_recherche' => $texte,
        'config_recherche' => ConfigurationRecherche::pourLangue('fr'),
    ]);
}

// Critère recherche : un mot présent seulement dans le résumé renvoie un résultat, sans horodatage de passage.
it('trouve un mot du résumé et ouvre la transcription sans horodatage', function () {
    $utilisateur = rechercheResumeCompte();
    $transcription = rechercheResumeTranscription($utilisateur, 'Atelier poterie');
    rechercheResumeSortie($transcription, "Le lancement du produit est prévu demain.\nUne offre limitée.");

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertOk()
        ->assertSee('Dans le résumé')
        ->assertSee(route('transcriptions.show', ['transcription' => $transcription]), false)
        ->assertDontSee('&t=');
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : seul un résumé terminé est cherché ; un résumé en cours ou en échec ne l'est pas.
it('ne cherche pas dans un résumé en cours ou en échec', function (StatutSortieIa $statut) {
    $utilisateur = rechercheResumeCompte();
    $transcription = rechercheResumeTranscription($utilisateur, 'Atelier poterie');
    rechercheResumeSortie($transcription, 'Le lancement du produit est prévu demain.', $statut);

    $resultats = app(RechercheTranscriptions::class)->rechercher($utilisateur, 'lancement', 10, 1);

    expect($resultats->total())->toBe(0);
})->with([StatutSortieIa::EnCours, StatutSortieIa::Echec])
    ->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : un compte ne trouve jamais le résumé d'un autre compte.
it('ne renvoie pas le résumé d’un autre compte', function () {
    $proprietaire = rechercheResumeCompte();
    $transcription = rechercheResumeTranscription($proprietaire, 'Atelier poterie');
    rechercheResumeSortie($transcription, 'Le lancement du produit est prévu demain.');

    $autre = rechercheResumeCompte();

    expect(app(RechercheTranscriptions::class)->rechercher($autre, 'lancement', 10, 1)->total())->toBe(0);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : le résumé et un passage de la même transcription sont tous deux listés, le résumé en premier.
it('liste le résumé avant les passages de sa transcription', function () {
    $utilisateur = rechercheResumeCompte();
    $transcription = rechercheResumeTranscription($utilisateur, 'Atelier poterie');
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 2,
        'debut_ms' => 30_000,
        'fin_ms' => 40_000,
        'texte' => 'Le lancement commence demain.',
        'config_recherche' => ConfigurationRecherche::pourLangue('fr'),
    ]);
    rechercheResumeSortie($transcription, 'Le lancement du produit est prévu demain.');

    $resultats = app(RechercheTranscriptions::class)->rechercher($utilisateur, 'lancement', 10, 1);

    expect($resultats->total())->toBe(2)
        ->and($resultats->items()[0]->estDuResume())->toBeTrue()
        ->and($resultats->items()[1]->debutMs)->toBe(30_000);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

<?php

require_once __DIR__.'/outils.php';

use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Models\AiOutput;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Recherche\RechercheTranscriptions;
use App\Support\ConfigurationRecherche;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// La recherche plein texte est propre à PostgreSQL : ces tests sont ignorés sur SQLite.
uses(RefreshDatabase::class);

$pasPostgres = fn () => DB::getDriverName() !== 'pgsql';

/** Traduction avec son texte de recherche, dans l'état donné. */
function rechercheTraductionSortie(Transcription $transcription, string $texte, string $langue, StatutSortieIa $statut = StatutSortieIa::Termine): AiOutput
{
    return AiOutput::create([
        'transcription_id' => $transcription->getKey(),
        'fonctionnalite' => 'traduction',
        'format' => $langue,
        'statut' => $statut,
        'langue' => $langue,
        'credits' => 1,
        'texte_recherche' => $texte,
        'config_recherche' => ConfigurationRecherche::pourLangue($langue),
    ]);
}

// Critère recherche : les traductions sont incluses dans la recherche de l'historique, sans passage précis.
it('trouve un mot de la traduction et l’indique comme traduction', function () {
    $utilisateur = User::factory()->create();
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    $transcription = traductionTranscription($utilisateur, 60);
    rechercheTraductionSortie($transcription, 'The balcony receives strong sunlight.', 'en');

    $resultats = app(RechercheTranscriptions::class)->rechercher($utilisateur, 'sunlight', 10, 1);

    expect($resultats->total())->toBe(1)
        ->and($resultats->items()[0]->estUneTraduction())->toBeTrue()
        ->and($resultats->items()[0]->langueTraduction)->toBe('en')
        ->and($resultats->items()[0]->debutMs)->toBeNull();
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : une traduction en cours ou en échec n'est pas cherchée.
it('ne cherche pas dans une traduction en cours ou en échec', function (StatutSortieIa $statut) {
    $utilisateur = User::factory()->create();
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    $transcription = traductionTranscription($utilisateur, 60);
    rechercheTraductionSortie($transcription, 'The balcony receives strong sunlight.', 'en', $statut);

    expect(app(RechercheTranscriptions::class)->rechercher($utilisateur, 'sunlight', 10, 1)->total())->toBe(0);
})->with([StatutSortieIa::EnCours, StatutSortieIa::Echec])
    ->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : un résumé n'est pas compté comme une traduction, et inversement.
it('distingue le résumé de la traduction dans les résultats', function () {
    $utilisateur = User::factory()->create();
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    $transcription = traductionTranscription($utilisateur, 60);
    rechercheTraductionSortie($transcription, 'The balcony receives strong sunlight.', 'en');
    AiOutput::create([
        'transcription_id' => $transcription->getKey(),
        'fonctionnalite' => 'resume',
        'statut' => StatutSortieIa::Termine,
        'langue' => 'fr',
        'credits' => 1,
        'texte_recherche' => 'Le balcon reçoit beaucoup de soleil.',
        'config_recherche' => ConfigurationRecherche::pourLangue('fr'),
    ]);

    $resultats = app(RechercheTranscriptions::class)->rechercher($utilisateur, 'balcony', 10, 1);
    $resume = app(RechercheTranscriptions::class)->rechercher($utilisateur, 'soleil', 10, 1);

    expect($resultats->items()[0]->estUneTraduction())->toBeTrue()
        ->and($resume->items()[0]->estDuResume())->toBeTrue()
        ->and($resume->items()[0]->estUneTraduction())->toBeFalse();
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// Critère recherche : une traduction ne se trouve jamais dans le compte d'un autre utilisateur.
it('ne renvoie pas la traduction d’un autre compte', function () {
    $proprietaire = User::factory()->create();
    $proprietaire->forceFill(['plan' => Plan::Pro])->save();
    $transcription = traductionTranscription($proprietaire, 60);
    rechercheTraductionSortie($transcription, 'The balcony receives strong sunlight.', 'en');

    $autre = User::factory()->create();
    $autre->forceFill(['plan' => Plan::Pro])->save();

    expect(app(RechercheTranscriptions::class)->rechercher($autre, 'sunlight', 10, 1)->total())->toBe(0);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

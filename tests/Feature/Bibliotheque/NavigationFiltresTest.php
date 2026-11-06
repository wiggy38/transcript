<?php

// Colonne latérale et filtres combinables (V2). Les filtres sont lus dans l'URL.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\TypeEtiquette;
use App\Models\ScriptAnalysis;
use App\Models\Transcription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche Toutes, Non classé, les dossiers, les chaînes et les étiquettes par type dans la colonne latérale', function () {
    $utilisateur = bibPro();
    bibDossier($utilisateur, 'Concurrents lampe');
    $etiquette = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');
    $transcription = bibTranscription($utilisateur, ['chaine' => 'Marque Lune']);
    $transcription->etiquettes()->attach($etiquette->getKey());

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertOk()
        ->assertSee('id="titre-bibliotheque"', false)
        ->assertSee(__('bibliotheque.navigation.toutes'))
        ->assertSee(__('bibliotheque.navigation.non_classees'))
        ->assertSee('Concurrents lampe')
        ->assertSee('Marque Lune')
        ->assertSee(TypeEtiquette::Niche->libelle())
        ->assertSee('Café filtre');
});

it('combine les filtres : dossier, étiquette, plateforme et analyse de script', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');
    $etiquette = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');

    $cible = bibTranscription($utilisateur, ['titre' => 'Titre retenu', 'source' => 'tiktok', 'dossier' => $dossier]);
    $cible->etiquettes()->attach($etiquette->getKey());
    ScriptAnalysis::factory()->termine()->for($cible)->create();

    $autrePlateforme = bibTranscription($utilisateur, ['titre' => 'Titre youtube', 'source' => 'youtube', 'dossier' => $dossier]);
    $autrePlateforme->etiquettes()->attach($etiquette->getKey());
    ScriptAnalysis::factory()->termine()->for($autrePlateforme)->create();

    $sansAnalyse = bibTranscription($utilisateur, ['titre' => 'Titre sans analyse', 'source' => 'tiktok', 'dossier' => $dossier]);
    $sansAnalyse->etiquettes()->attach($etiquette->getKey());

    $horsDossier = bibTranscription($utilisateur, ['titre' => 'Titre hors dossier', 'source' => 'tiktok']);
    $horsDossier->etiquettes()->attach($etiquette->getKey());
    ScriptAnalysis::factory()->termine()->for($horsDossier)->create();

    $this->actingAs($utilisateur)
        ->get(route('historique', [
            'dossier' => $dossier->getKey(),
            'etiquettes' => [$etiquette->getKey()],
            'plateforme' => 'tiktok',
            'analyse' => 'oui',
        ]))
        ->assertOk()
        ->assertSee('Titre retenu')
        ->assertDontSee('Titre youtube')
        ->assertDontSee('Titre sans analyse')
        ->assertDontSee('Titre hors dossier');
});

it('garde les filtres dans l’URL : le formulaire réaffiche la valeur choisie', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');

    $this->actingAs($utilisateur)
        ->get(route('historique', ['dossier' => $dossier->getKey(), 'periode' => 30]))
        ->assertOk()
        ->assertSee('value="'.$dossier->getKey().'" selected', false)
        ->assertSee('value="30" selected', false);
});

it('filtre par « Non classé » avec la valeur aucun', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');
    bibTranscription($utilisateur, ['titre' => 'Titre rangé', 'dossier' => $dossier]);
    bibTranscription($utilisateur, ['titre' => 'Titre libre']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['dossier' => 'aucun']))
        ->assertOk()
        ->assertSee('Titre libre')
        ->assertDontSee('Titre rangé');
});

it('filtre par chaîne et par période', function () {
    $utilisateur = bibPro();
    bibTranscription($utilisateur, ['titre' => 'Titre récent', 'chaine' => 'Marque Lune'], now()->subDays(2));
    bibTranscription($utilisateur, ['titre' => 'Titre ancien', 'chaine' => 'Marque Lune'], now()->subDays(90));
    bibTranscription($utilisateur, ['titre' => 'Titre autre chaîne', 'chaine' => 'Marque Soleil'], now()->subDays(2));

    $this->actingAs($utilisateur)
        ->get(route('historique', ['chaine' => 'Marque Lune', 'periode' => 30]))
        ->assertOk()
        ->assertSee('Titre récent')
        ->assertDontSee('Titre ancien')
        ->assertDontSee('Titre autre chaîne');
});

it('ignore les valeurs de filtre inconnues plutôt que de planter', function () {
    $utilisateur = bibPro();
    bibTranscription($utilisateur, ['titre' => 'Titre visible']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['dossier' => 'n-importe-quoi', 'plateforme' => 'myspace', 'periode' => 999, 'analyse' => 'peut-etre', 'portee' => 'autre']))
        ->assertOk()
        ->assertSee('Titre visible');
});

it('n’affiche aucun filtre ni colonne latérale au plan Gratuit sans dossier ni étiquette : liste du MVP', function () {
    $utilisateur = bibGratuit();
    bibTranscription($utilisateur, ['titre' => 'Titre gratuit un']);
    bibTranscription($utilisateur, ['titre' => 'Titre gratuit deux', 'source' => 'tiktok']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['plateforme' => 'tiktok']))
        ->assertOk()
        ->assertDontSee('id="titre-bibliotheque"', false)
        ->assertDontSee('name="plateforme"', false)
        ->assertSee('Titre gratuit un')
        ->assertSee('Titre gratuit deux');
});

it('trie la liste filtrée par date, la plus ancienne d’abord', function () {
    $utilisateur = bibPro();
    bibTranscription($utilisateur, ['titre' => 'Premier'], now()->subDays(5));
    bibTranscription($utilisateur, ['titre' => 'Dernier'], now()->subDay());

    $reponse = $this->actingAs($utilisateur)
        ->get(route('historique', ['tri' => 'ancien']))
        ->assertOk();

    $titres = $reponse->viewData('transcriptions')->pluck('titre')->all();

    expect($titres)->toBe(['Premier', 'Dernier']);
    expect(Transcription::query()->where('user_id', $utilisateur->getKey())->count())->toBe(2);
});

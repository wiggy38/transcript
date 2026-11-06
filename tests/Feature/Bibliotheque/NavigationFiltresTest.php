<?php

// Colonne latérale et filtres (V2) : Étiquette, Plateforme et Produit, lus dans l'URL.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\TypeEtiquette;
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

it('ne propose dans le formulaire que trois filtres : Étiquette, Produit et Plateforme', function () {
    $utilisateur = bibPro();
    bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Lampe LED');
    bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertOk()
        ->assertSee('name="etiquettes[]"', false)
        ->assertSee('name="produit"', false)
        ->assertSee('name="plateforme"', false)
        ->assertDontSee('name="periode"', false)
        ->assertDontSee('name="analyse"', false)
        ->assertDontSee('name="resume"', false)
        ->assertDontSee('name="chaine"', false)
        ->assertDontSee('name="dossier"', false);
});

it('combine les filtres : étiquette, produit et plateforme', function () {
    $utilisateur = bibPro();
    $niche = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');
    $produit = bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Lampe LED');
    $autreProduit = bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Tapis');

    $cible = bibTranscription($utilisateur, ['titre' => 'Titre retenu', 'source' => 'tiktok']);
    $cible->etiquettes()->attach([$niche->getKey(), $produit->getKey()]);

    $autrePlateforme = bibTranscription($utilisateur, ['titre' => 'Titre youtube', 'source' => 'youtube']);
    $autrePlateforme->etiquettes()->attach([$niche->getKey(), $produit->getKey()]);

    $sansProduit = bibTranscription($utilisateur, ['titre' => 'Titre sans produit', 'source' => 'tiktok']);
    $sansProduit->etiquettes()->attach($niche->getKey());

    $autreProduitTranscription = bibTranscription($utilisateur, ['titre' => 'Titre autre produit', 'source' => 'tiktok']);
    $autreProduitTranscription->etiquettes()->attach([$niche->getKey(), $autreProduit->getKey()]);

    $this->actingAs($utilisateur)
        ->get(route('historique', [
            'etiquettes' => [$niche->getKey()],
            'produit' => $produit->getKey(),
            'plateforme' => 'tiktok',
        ]))
        ->assertOk()
        ->assertSee('Titre retenu')
        ->assertDontSee('Titre youtube')
        ->assertDontSee('Titre sans produit')
        ->assertDontSee('Titre autre produit');
});

it('réserve le filtre Produit aux étiquettes de type produit', function () {
    $utilisateur = bibPro();
    $niche = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');
    $transcription = bibTranscription($utilisateur, ['titre' => 'Titre niche']);
    $transcription->etiquettes()->attach($niche->getKey());

    // Une étiquette de type niche passée au filtre Produit ne ramène rien.
    $this->actingAs($utilisateur)
        ->get(route('historique', ['produit' => $niche->getKey()]))
        ->assertOk()
        ->assertDontSee('Titre niche');
});

it('garde les filtres dans l’URL : le formulaire réaffiche la valeur choisie', function () {
    $utilisateur = bibPro();
    $produit = bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Lampe LED');

    $this->actingAs($utilisateur)
        ->get(route('historique', ['produit' => $produit->getKey(), 'plateforme' => 'tiktok']))
        ->assertOk()
        ->assertSee('value="'.$produit->getKey().'" checked', false)
        ->assertSee('value="tiktok" checked', false);
});

it('garde cochées les étiquettes choisies et affiche leur nombre dans le menu', function () {
    $utilisateur = bibPro();
    $niche = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café filtre');
    $libre = bibEtiquette($utilisateur, TypeEtiquette::Libre, 'À revoir');

    $this->actingAs($utilisateur)
        ->get(route('historique', ['etiquettes' => [$niche->getKey(), $libre->getKey()]]))
        ->assertOk()
        ->assertSee('value="'.$niche->getKey().'" checked', false)
        ->assertSee('value="'.$libre->getKey().'" checked', false)
        ->assertSee(__('bibliotheque.filtres.etiquettes_nombre', ['nombre' => 2]));
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

it('filtre par chaîne, lien de la colonne latérale', function () {
    $utilisateur = bibPro();
    bibTranscription($utilisateur, ['titre' => 'Titre lune', 'chaine' => 'Marque Lune']);
    bibTranscription($utilisateur, ['titre' => 'Titre soleil', 'chaine' => 'Marque Soleil']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['chaine' => 'Marque Lune']))
        ->assertOk()
        ->assertSee('Titre lune')
        ->assertDontSee('Titre soleil');
});

it('ignore les valeurs de filtre inconnues plutôt que de planter', function () {
    $utilisateur = bibPro();
    bibTranscription($utilisateur, ['titre' => 'Titre visible']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['dossier' => 'n-importe-quoi', 'plateforme' => 'myspace', 'produit' => 'abc', 'portee' => 'autre']))
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

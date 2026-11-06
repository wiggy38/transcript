<?php

// Performance de la bibliothèque (V2, critère de l'étape 5 : liste filtrée et recherche sous une seconde sur 500 transcriptions).
// La recherche plein texte n'existe que sur PostgreSQL : le test de recherche est ignoré sur SQLite.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\TypeEtiquette;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

$pasPostgres = fn () => DB::getDriverName() !== 'pgsql';

const BIB_NOMBRE_TRANSCRIPTIONS = 500;

/**
 * 500 transcriptions : un dossier sur cinq (indice modulo 5), une étiquette sur deux (indice pair).
 * Les insertions passent par le constructeur de requêtes pour rester rapides.
 *
 * @return array{dossiers: list<int>, etiquette: int, produit: int}
 */
function bibRemplirBibliotheque(User $utilisateur): array
{
    $maintenant = now();
    $dossiers = [];

    for ($d = 0; $d < 5; $d++) {
        $dossiers[] = DB::table('dossiers')->insertGetId([
            'user_id' => $utilisateur->getKey(),
            'nom' => "Dossier {$d}",
            'nom_normalise' => "dossier {$d}",
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);
    }

    $etiquette = DB::table('etiquettes')->insertGetId([
        'user_id' => $utilisateur->getKey(),
        'type' => TypeEtiquette::Niche->value,
        'nom' => 'Café',
        'nom_normalise' => 'café',
        'created_at' => $maintenant,
        'updated_at' => $maintenant,
    ]);

    $produit = DB::table('etiquettes')->insertGetId([
        'user_id' => $utilisateur->getKey(),
        'type' => TypeEtiquette::Produit->value,
        'nom' => 'Lampe LED',
        'nom_normalise' => 'lampe led',
        'created_at' => $maintenant,
        'updated_at' => $maintenant,
    ]);

    for ($i = 1; $i <= BIB_NOMBRE_TRANSCRIPTIONS; $i++) {
        $identifiant = DB::table('transcriptions')->insertGetId([
            'user_id' => $utilisateur->getKey(),
            'dossier_id' => $dossiers[$i % 5],
            'source' => 'youtube',
            'url_origine' => "https://www.youtube.com/watch?v=bib{$i}",
            'id_video' => 'bib'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            'titre' => "Transcription {$i}",
            'chaine' => 'Chaîne '.($i % 7),
            'duree_secondes' => 600,
            'statut' => 'termine',
            'nombre_mots' => 100,
            'cout_estime_usd' => 0.0367,
            'est_apercu' => false,
            'created_at' => $maintenant->copy()->subMinutes($i),
            'updated_at' => $maintenant,
        ]);

        if ($i % 2 === 0) {
            DB::table('etiquette_transcription')->insert([
                'transcription_id' => $identifiant,
                'etiquette_id' => $etiquette,
            ]);
        }

        if ($i % 5 === 0) {
            DB::table('etiquette_transcription')->insert([
                'transcription_id' => $identifiant,
                'etiquette_id' => $produit,
            ]);
        }
    }

    return ['dossiers' => $dossiers, 'etiquette' => $etiquette, 'produit' => $produit];
}

it('filtre une bibliothèque de 500 transcriptions en moins d’une seconde, avec des filtres combinés', function () {
    $utilisateur = bibPro();
    ['etiquette' => $etiquette, 'produit' => $produit] = bibRemplirBibliotheque($utilisateur);

    $debut = microtime(true);

    $reponse = $this->actingAs($utilisateur)
        ->get(route('historique', [
            'etiquettes' => [$etiquette],
            'produit' => $produit,
            'plateforme' => 'youtube',
        ]))
        ->assertOk();

    $duree = microtime(true) - $debut;

    // Étiquette niche (indices pairs) et produit (indices multiples de 5) : les multiples de 10, soit 50.
    expect($reponse->viewData('transcriptions')->total())->toBe(50);
    expect($duree)->toBeLessThan(1.0);
});

it('affiche la colonne latérale et la liste complète d’une bibliothèque de 500 transcriptions en moins d’une seconde', function () {
    $utilisateur = bibPro();
    bibRemplirBibliotheque($utilisateur);

    $debut = microtime(true);

    $this->actingAs($utilisateur)->get(route('historique'))->assertOk();

    expect(microtime(true) - $debut)->toBeLessThan(1.0);
});

it('limite la recherche à la vue filtrée quand la portée est « filtre »', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');

    $dedans = bibTranscription($utilisateur, ['titre' => 'Titre dedans', 'dossier' => $dossier]);
    $dehors = bibTranscription($utilisateur, ['titre' => 'Titre dehors']);

    foreach ([$dedans, $dehors] as $transcription) {
        TranscriptionParagraph::factory()->for($transcription)->create([
            'position' => 1,
            'debut_ms' => 0,
            'texte' => 'une lampe solaire pour le jardin',
            'config_recherche' => 'french',
        ]);
    }

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lampe', 'dossier' => $dossier->getKey(), 'portee' => 'filtre']))
        ->assertOk()
        ->assertSee('Titre dedans')
        ->assertDontSee('Titre dehors');

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lampe', 'dossier' => $dossier->getKey(), 'portee' => 'tout']))
        ->assertOk()
        ->assertSee('Titre dedans')
        ->assertSee('Titre dehors');
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

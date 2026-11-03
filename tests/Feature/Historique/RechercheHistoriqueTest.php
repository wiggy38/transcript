<?php

use App\Enums\Plan;
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

/** Compte Pro (le drapeau de plan est activé pour le test). */
function utilisateurPro(): User
{
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    app()->setLocale('fr');

    return $utilisateur;
}

/** Transcription de la langue donnée, avec ses paragraphes (début en ms, texte). */
function transcriptionAvecPassages(User $utilisateur, string $titre, ?string $langue, array $passages): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create([
        'titre' => $titre,
        'langue_detectee' => $langue,
    ]);

    foreach (array_values($passages) as $index => [$debut, $texte]) {
        TranscriptionParagraph::factory()->for($transcription)->create([
            'position' => $index + 1,
            'debut_ms' => $debut,
            'fin_ms' => $debut + 10_000,
            'texte' => $texte,
            'config_recherche' => ConfigurationRecherche::pourLangue($langue),
        ]);
    }

    return $transcription;
}

// Critère F5 (recherche) : extrait surligné et lien vers l'horodatage trouvé.
it('renvoie un extrait surligné et un lien qui ouvre la transcription à l’horodatage trouvé', function () {
    $utilisateur = utilisateurPro();
    $transcription = transcriptionAvecPassages($utilisateur, 'Atelier poterie', 'fr', [
        [0, 'Bienvenue dans l’atelier.'],
        [125_000, 'Le lancement du produit commence demain, avec une offre limitée.'],
    ]);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertOk()
        ->assertSee('<mark', false)
        ->assertSee('Atelier poterie')
        ->assertSee('02:05')
        ->assertSee(route('transcriptions.show', ['transcription' => $transcription, 't' => 125_000]), false);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

// L'extrait est échappé avant le surlignage : seules nos balises <mark> sont du HTML.
it('échappe le texte transcrit : seul le surlignage produit du HTML dans l’extrait', function () {
    $surligner = new ReflectionMethod(RechercheTranscriptions::class, 'surligner');

    $html = $surligner->invoke(
        new RechercheTranscriptions,
        "<img src=x onerror=alert(1)> le \x02lancement\x03 & fin",
    );

    expect($html)
        ->toContain('&lt;img src=x onerror=alert(1)&gt;')
        ->toContain('&amp; fin')
        ->toContain('<mark class=')
        ->not->toContain('<img');
});

// Chaque langue a sa configuration : « french » racinise « écoles » et « école ».
it('applique la configuration de langue de chaque transcription', function () {
    $utilisateur = utilisateurPro();

    $enFrancais = transcriptionAvecPassages($utilisateur, 'Cours en français', 'fr', [
        [5_000, 'Les écoles ouvrent en septembre.'],
    ]);
    transcriptionAvecPassages($utilisateur, 'Sans langue détectée', null, [
        [7_000, 'Les écoles ouvrent aussi.'],
    ]);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'école']))
        ->assertSee('Cours en français')
        ->assertDontSee('Sans langue détectée');
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

it('ne cherche jamais dans les transcriptions d’un autre compte', function () {
    $utilisateur = utilisateurPro();
    $autre = User::factory()->create(['locale' => 'fr']);

    transcriptionAvecPassages($autre, 'Transcription d’un autre', 'fr', [[0, 'secret du lancement']]);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertOk()
        ->assertDontSee('Transcription d’un autre')
        ->assertSee(trans_choice('historique.recherche.resultats', 0, ['terme' => 'lancement', 'nombre' => 0]), false);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

it('ne lance aucune recherche au plan Gratuit', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);
    app()->setLocale('fr');

    transcriptionAvecPassages($utilisateur, 'Vidéo gratuite', 'fr', [[0, 'le lancement du produit']]);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertOk()
        ->assertSee(__('historique.recherche.gratuit'))
        ->assertDontSee('<mark', false);
});

it('ouvre la transcription à la position demandée par le lien de recherche', function () {
    $utilisateur = utilisateurPro();
    $transcription = transcriptionAvecPassages($utilisateur, 'Lecture ciblée', 'fr', [
        [0, 'Début.'],
        [125_000, 'Le passage recherché.'],
    ]);
    $transcription->update(['duree_secondes' => 600]);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.show', ['transcription' => $transcription, 't' => 125_000]))
        ->assertOk()
        // Dans l'attribut x-data, le JSON est échappé : chaque guillemet devient ".
        ->assertSee('positionInitiale\\u0022:125000', false);
});

it('utilise l’index partiel de la langue pour la recherche', function () {
    $requete = TranscriptionParagraph::query()->recherche('ecole', 'french');
    $sql = $requete->toSql();
    $bindings = $requete->getBindings();

    DB::statement('SET enable_seqscan = off');

    try {
        $plan = collect(DB::select('EXPLAIN '.$sql, $bindings))
            ->pluck('QUERY PLAN')
            ->implode("\n");
    } finally {
        DB::statement('SET enable_seqscan = on');
    }

    expect($plan)->toContain('transcription_paragraphs_recherche_french');
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

it('refuse une configuration de recherche absente de la liste fermée', function () {
    TranscriptionParagraph::query()->recherche('x', 'pg_catalog; DROP TABLE users');
})->throws(InvalidArgumentException::class)->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

<?php

use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// La recherche plein texte utilise tsvector : ces tests sont ignorés sur SQLite.
// Pour les exécuter, pointer phpunit vers une base PostgreSQL de test (voxia_test).
uses(RefreshDatabase::class);

$pasPostgres = fn () => DB::getDriverName() !== 'pgsql';

test('la recherche renvoie le paragraphe qui contient le terme, avec son horodatage', function () {
    $transcription = Transcription::factory()->create();

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 60000,
        'texte' => 'Bienvenue dans la vidéo.',
    ]);
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 2,
        'debut_ms' => 120000,
        'fin_ms' => 180000,
        'texte' => 'Le lancement du produit commence demain.',
    ]);

    $resultats = TranscriptionParagraph::query()->recherche('lancement')->get();

    expect($resultats)->toHaveCount(1)
        ->and($resultats->first()->debut_ms)->toBe(120000)
        ->and($resultats->first()->transcription_id)->toBe($transcription->id);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

test('la recherche ne renvoie rien pour un terme absent', function () {
    $transcription = Transcription::factory()->create();

    TranscriptionParagraph::factory()->for($transcription)->create(['texte' => 'Une vidéo sur le café.']);

    expect(TranscriptionParagraph::query()->recherche('chocolat')->count())->toBe(0);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

test('une transcription supprimée disparaît de la recherche', function () {
    $transcription = Transcription::factory()->create();

    TranscriptionParagraph::factory()->for($transcription)->create(['texte' => 'Point clé du script.']);

    expect(TranscriptionParagraph::query()->recherche('script')->count())->toBe(1);

    $transcription->delete();

    expect(TranscriptionParagraph::query()->recherche('script')->count())->toBe(0);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

test('la recherche utilise l\'index GIN et non un parcours complet de la table', function () {
    $transcription = Transcription::factory()->create();
    TranscriptionParagraph::factory()->for($transcription)->create(['texte' => 'Offre limitée ce week-end.']);

    $requete = TranscriptionParagraph::query()->recherche('offre');
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

    expect($plan)->toContain('transcription_paragraphs_recherche_index');
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

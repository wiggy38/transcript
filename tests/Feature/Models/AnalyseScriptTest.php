<?php

use App\Enums\StatutAnalyseScript;
use App\Enums\TypePartieScript;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use App\Models\Transcription;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('une analyse porte les cinq parties dans l\'ordre de la vidéo', function () {
    $analyse = ScriptAnalysis::factory()->termine()->create();

    $types = [
        TypePartieScript::Accroche,
        TypePartieScript::Probleme,
        TypePartieScript::Preuve,
        TypePartieScript::Offre,
        TypePartieScript::AppelAction,
    ];

    // Créées à l'envers pour prouver que l'ordre vient de la position.
    foreach (array_reverse($types, true) as $position => $type) {
        ScriptPart::factory()->for($analyse, 'scriptAnalysis')->pour($type, $position + 1)->create([
            'debut_ms' => $position * 3000,
            'fin_ms' => ($position + 1) * 3000,
        ]);
    }

    $parties = $analyse->load('parts')->parts;

    expect($parties->pluck('type')->all())->toBe($types)
        ->and($parties->pluck('debut_ms')->all())->toBe([0, 3000, 6000, 9000, 12000])
        ->and($parties->every(fn (ScriptPart $partie) => $partie->detectee))->toBeTrue()
        ->and($parties->every(fn (ScriptPart $partie) => $partie->debut_ms < $partie->fin_ms))->toBeTrue();
});

test('une partie absente est marquée non détectée, sans bornes ni texte', function () {
    $analyse = ScriptAnalysis::factory()->create();

    $partie = ScriptPart::factory()
        ->for($analyse, 'scriptAnalysis')
        ->pour(TypePartieScript::Preuve, 3)
        ->nonDetectee()
        ->create();

    $relue = ScriptPart::find($partie->id);

    expect($relue->detectee)->toBeFalse()
        ->and($relue->debut_ms)->toBeNull()
        ->and($relue->fin_ms)->toBeNull()
        ->and($relue->texte)->toBeNull();
});

test('un même type de partie ne peut apparaître qu\'une fois par analyse', function () {
    $analyse = ScriptAnalysis::factory()->create();

    ScriptPart::factory()->for($analyse, 'scriptAnalysis')->pour(TypePartieScript::Offre, 4)->create();

    // DB::transaction ouvre un savepoint : sous PostgreSQL, l'erreur n'abandonne pas le test.
    expect(fn () => DB::transaction(fn () => ScriptPart::factory()
        ->for($analyse, 'scriptAnalysis')
        ->pour(TypePartieScript::Offre, 5)
        ->create()))
        ->toThrow(QueryException::class);
});

test('une transcription n\'a qu\'une seule analyse de script', function () {
    $transcription = Transcription::factory()->create();

    ScriptAnalysis::factory()->for($transcription)->create();

    expect(fn () => DB::transaction(fn () => ScriptAnalysis::factory()->for($transcription)->create()))
        ->toThrow(QueryException::class);
});

test('l\'analyse est rattachée à sa transcription et démarre en attente', function () {
    $transcription = Transcription::factory()->create();

    ScriptAnalysis::factory()->for($transcription)->create();

    $analyse = $transcription->load('scriptAnalysis')->scriptAnalysis;

    expect($analyse->statut)->toBe(StatutAnalyseScript::EnAttente)
        ->and($analyse->transcription->is($transcription))->toBeTrue();
});

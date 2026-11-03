<?php

use App\Enums\EtapeTraitement;
use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use App\Enums\TypePartieScript;
use App\Models\ProcessingLog;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\TranscriptionWord;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('les statuts et motifs d\'échec sont stockés en français et relus sous forme d\'enum', function () {
    $transcription = Transcription::factory()->echec(MotifEchec::LiveEnCours)->create();

    expect(DB::table('transcriptions')->where('id', $transcription->id)->value('statut'))->toBe('echec')
        ->and(DB::table('transcriptions')->where('id', $transcription->id)->value('motif_echec'))->toBe('live_en_cours');

    $relue = Transcription::find($transcription->id);

    expect($relue->source)->toBe(SourceTranscription::Youtube)
        ->and($relue->statut)->toBe(StatutTranscription::Echec)
        ->and($relue->motif_echec)->toBe(MotifEchec::LiveEnCours);
});

test('un même lien ne peut pas être enregistré deux fois pour le même utilisateur', function () {
    $utilisateur = User::factory()->create();
    Transcription::factory()->for($utilisateur)->create(['id_video' => 'dQw4w9WgXcQ']);

    // DB::transaction ouvre un savepoint : sous PostgreSQL, l'erreur n'abandonne pas le test.
    expect(fn () => DB::transaction(fn () => Transcription::factory()
        ->for($utilisateur)
        ->create(['id_video' => 'dQw4w9WgXcQ'])))
        ->toThrow(QueryException::class);

    // Un autre utilisateur peut transcrire la même vidéo.
    $autre = Transcription::factory()->create(['id_video' => 'dQw4w9WgXcQ']);

    expect($autre->user_id)->not->toBe($utilisateur->id);
});

test('plusieurs fichiers importés peuvent coexister sans identifiant de vidéo', function () {
    $utilisateur = User::factory()->create();

    Transcription::factory()->fichier()->for($utilisateur)->count(3)->create();

    expect(Transcription::where('user_id', $utilisateur->id)->count())->toBe(3);
});

test('les mots et les paragraphes sortent dans l\'ordre de la vidéo, quel que soit l\'ordre d\'écriture', function () {
    $transcription = Transcription::factory()->create();

    TranscriptionWord::factory()->for($transcription)->create(['position' => 2, 'texte' => 'deux', 'debut_ms' => 1000, 'fin_ms' => 1300]);
    TranscriptionWord::factory()->for($transcription)->create(['position' => 1, 'texte' => 'un', 'debut_ms' => 0, 'fin_ms' => 900]);

    TranscriptionParagraph::factory()->for($transcription)->create(['position' => 2, 'texte' => 'Deuxième.', 'debut_ms' => 60000, 'fin_ms' => 90000]);
    TranscriptionParagraph::factory()->for($transcription)->create(['position' => 1, 'texte' => 'Premier.', 'debut_ms' => 0, 'fin_ms' => 60000]);

    $transcription->load(['words', 'paragraphs']);

    expect($transcription->words->pluck('texte')->all())->toBe(['un', 'deux'])
        ->and($transcription->words->first()->debut_ms)->toBe(0)
        ->and($transcription->paragraphs->pluck('texte')->all())->toBe(['Premier.', 'Deuxième.']);
});

test('deux mots ne peuvent pas partager la même position dans une transcription', function () {
    $transcription = Transcription::factory()->create();

    TranscriptionWord::factory()->for($transcription)->create(['position' => 1]);

    expect(fn () => DB::transaction(fn () => TranscriptionWord::factory()
        ->for($transcription)
        ->create(['position' => 1])))
        ->toThrow(QueryException::class);
});

test('supprimer un compte supprime toutes ses transcriptions et leurs données', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->termine()->create();

    TranscriptionWord::factory()->for($transcription)->create();
    TranscriptionParagraph::factory()->for($transcription)->create();
    ProcessingLog::factory()->for($transcription)->create();
    $analyse = ScriptAnalysis::factory()->for($transcription)->create();
    ScriptPart::factory()->for($analyse, 'scriptAnalysis')->pour(TypePartieScript::Accroche, 1)->create();
    UsageRecord::factory()->for($utilisateur)->for($transcription)->create();

    $utilisateur->delete();

    expect(Transcription::count())->toBe(0)
        ->and(TranscriptionWord::count())->toBe(0)
        ->and(TranscriptionParagraph::count())->toBe(0)
        ->and(ProcessingLog::count())->toBe(0)
        ->and(ScriptAnalysis::count())->toBe(0)
        ->and(ScriptPart::count())->toBe(0)
        ->and(UsageRecord::count())->toBe(0);
});

test('supprimer une transcription efface ses mots, paragraphes et analyse, mais garde le quota consommé', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->termine()->create();

    TranscriptionWord::factory()->for($transcription)->create();
    TranscriptionParagraph::factory()->for($transcription)->create();
    ProcessingLog::factory()->for($transcription)->create();
    $analyse = ScriptAnalysis::factory()->for($transcription)->create();
    ScriptPart::factory()->for($analyse, 'scriptAnalysis')->pour(TypePartieScript::Accroche, 1)->create();
    UsageRecord::factory()->for($utilisateur)->for($transcription)->create();

    $transcription->delete();

    expect(TranscriptionWord::count())->toBe(0)
        ->and(TranscriptionParagraph::count())->toBe(0)
        ->and(ProcessingLog::count())->toBe(0)
        ->and(ScriptAnalysis::count())->toBe(0)
        ->and(ScriptPart::count())->toBe(0);

    $consommation = UsageRecord::sole();

    expect($consommation->transcription_id)->toBeNull()
        ->and($consommation->quantite)->toBe(1);
});

test('chaque étape de traitement est journalisée avec sa durée, son succès et son message', function () {
    $transcription = Transcription::factory()->create();

    ProcessingLog::factory()->for($transcription)->create([
        'etape' => EtapeTraitement::Metadonnees,
        'duree_ms' => 800,
    ]);
    ProcessingLog::factory()->for($transcription)->echec('Vidéo privée.')->create([
        'etape' => EtapeTraitement::Telechargement,
    ]);

    $journaux = $transcription->processingLogs()->get();

    expect($journaux->pluck('etape')->all())->toBe([EtapeTraitement::Metadonnees, EtapeTraitement::Telechargement])
        ->and($journaux[0]->duree_ms)->toBe(800)
        ->and($journaux[0]->succes)->toBeTrue()
        ->and($journaux[1]->succes)->toBeFalse()
        ->and($journaux[1]->message)->toBe('Vidéo privée.');
});

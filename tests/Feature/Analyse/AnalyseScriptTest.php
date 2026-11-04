<?php

require_once __DIR__.'/outils.php';

use App\Enums\EtapeTraitement;
use App\Enums\StatutAnalyseScript;
use App\Enums\StatutTranscription;
use App\Enums\TypePartieScript;
use App\Enums\UniteConsommation;
use App\Exceptions\AnalyseRefusee;
use App\Exceptions\ErreurAnalyse;
use App\Jobs\AnalyserScript;
use App\Models\ProcessingLog;
use App\Models\ScriptAnalysis;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Analyse\LancerAnalyseScript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/** Plages qui se chevauchent : la problème commence dans le mot où l'accroche finit. */
function plagesChevauchees(): array
{
    $plages = analysePlagesVraies();
    $plages['probleme'] = [3000, 5900];

    return $plages;
}

// Critère F7 : les cinq parties, dans l'ordre de la vidéo, avec leur début, leur fin et leur texte.
it('découpe la pub en cinq parties dans l’ordre de la vidéo', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()->repondre(analyseReponse());

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $analyse = ScriptAnalysis::where('transcription_id', $transcription->getKey())->firstOrFail();
    $parties = $analyse->parts;

    expect($analyse->statut)->toBe(StatutAnalyseScript::Termine)
        ->and($parties->pluck('type')->all())->toBe(TypePartieScript::cases())
        ->and($parties->pluck('position')->all())->toBe([1, 2, 3, 4, 5])
        ->and($parties->pluck('texte')->all())->toBe(array_values(analyseTextesAttendus()))
        ->and($parties[0]->debut_ms)->toBe(0)
        ->and($parties[0]->fin_ms)->toBe(3900);
});

// Critère F7 : une partie absente de la pub est marquée « non détectée », sans bornes ni texte.
it('marque une partie absente comme non détectée, sans bornes ni texte', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    $plages = analysePlagesVraies();
    $plages['offre'] = null;
    analyseFaux()->repondre(analyseReponse($plages));

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $offre = ScriptAnalysis::where('transcription_id', $transcription->getKey())->firstOrFail()
        ->parts->firstWhere('type', TypePartieScript::Offre);

    expect($offre->detectee)->toBeFalse()
        ->and($offre->debut_ms)->toBeNull()
        ->and($offre->fin_ms)->toBeNull()
        ->and($offre->texte)->toBeNull();
});

// Critère F7 : les parties ne se chevauchent pas. Deux réponses invalides : échec propre, aucun crédit.
it('échoue proprement après une relance, sans crédit ni partie enregistrée', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()->repondre(analyseReponse(plagesChevauchees()))->repondre(analyseReponse(plagesChevauchees()));

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $analyse = ScriptAnalysis::where('transcription_id', $transcription->getKey())->firstOrFail();

    expect($analyse->statut)->toBe(StatutAnalyseScript::Echec)
        ->and($analyse->date_echec)->not->toBeNull()
        ->and($analyse->parts)->toHaveCount(0)
        ->and(analyseFaux()->appels)->toHaveCount(2)
        ->and(UsageRecord::count())->toBe(0);
});

// Critère F7 : une réponse invalide est relancée une fois ; la seconde réponse valide réussit.
it('relance une réponse invalide une fois, puis réussit', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()
        ->echouer(new ErreurAnalyse('réponse illisible'))
        ->repondre(analyseReponse());

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $analyse = ScriptAnalysis::where('transcription_id', $transcription->getKey())->firstOrFail();

    expect($analyse->statut)->toBe(StatutAnalyseScript::Termine)
        ->and(analyseFaux()->appels)->toHaveCount(2)
        ->and($analyse->parts)->toHaveCount(5);
});

// Critère F7 : une analyse réussie consomme 1 crédit IA, et une seule.
it('consomme un crédit IA par analyse réussie', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()->repondre(analyseReponse());

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $credit = UsageRecord::sole();

    expect($credit->unite)->toBe(UniteConsommation::CreditIa)
        ->and($credit->quantite)->toBe(1)
        ->and($credit->user_id)->toBe($utilisateur->getKey())
        ->and($credit->transcription_id)->toBe($transcription->getKey());
});

// Critère F7 : le coût réel de chaque appel, y compris un appel rejeté, va dans processing_logs.
it('journalise le coût réel, y compris celui des appels rejetés', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()
        ->repondre(analyseReponse(plagesChevauchees(), 0.001))
        ->repondre(analyseReponse(plagesChevauchees(), 0.001));

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $journal = ProcessingLog::where('transcription_id', $transcription->getKey())
        ->where('etape', EtapeTraitement::AnalyseScript)
        ->sole();

    expect($journal->succes)->toBeFalse()
        ->and((float) $journal->cout_usd)->toBe(0.002);
});

// Critère F7 : une analyse réussie journalise aussi son coût.
it('journalise le succès avec le coût de l’appel', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()->repondre(analyseReponse(null, 0.0123));

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    $journal = ProcessingLog::where('transcription_id', $transcription->getKey())->sole();

    expect($journal->succes)->toBeTrue()
        ->and((float) $journal->cout_usd)->toBe(0.0123);
});

// Critère F7 : une vidéo de plus de 3 minutes est refusée côté serveur, sans aucun appel.
it('refuse une vidéo plus longue que la limite, sans appel', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur, ['duree_secondes' => 181]);

    expect(fn () => app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription))
        ->toThrow(AnalyseRefusee::class);

    expect(analyseFaux()->appels)->toHaveCount(0)
        ->and(ScriptAnalysis::count())->toBe(0);
});

it('accepte une vidéo de exactement 3 minutes', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur, ['duree_secondes' => 180]);
    analyseFaux()->repondre(analyseReponse());

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    expect(ScriptAnalysis::where('transcription_id', $transcription->getKey())->value('statut'))
        ->toBe(StatutAnalyseScript::Termine);
});

// Une analyse terminée n'est jamais relancée : un second crédit serait décompté à tort.
it('ne relance pas une analyse déjà terminée', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur);
    analyseFaux()->repondre(analyseReponse());

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);
    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription->refresh());

    expect(analyseFaux()->appels)->toHaveCount(1)
        ->and(UsageRecord::count())->toBe(1);
});

// Une transcription en cours de traitement ne peut pas être analysée.
it('refuse une transcription qui n’est pas terminée', function () {
    $utilisateur = analyseUtilisateurPro();
    $transcription = transcriptionAnalysable($utilisateur, ['statut' => StatutTranscription::Transcription]);

    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);
})->throws(AnalyseRefusee::class);

// Une analyse passe par la file prioritaire pour le Pro (cahier §8), la file standard sinon.
it('met l’analyse dans la file haute pour le Pro, et refuse le Gratuit', function () {
    Queue::fake();

    $pro = analyseUtilisateurPro();
    app(LancerAnalyseScript::class)->lancer($pro, transcriptionAnalysable($pro));

    // Le Gratuit n'a pas de crédit IA : son lancement est refusé, rien n'est mis en file.
    $gratuit = User::factory()->create();

    expect(fn () => app(LancerAnalyseScript::class)->lancer($gratuit, transcriptionAnalysable($gratuit)))
        ->toThrow(AnalyseRefusee::class);

    Queue::assertPushedOn('high', AnalyserScript::class, fn ($job) => true);
    Queue::assertPushed(AnalyserScript::class, 1);
});

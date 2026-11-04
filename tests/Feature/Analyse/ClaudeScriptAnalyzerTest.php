<?php

require_once __DIR__.'/outils.php';

use App\Data\EntreeAnalyse;
use App\Data\MotTranscrit;
use App\Exceptions\ErreurAnalyse;
use App\Services\Analyse\ClaudeScriptAnalyzer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/** Entrée de test : les mots de la pub de test, sans paragraphes (l'adaptateur ne les lit que pour le contexte). */
function claudeEntree(): EntreeAnalyse
{
    $mots = [];

    foreach (analyseMots() as $index => $texte) {
        $mots[] = new MotTranscrit($texte, $index * 1000, $index * 1000 + 900);
    }

    return new EntreeAnalyse(paragraphes: [], mots: $mots, dureeMs: 10_000);
}

/** Corps d'une réponse de l'API Messages qui porte le JSON donné. */
function claudeReponse(array $donnees, string $stop = 'end_turn'): array
{
    return [
        'model' => 'claude-haiku-5-5',
        'stop_reason' => $stop,
        'content' => [['type' => 'text', 'text' => json_encode($donnees, JSON_UNESCAPED_UNICODE)]],
        'usage' => ['input_tokens' => 1000, 'output_tokens' => 200],
    ];
}

/** Réponse JSON du modèle : numéros de mots pour chaque partie. */
function claudeDonnees(array $numeros): array
{
    $donnees = [];

    foreach (['accroche', 'probleme', 'preuve', 'offre', 'appel_action'] as $cle) {
        $plage = $numeros[$cle] ?? null;

        $donnees[$cle] = $plage === null
            ? ['detectee' => false, 'premier_mot' => null, 'dernier_mot' => null]
            : ['detectee' => true, 'premier_mot' => $plage[0], 'dernier_mot' => $plage[1]];
    }

    return $donnees;
}

beforeEach(function () {
    config([
        'services.anthropic.key' => 'cle-de-test',
        'services.anthropic.model' => 'claude-haiku-5-5',
        'services.anthropic_messages.base_url' => 'https://api.anthropic.com',
        'services.anthropic_messages.version' => '2026-10-21',
    ]);
});

// Critère F7 (fournisseur) : sortie structurée par schéma, modèle configuré, aucune température envoyée,
// en-têtes de version et de clé. Le schéma impose les cinq parties.
it('envoie le schéma en sortie structurée, avec la version de l’API figée', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(claudeReponse(claudeDonnees([
        'accroche' => [0, 3],
        'probleme' => [4, 5],
        'preuve' => [6, 7],
        'offre' => [8, 8],
        'appel_action' => [9, 9],
    ])), 200)]);

    (new ClaudeScriptAnalyzer)->analyser(claudeEntree());

    Http::assertSent(function (Request $requete) {
        $format = $requete['output_config']['format'];

        return $requete->url() === 'https://api.anthropic.com/v1/messages'
            && $requete->hasHeader('x-api-key', 'cle-de-test')
            && $requete->hasHeader('anthropic-version', '2026-10-21')
            && $requete['model'] === 'claude-haiku-5-5'
            && $format['type'] === 'json_schema'
            && $format['schema']['required'] === ['accroche', 'probleme', 'preuve', 'offre', 'appel_action']
            && $format['schema']['additionalProperties'] === false
            && ! array_key_exists('temperature', $requete->data());
    });
});

// Critère F7 (fournisseur) : les numéros de mots donnent les bornes réelles ; le modèle n'écrit aucun horodatage.
it('convertit les numéros de mots en bornes réelles et le coût en dollars', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(claudeReponse(claudeDonnees([
        'accroche' => [0, 3],
        'probleme' => [4, 5],
        'preuve' => [6, 7],
        'offre' => null,
        'appel_action' => [9, 9],
    ])), 200)]);

    $resultat = (new ClaudeScriptAnalyzer)->analyser(claudeEntree());

    expect($resultat->parties[0]->debutMs)->toBe(0)
        ->and($resultat->parties[0]->finMs)->toBe(3900)
        ->and($resultat->parties[3]->detectee)->toBeFalse()
        ->and($resultat->parties[4]->debutMs)->toBe(9000)
        ->and($resultat->tokensEntree)->toBe(1000)
        ->and($resultat->tokensSortie)->toBe(200)
        // 1000 × 0,10 $/MTok + 200 × 0,50 $/MTok
        ->and($resultat->coutUsd)->toBe(0.0002)
        ->and($resultat->fournisseur)->toBe('anthropic');
});

// Critère F7 (fournisseur) : un refus ou une troncature est une réponse invalide, dont le coût reste compté.
it('signale un refus du modèle, coût compris', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(claudeReponse(claudeDonnees([]), 'refusal'), 200)]);

    try {
        (new ClaudeScriptAnalyzer)->analyser(claudeEntree());
        $this->fail('Une exception était attendue.');
    } catch (ErreurAnalyse $erreur) {
        expect($erreur->getMessage())->toBe('refus du modèle')
            ->and($erreur->coutUsd)->toBe(0.0002);
    }
});

it('signale une réponse tronquée', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(claudeReponse(claudeDonnees([]), 'max_tokens'), 200)]);

    (new ClaudeScriptAnalyzer)->analyser(claudeEntree());
})->throws(ErreurAnalyse::class, 'réponse tronquée');

// Critère F7 (fournisseur) : un numéro de mot hors du texte rend la réponse invalide (relance côté service).
it('rejette un numéro de mot hors du texte', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(claudeReponse(claudeDonnees([
        'accroche' => [0, 42],
    ])), 200)]);

    (new ClaudeScriptAnalyzer)->analyser(claudeEntree());
})->throws(ErreurAnalyse::class, 'réponse invalide');

it('signale une erreur HTTP du fournisseur', function () {
    Http::fake(['api.anthropic.com/*' => Http::response(['type' => 'error'], 429)]);

    (new ClaudeScriptAnalyzer)->analyser(claudeEntree());
})->throws(ErreurAnalyse::class, 'erreur HTTP 429');

// Aucun appel réseau sans clé : l'erreur est immédiate.
it('sans clé d’API, échoue sans aucun appel', function () {
    config(['services.anthropic.key' => '']);
    Http::fake();

    try {
        (new ClaudeScriptAnalyzer)->analyser(claudeEntree());
        $this->fail('Une exception était attendue.');
    } catch (ErreurAnalyse $erreur) {
        expect($erreur->getMessage())->toBe('clé absente');
    }

    Http::assertNothingSent();
});

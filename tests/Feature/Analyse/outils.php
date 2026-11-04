<?php

// Outils partagés des tests d'analyse de script (F7). Chargés par require_once depuis chaque fichier de test.

use App\Contracts\ScriptAnalyzer;
use App\Data\PartieAnalysee;
use App\Data\ResultatFournisseur;
use App\Enums\Plan;
use App\Enums\TypePartieScript;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\TranscriptionWord;
use App\Models\User;
use App\Services\Analyse\FakeScriptAnalyzer;

/** Mots de la pub de test : une seconde chacun, de 0 à 9,9 s. Les textes sont ceux que le service reconstruit. */
function analyseMots(): array
{
    return ['Tu', 'te', 'réveilles', '?', 'Ta', 'peau', 'tire', 'Essaie', 'ça', 'vite.'];
}

/** Plages « vraies » des cinq parties, en millisecondes (début, fin). Absente = null. */
function analysePlagesVraies(): array
{
    return [
        'accroche' => [0, 3900],
        'probleme' => [4000, 5900],
        'preuve' => [6000, 7900],
        'offre' => [8000, 8900],
        'appel_action' => [9000, 9900],
    ];
}

/** Textes attendus pour analysePlagesVraies(), dans l'ordre des parties. */
function analyseTextesAttendus(): array
{
    return [
        'accroche' => 'Tu te réveilles?',
        'probleme' => 'Ta peau',
        'preuve' => 'tire Essaie',
        'offre' => 'ça',
        'appel_action' => 'vite.',
    ];
}

/** Transcription terminée de 10 s, avec ses mots et un paragraphe. */
function transcriptionAnalysable(User $utilisateur, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)
        ->create(array_merge(['duree_secondes' => 10], $attributs));

    foreach (analyseMots() as $index => $texte) {
        TranscriptionWord::create([
            'transcription_id' => $transcription->getKey(),
            'position' => $index + 1,
            'texte' => $texte,
            'debut_ms' => $index * 1000,
            'fin_ms' => $index * 1000 + 900,
        ]);
    }

    TranscriptionParagraph::create([
        'transcription_id' => $transcription->getKey(),
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 9900,
        'texte' => implode(' ', analyseMots()),
        'config_recherche' => 'simple',
    ]);

    return $transcription;
}

/**
 * Réponse d'un fournisseur pour des plages données (ms). Une partie absente de $plages, ou valant null,
 * est déclarée absente. Le texte n'est pas renseigné : c'est au validateur de le reconstruire.
 *
 * @param  array<string, array{0: int, 1: int}|null>|null  $plages
 */
function analyseReponse(?array $plages = null, float $cout = 0.0002): ResultatFournisseur
{
    $plages ??= analysePlagesVraies();
    $parties = [];

    foreach (TypePartieScript::cases() as $type) {
        $plage = $plages[$type->value] ?? null;

        $parties[] = $plage === null
            ? new PartieAnalysee($type, false)
            : new PartieAnalysee($type, true, $plage[0], $plage[1]);
    }

    return new ResultatFournisseur($parties, 'anthropic', 'claude-haiku-5-5', 1000, 200, $cout);
}

function analyseFaux(): FakeScriptAnalyzer
{
    /** @var FakeScriptAnalyzer $faux */
    $faux = app(ScriptAnalyzer::class);

    return $faux;
}

function analyseUtilisateurPro(): User
{
    $utilisateur = User::factory()->create();
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();

    return $utilisateur;
}

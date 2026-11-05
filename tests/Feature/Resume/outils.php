<?php

// Outils partagés des tests du résumé (V2). Chargés par require_once depuis chaque fichier de test.

use App\Contracts\LanguageModel;
use App\Data\Ia\ReponseModele;
use App\Enums\Plan;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Services\Ia\FauxLanguageModel;

/** Faux modèle lié par Tests\TestCase : les réponses se préparent avec repondre(). */
function resumeFaux(): FauxLanguageModel
{
    /** @var FauxLanguageModel $faux */
    $faux = app(LanguageModel::class);

    return $faux;
}

function resumeUtilisateur(Plan $plan = Plan::Pro): User
{
    $utilisateur = User::factory()->create();
    $utilisateur->forceFill(['plan' => $plan])->save();

    return $utilisateur;
}

/**
 * Transcription terminée avec ses paragraphes. Chaque paragraphe est [début en ms, texte] ; sa position
 * (« Paragraphe N ») est son rang dans la liste, à partir de 1.
 *
 * @param  list<array{0: int, 1: string}>  $paragraphes
 */
function resumeTranscription(User $utilisateur, int $dureeSecondes, array $paragraphes, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)
        ->create(array_merge(['duree_secondes' => $dureeSecondes], $attributs));

    foreach ($paragraphes as $index => [$debut, $texte]) {
        TranscriptionParagraph::create([
            'transcription_id' => $transcription->getKey(),
            'position' => $index + 1,
            'debut_ms' => $debut,
            'fin_ms' => $debut + 1000,
            'texte' => $texte,
            'config_recherche' => 'simple',
        ]);
    }

    return $transcription;
}

/** Paragraphes de la transcription courte (100 s, six paragraphes, sans chiffre), sous la forme [début ms, texte]. */
function resumeParagraphesCourts(): array
{
    return [
        [0, 'Bonjour, je vous présente mon balcon et ses bacs.'],
        [20000, 'Le balcon sud reçoit beaucoup de soleil en été.'],
        [40000, 'Les bacs doivent être légers, avec un bon drainage.'],
        [60000, 'J’arrose au doigt plutôt qu’au calendrier.'],
        [80000, 'La tomate demande un tuteur solide dès la plantation.'],
        [90000, 'Laissez de l’espace entre les salades.'],
    ];
}

/** Transcription courte (100 s) : une sortie valide la décrit. */
function resumeTranscriptionCourte(User $utilisateur, array $attributs = []): Transcription
{
    return resumeTranscription($utilisateur, 100, resumeParagraphesCourts(), $attributs);
}

/**
 * Les 84 paragraphes de la fixture de 28 minutes, sous la forme [début ms, texte].
 *
 * @return list<array{0: int, 1: string}>
 */
function resumeParagraphesFixture(): array
{
    $lignes = file(__DIR__.'/../../fixtures/transcription-fr-28min.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    return array_map(function (string $ligne) {
        preg_match('/^\[(\d{2}):(\d{2})\] (.*)$/u', $ligne, $m);

        return [((int) $m[1] * 60 + (int) $m[2]) * 1000, $m[3]];
    }, $lignes);
}

/** Réponse d'un appel réussi, telle que la rend AnthropicLanguageModel. */
function resumeReponse(array $structure, float $cout = 0.001): ReponseModele
{
    return new ReponseModele(
        texte: null,
        structure: $structure,
        modele: 'claude-haiku-5-5',
        tokensEntree: 1000,
        tokensSortie: 200,
        coutUsd: $cout,
        dureeMs: 10,
    );
}

/** Sortie valide pour resumeTranscriptionCourte() : cinq points, paragraphes 1 à 5, aucun chiffre. */
function resumeSortieValide(): array
{
    return [
        'resume' => 'Le balcon sud reçoit beaucoup de soleil. Les bacs doivent être légers. L’arrosage se fait au doigt. La tomate demande un tuteur.',
        'points_cles' => [
            ['texte' => 'Choisir un balcon bien orienté.', 'paragraphe' => 2],
            ['texte' => 'Préférer des bacs légers.', 'paragraphe' => 3],
            ['texte' => 'Arroser au doigt, pas au calendrier.', 'paragraphe' => 4],
            ['texte' => 'Soutenir la tomate dès la plantation.', 'paragraphe' => 5],
            ['texte' => 'Laisser de l’espace entre les salades.', 'paragraphe' => 6],
        ],
        'chapitres' => [],
    ];
}

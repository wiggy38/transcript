<?php

require_once __DIR__.'/outils.php';

use App\Data\MotTranscrit;
use App\Data\PartieAnalysee;
use App\Enums\TypePartieScript;
use App\Exceptions\ErreurAnalyse;
use App\Services\Analyse\ValidateurAnalyse;

/** Mots de test, en objets, comme les lit le service. */
function validateurMots(): array
{
    $mots = [];

    foreach (analyseMots() as $index => $texte) {
        $mots[] = new MotTranscrit($texte, $index * 1000, $index * 1000 + 900);
    }

    return $mots;
}

/** Parties brutes à partir de plages en ms (null = absente), avec un texte optionnel renvoyé par le fournisseur. */
function validateurParties(array $plages, ?string $texte = null): array
{
    $parties = [];

    foreach (TypePartieScript::cases() as $type) {
        $plage = $plages[$type->value] ?? null;

        $parties[] = $plage === null
            ? new PartieAnalysee($type, false)
            : new PartieAnalysee($type, true, $plage[0], $plage[1], $texte);
    }

    return $parties;
}

// Critère F7 : les parties suivent l'ordre de la vidéo, bornes recalées sur les mots réels, texte rebâti
// à partir des mots (le texte renvoyé par le fournisseur est ignoré).
it('recale les bornes sur les mots réels et reconstruit le texte', function () {
    $plages = analysePlagesVraies();
    // Bornes approximatives : 4100 et 5850 doivent tomber sur le mot 4 (début 4000) et le mot 5 (fin 5900).
    $plages['probleme'] = [4100, 5850];

    $sorties = (new ValidateurAnalyse)->valider(validateurParties($plages, 'texte inventé par le modèle'), validateurMots());

    expect(array_map(fn (PartieAnalysee $p) => $p->type, $sorties))->toBe(TypePartieScript::cases())
        ->and($sorties[1]->debutMs)->toBe(4000)
        ->and($sorties[1]->finMs)->toBe(5900)
        ->and($sorties[1]->texte)->toBe('Ta peau')
        ->and($sorties[0]->texte)->toBe('Tu te réveilles?')
        ->and($sorties[4]->texte)->toBe('vite.');
});

// Critère F7 : les parties ne se chevauchent pas (une partie ne peut pas commencer avant la fin de la précédente).
it('refuse deux parties qui se chevauchent', function () {
    $plages = analysePlagesVraies();
    // La problème commence à 3000 ms, dans le mot 3 que l'accroche occupe encore.
    $plages['probleme'] = [3000, 5900];

    (new ValidateurAnalyse)->valider(validateurParties($plages), validateurMots());
})->throws(ErreurAnalyse::class, 'chevauchement ou désordre : probleme');

// Critère F7 : les parties suivent l'ordre de la vidéo (la preuve ne peut pas précéder le problème).
it('refuse des parties dans le désordre', function () {
    $plages = analysePlagesVraies();
    $plages['accroche'] = [4000, 5900];
    $plages['probleme'] = [0, 3900];

    (new ValidateurAnalyse)->valider(validateurParties($plages), validateurMots());
})->throws(ErreurAnalyse::class);

// Critère F7 : une partie absente est déclarée absente, jamais inventée.
it('garde une partie absente absente, sans bornes ni texte', function () {
    $plages = analysePlagesVraies();
    $plages['offre'] = null;

    $sorties = (new ValidateurAnalyse)->valider(validateurParties($plages), validateurMots());
    $offre = $sorties[3];

    expect($offre->type)->toBe(TypePartieScript::Offre)
        ->and($offre->detectee)->toBeFalse()
        ->and($offre->debutMs)->toBeNull()
        ->and($offre->finMs)->toBeNull()
        ->and($offre->texte)->toBeNull();
});

it('refuse une réponse qui ne donne pas les cinq parties', function () {
    $parties = validateurParties(analysePlagesVraies());
    array_pop($parties);

    (new ValidateurAnalyse)->valider($parties, validateurMots());
})->throws(ErreurAnalyse::class, 'partie absente de la réponse : appel_action');

it('refuse une borne trop éloignée de tout mot transcrit', function () {
    $plages = analysePlagesVraies();
    $plages['preuve'] = [60_000, 62_000];

    (new ValidateurAnalyse)->valider(validateurParties($plages), validateurMots());
})->throws(ErreurAnalyse::class, 'borne hors du texte transcrit : preuve');

it('refuse une partie dont le début est après la fin', function () {
    $plages = analysePlagesVraies();
    $plages['offre'] = [8000, 7000];

    (new ValidateurAnalyse)->valider(validateurParties($plages), validateurMots());
})->throws(ErreurAnalyse::class, 'bornes incohérentes : offre');

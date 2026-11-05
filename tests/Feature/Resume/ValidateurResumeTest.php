<?php

require_once __DIR__.'/outils.php';

use App\Data\Resume\ExigencesResume;
use App\Data\Resume\ParagrapheResume;
use App\Enums\ModeChapitres;
use App\Exceptions\ErreurResume;
use App\Services\Resume\ValidateurResume;

/** Paragraphes de resumeParagraphesCourts(), numérotés comme le fait le service (rang à partir de 1). @return array<int, ParagrapheResume> */
function paragraphesValidation(): array
{
    $paragraphes = [];

    foreach (resumeParagraphesCourts() as $index => [$debut, $texte]) {
        $paragraphes[$index + 1] = new ParagrapheResume($index + 1, $debut, $texte);
    }

    return $paragraphes;
}

function texteTranscriptionCourte(): string
{
    return implode(' ', array_map(fn (array $p) => $p[1], resumeParagraphesCourts()));
}

// Critère F-résumé (fidélité) : un horodatage n'est jamais repris du modèle. Il est lu dans la transcription.
it('lit l’horodatage de chaque point dans la transcription, jamais dans la réponse', function () {
    $structure = resumeSortieValide();
    $contenu = (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(100), texteTranscriptionCourte(), 100);

    expect(array_map(fn ($p) => $p->debutMs, $contenu->points))->toBe([20000, 40000, 60000, 80000, 90000])
        ->and($contenu->chapitres)->toBe([]);
});

// Critère fidélité : un horodatage inventé (numéro de paragraphe inexistant) fait retirer le point.
it('retire un point cité à un paragraphe inexistant', function () {
    $structure = resumeSortieValide();
    $structure['points_cles'][] = ['texte' => 'Un point inventé.', 'paragraphe' => 999];

    $contenu = (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(100), texteTranscriptionCourte(), 100);

    expect($contenu->points)->toHaveCount(5)
        ->and(collect($contenu->points)->pluck('texte')->all())->not->toContain('Un point inventé.');
});

// Critère fidélité : un point cité au-delà de la durée de la vidéo est retiré.
it('retire un point dont le paragraphe commence après la fin de la vidéo', function () {
    $paragraphes = paragraphesValidation();
    $paragraphes[7] = new ParagrapheResume(7, 150000, 'Un passage après la fin.');
    $structure = resumeSortieValide();
    $structure['points_cles'][] = ['texte' => 'Point hors durée.', 'paragraphe' => 7];

    $contenu = (new ValidateurResume)->valider($structure, $paragraphes, ExigencesResume::finales(100), texteTranscriptionCourte(), 100);

    expect(collect($contenu->points)->pluck('texte')->all())->not->toContain('Point hors durée.');
});

// Critère fidélité : si les horodatages inventés font passer sous le minimum, la sortie est rejetée (puis relancée).
it('refuse une sortie dont il ne reste pas assez de points valides', function () {
    $structure = resumeSortieValide();
    $structure['points_cles'] = array_slice($structure['points_cles'], 0, 3);
    $structure['points_cles'][] = ['texte' => 'Inventé.', 'paragraphe' => 999];
    $structure['points_cles'][] = ['texte' => 'Inventé aussi.', 'paragraphe' => 998];

    (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(100), texteTranscriptionCourte(), 100);
})->throws(ErreurResume::class);

// Critère format : le résumé doit compter entre 3 et 5 phrases.
it('refuse un résumé de deux phrases', function () {
    $structure = resumeSortieValide();
    $structure['resume'] = 'Le balcon sud reçoit du soleil. Les bacs doivent être légers.';

    (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(100), texteTranscriptionCourte(), 100);
})->throws(ErreurResume::class, 'phrases_resume');

// Critère fidélité : un chiffre qui ne figure pas dans la transcription est refusé.
it('refuse un chiffre absent de la transcription', function () {
    $structure = resumeSortieValide();
    $structure['resume'] = 'Le balcon sud reçoit 6 heures de soleil. Les bacs doivent être légers. L’arrosage se fait au doigt. La tomate demande un tuteur.';

    (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(100), texteTranscriptionCourte(), 100);
})->throws(ErreurResume::class, 'chiffre_absent');

// Critère fidélité : un chiffre présent dans la transcription est accepté.
it('accepte un chiffre présent dans la transcription', function () {
    $texte = 'Je dépense 45 euros par an. '.texteTranscriptionCourte();
    $paragraphes = paragraphesValidation();
    $paragraphes[1] = new ParagrapheResume(1, 0, 'Je dépense 45 euros par an.');
    $structure = resumeSortieValide();
    $structure['resume'] = 'Le budget est de 45 euros. Les bacs doivent être légers. L’arrosage se fait au doigt. La tomate demande un tuteur.';

    $contenu = (new ValidateurResume)->valider($structure, $paragraphes, ExigencesResume::finales(100), $texte, 100);

    expect($contenu->resume)->toContain('45');
});

// Critère chapitres : une vidéo de vingt minutes ou moins n'a pas de chapitres.
it('ne garde aucun chapitre pour une vidéo de vingt minutes ou moins', function () {
    $structure = resumeSortieValide();
    $structure['chapitres'] = [['titre' => 'Le balcon', 'paragraphe' => 2, 'resume' => 'Le balcon reçoit du soleil. Il est sud.']];

    $contenu = (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(1200), texteTranscriptionCourte(), 100);

    expect($contenu->chapitres)->toBe([]);
});

// Critère chapitres : au-delà de vingt minutes, des chapitres sont requis, avec deux phrases chacun.
it('exige un chapitre de deux phrases au-delà de vingt minutes', function () {
    $structure = resumeSortieValide();
    $structure['chapitres'] = [['titre' => 'Le balcon', 'paragraphe' => 2, 'resume' => 'Le balcon reçoit du soleil. Il est sud. Il est grand.']];

    (new ValidateurResume)->valider($structure, paragraphesValidation(), ExigencesResume::finales(1500), texteTranscriptionCourte(), 1500);
})->throws(ErreurResume::class, 'phrases_chapitre');

it('refuse une vidéo longue sans aucun chapitre', function () {
    (new ValidateurResume)->valider(resumeSortieValide(), paragraphesValidation(), ExigencesResume::finales(1500), texteTranscriptionCourte(), 1500);
})->throws(ErreurResume::class, 'chapitres');

it('règle les chapitres selon la durée : aucun, facultatifs, requis', function () {
    expect(ExigencesResume::finales(1200)->chapitres)->toBe(ModeChapitres::Aucun)
        ->and(ExigencesResume::finales(1201)->chapitres)->toBe(ModeChapitres::Requis)
        ->and(ExigencesResume::morceau(1201)->chapitres)->toBe(ModeChapitres::Facultatifs);
});

<?php

require_once __DIR__.'/outils.php';

use App\Enums\FormatReecriture;
use App\Enums\LongueurReecriture;
use App\Exceptions\ErreurReecriture;
use App\Services\Exports\ExporteurReecriture;
use App\Services\Reecriture\ValidateurReecriture;

/** Texte de la transcription de référence, sans chiffre ni lien. */
function reecritureTexteReference(): string
{
    return implode("\n\n", array_column(resumeParagraphesCourts(), 1));
}

function reecritureValider(FormatReecriture $format, array $structure, string $texte = '', LongueurReecriture $longueur = LongueurReecriture::Moyenne, bool $tronquer = false): array
{
    return app(ValidateurReecriture::class)->valider($format, $structure, $texte !== '' ? $texte : reecritureTexteReference(), $longueur, $tronquer);
}

it('découpe un message trop long à la frontière d’un mot, sans jamais couper un mot', function () {
    $message = str_repeat('Le balcon reçoit du soleil et ', 25); // bien au-delà de 274 caractères
    $validateur = app(ValidateurReecriture::class);

    $morceaux = $validateur->decouper(trim($message), 274);

    expect(count($morceaux))->toBeGreaterThan(1)
        ->and(collect($morceaux)->every(fn (string $m) => mb_strlen($m) <= 274))->toBeTrue()
        ->and(implode(' ', $morceaux))->toBe(trim($message));
});

it('refuse un mot plus long que la limite d’un message : il ne peut pas être découpé sans être tronqué', function () {
    $validateur = app(ValidateurReecriture::class);

    expect(fn () => $validateur->decouper(str_repeat('a', 300), 274))
        ->toThrow(ErreurReecriture::class, 'mot_trop_long');
});

it('un thread découpé reste dans la fourchette de messages, et chaque message numéroté tient en 280 caractères', function () {
    $long = str_repeat('Le balcon reçoit du soleil et les bacs ', 12);
    $structure = ['messages' => [
        ['texte' => 'Accroche du fil.'],
        ['texte' => trim($long)],
        ['texte' => 'Les bacs légers.'],
        ['texte' => 'Arrosage au doigt.'],
        ['texte' => 'Tuteur dès la plantation.'],
    ]];

    $contenu = reecritureValider(FormatReecriture::Thread, $structure);
    $numerotes = app(ExporteurReecriture::class)->numerotes($contenu['messages']);

    expect(count($contenu['messages']))->toBeGreaterThan(5)
        ->and(count($contenu['messages']))->toBeLessThanOrEqual(12)
        ->and(collect($numerotes)->every(fn (string $m) => mb_strlen($m) <= 280))->toBeTrue()
        ->and($numerotes[0])->toStartWith('1/'.count($contenu['messages']).' ');
});

it('refuse un thread hors de la fourchette de messages', function () {
    $structure = ['messages' => array_fill(0, 4, ['texte' => 'Un message sans chiffre.'])];

    expect(fn () => reecritureValider(FormatReecriture::Thread, $structure))
        ->toThrow(ErreurReecriture::class, 'nombre_messages');
});

it('accepte le repère [à compléter] dans la sortie', function () {
    $structure = reecritureNewsletterValide('Réserve ta place sur [à compléter].');

    $contenu = reecritureValider(FormatReecriture::Newsletter, $structure);

    expect($contenu['appel_action'])->toBe('Réserve ta place sur [à compléter].');
});

it('refuse un chiffre qui ne figure pas dans la transcription', function () {
    $structure = reecritureNewsletterValide();
    $structure['apercu'] = 'Trois gestes en 2026.';

    expect(fn () => reecritureValider(FormatReecriture::Newsletter, $structure))
        ->toThrow(ErreurReecriture::class, 'chiffre_absent');
});

it('refuse un lien qui ne figure pas dans la transcription', function () {
    $structure = reecritureNewsletterValide('Va sur https://exemple.invente/offre maintenant.');

    expect(fn () => reecritureValider(FormatReecriture::Newsletter, $structure))
        ->toThrow(ErreurReecriture::class, 'lien_absent');
});

it('accepte un lien présent dans la transcription, même suivi d’une ponctuation', function () {
    $texte = 'Le guide est sur https://voxia.example/guide et il est gratuit.';
    $structure = reecritureNewsletterValide('Lis le guide : https://voxia.example/guide.');

    $contenu = reecritureValider(FormatReecriture::Newsletter, $structure, $texte);

    expect($contenu['appel_action'])->toContain('https://voxia.example/guide');
});

it('refuse un article hors de la fourchette de mots de sa longueur', function () {
    $structure = reecritureArticleValide();
    $structure['conclusion'] = 'Trop court.';

    expect(fn () => reecritureValider(FormatReecriture::Article, $structure, longueur: LongueurReecriture::Courte))
        ->toThrow(ErreurReecriture::class, 'longueur');
});

it('refuse un post LinkedIn trop long, sauf en mode raccourci qui retire des paragraphes entiers', function () {
    $structure = [
        'accroche' => 'Un balcon réussi commence par la lumière.',
        'paragraphes' => [reecritureMots(120), reecritureMots(120), reecritureMots(120)],
    ];

    expect(fn () => reecritureValider(FormatReecriture::LinkedIn, $structure))
        ->toThrow(ErreurReecriture::class, 'trop_long');

    $contenu = reecritureValider(FormatReecriture::LinkedIn, $structure, tronquer: true);

    expect($contenu['paragraphes'])->toHaveCount(1);
});

it('refuse une newsletter dont le corps n’a pas le nombre de paragraphes demandé', function () {
    $structure = reecritureNewsletterValide();
    $structure['corps'] = ['Un seul paragraphe.'];

    expect(fn () => reecritureValider(FormatReecriture::Newsletter, $structure))
        ->toThrow(ErreurReecriture::class, 'corps');
});

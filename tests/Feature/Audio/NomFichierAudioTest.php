<?php

use App\Services\Audio\NomFichierAudio;

// Critère F3 : le nom du fichier ne contient que des caractères valides sous Windows,
// macOS et Linux. Dix titres piégés, plus les cas de longueur et d'accents.

dataset('titres piégés', [
    'caractères interdits' => ['Mon: vidéo / test? <final> | "ok" *', 'Mon vidéo test final ok.mp3'],
    'nom réservé CON' => ['CON', '_CON.mp3'],
    'nom réservé avec extension (nul.txt)' => ['nul.txt', '_nul.txt.mp3'],
    'nom réservé LPT9 en minuscules' => ['lpt9', '_lpt9.mp3'],
    'point final' => ['Titre de la vidéo.', 'Titre de la vidéo.mp3'],
    'uniquement des points' => ['...', 'audio.mp3'],
    'uniquement des espaces et tabulations' => ["  \t  ", 'audio.mp3'],
    'remontée de dossiers' => ['..\\..\\etc/passwd', 'etcpasswd.mp3'],
    'caractères de contrôle' => ["Titre\x00 avec\ttabulation\r\nlignes", 'Titre avec tabulation lignes.mp3'],
    'nom réservé suivi d’un espace' => ['CON ', '_CON.mp3'],
]);

it('construit un nom attendu pour un titre piégé', function (string $titre, string $attendu) {
    expect(NomFichierAudio::depuisTitre($titre))->toBe($attendu);
})->with('titres piégés');

it('donne un nom valide sous Windows, macOS et Linux pour un titre piégé', function (string $titre) {
    $nom = NomFichierAudio::depuisTitre($titre);

    expect($nom)->not->toMatch('/[<>:"\/\\\\|?*\x00-\x1F\x7F]/u')
        ->and($nom)->not->toStartWith('.')
        ->and($nom)->not->toMatch('/[ .]\.mp3$/')
        ->and(preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i', $nom))->toBe(0)
        ->and(strlen($nom))->toBeLessThanOrEqual(200)
        ->and(mb_check_encoding($nom, 'UTF-8'))->toBeTrue();
})->with('titres piégés');

it('conserve les accents en forme composée (NFC)', function () {
    // Entrée décomposée (e + accent combinant, comme sur macOS) : le nom doit être composé.
    expect(NomFichierAudio::depuisTitre("E\u{0301}te\u{0301} a\u{0300} Montre\u{0301}al"))
        ->toBe('Été à Montréal.mp3');
});

it('ne coupe jamais un caractère UTF-8 et respecte la longueur maximale', function () {
    foreach ([str_repeat('é', 300), str_repeat('😀', 100), str_repeat('ab ', 200)] as $titre) {
        $nom = NomFichierAudio::depuisTitre($titre);

        expect(strlen($nom))->toBeLessThanOrEqual(200)
            ->and(mb_check_encoding($nom, 'UTF-8'))->toBeTrue()
            ->and($nom)->toEndWith('.mp3');
    }
});

it('ne laisse pas un nom tronqué devenir un nom réservé', function () {
    // Longueur 8 : il reste 3 octets pour le radical, donc la troncature ne garde que « CON ».
    $nom = NomFichierAudio::depuisTitre('CON'.str_repeat('x', 300), 'mp3', 8);

    expect($nom)->toBe('_CON.mp3')
        ->and(strlen($nom))->toBeLessThanOrEqual(8);
});

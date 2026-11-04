<?php

use App\Data\ContenuTranscription;
use App\Data\MotTranscrit;
use App\Services\Exports\ExporteurSrt;
use App\Services\Exports\ExporteurVtt;
use App\Services\Exports\GenerateurSousTitres;
use Tests\Support\ValidateurSousTitres;

/**
 * Mots horodatés à partir de phrases. La ponctuation finale est un mot à part, comme la renvoie
 * le moteur. Un mot dure selon sa longueur ; une pause sépare les mots, une pause plus longue les phrases.
 *
 * @param  list<string>  $phrases
 * @return list<MotTranscrit>
 */
function exportsMotsDepuisPhrases(array $phrases, int $debutMs = 0): array
{
    $mots = [];
    $temps = $debutMs;

    foreach ($phrases as $phrase) {
        foreach (preg_split('/\s+/u', trim($phrase)) as $jeton) {
            preg_match('/^(.*?)([.!?…,;:]*)$/u', $jeton, $m);

            if ($m[1] !== '') {
                $duree = max(150, mb_strlen($m[1]) * 70);
                $mots[] = new MotTranscrit($m[1], $temps, $temps + $duree);
                $temps += $duree + 60;
            }

            if ($m[2] !== '') {
                $mots[] = new MotTranscrit($m[2], $temps, $temps + 10);
                $temps += 10;
            }
        }

        $temps += 350;
    }

    return $mots;
}

/**
 * Les cinq cas du critère F4 : français accentué, anglais, langue non latine, phrase très longue
 * sans ponctuation, vidéo de 3 heures.
 *
 * @return array<string, array{0: list<MotTranscrit>}>
 */
function exportsCasSousTitres(): array
{
    $francais = [
        'Bienvenue dans l’atelier, où l’on transforme une vidéo en texte.',
        'Ça prend quelques secondes, sans rien installer, depuis votre ordinateur !',
        'Déjà, les créatrices préfèrent les sous-titres : é, è, ê, ë, à, ù, ô, ç.',
        'Les marketeurs gagnent un temps précieux, même avec les vidéos très longues.',
    ];

    $anglais = [
        'Welcome to the workshop, where we turn a video into a timed transcript.',
        'It only takes a few seconds, and you do not need to install anything.',
        'Creators, marketers and shop owners can export subtitles for YouTube Studio.',
        'Copy the text, download the PDF, or keep the timestamps for later.',
    ];

    $russe = [
        'Добро пожаловать в мастерскую, где видео превращается в текст с метками времени.',
        'Это занимает несколько секунд, ничего устанавливать не нужно.',
        'Создатели контента экспортируют субтитры для YouTube Studio и CapCut.',
    ];

    $mots = [];
    for ($i = 0; $i < 150; $i++) {
        $mots[] = ['sans', 'ponctuation', 'une', 'phrase', 'continue', 'longue', 'et', 'lente'][$i % 8];
    }

    $troisHeures = [];
    $temps = 0;
    $pool = [...$francais, ...$anglais];
    for ($i = 0; $temps < 10_800_000; $i++) {
        $bloc = exportsMotsDepuisPhrases([$pool[$i % count($pool)]], $temps);
        array_push($troisHeures, ...$bloc);
        $temps = end($bloc)->finMs + 350;
    }

    return [
        'français accentué' => [exportsMotsDepuisPhrases($francais)],
        'anglais' => [exportsMotsDepuisPhrases($anglais)],
        'langue non latine' => [exportsMotsDepuisPhrases($russe)],
        'phrase très longue sans ponctuation' => [exportsMotsDepuisPhrases([implode(' ', $mots)])],
        'vidéo de 3 heures' => [$troisHeures],
    ];
}

dataset('cas de sous-titres', exportsCasSousTitres());

// Critère F4 : SRT et VTT respectent chaque règle (42 caractères, 2 lignes, 1 à 7 s, pas de
// chevauchement, pas de coupure dans un mot, aucun mot perdu), sur les cinq cas.
it('produit un SRT conforme', function (array $mots) {
    $srt = app(ExporteurSrt::class)->exporter(new ContenuTranscription('t', null, null, [], $mots));

    expect($srt)->not->toBe('')
        ->and(ValidateurSousTitres::srt($srt, $mots))->toBe([]);
})->with('cas de sous-titres');

it('produit un VTT conforme', function (array $mots) {
    $vtt = app(ExporteurVtt::class)->exporter(new ContenuTranscription('t', null, null, [], $mots));

    expect(str_starts_with($vtt, "WEBVTT\n\n"))->toBeTrue()
        ->and(ValidateurSousTitres::vtt($vtt, $mots))->toBe([]);
})->with('cas de sous-titres');

// Le validateur doit attraper les fautes : sans cela, les tests ci-dessus ne prouvent rien.
it('signale une ligne de plus de 42 caractères', function () {
    $ligne = str_repeat('a', 43);
    $srt = "1\n00:00:00,000 --> 00:00:03,000\n{$ligne}\n";

    expect(ValidateurSousTitres::srt($srt))->toContain("Sous-titre 0 : ligne de 43 caractères, 42 au plus « {$ligne} ».");
});

it('signale un sous-titre de moins d’une seconde et un chevauchement', function () {
    $srt = "1\n00:00:00,000 --> 00:00:00,500\nBonjour\n\n2\n00:00:00,400 --> 00:00:03,000\nSalut\n";
    $erreurs = ValidateurSousTitres::srt($srt);

    expect($erreurs)->toContain('Sous-titre 0 : durée 500 ms hors de 1000 à 7000 ms.')
        ->and($erreurs)->toContain('Sous-titre 1 : chevauchement avec le précédent.');
});

it('signale une coupure au milieu d’un mot', function () {
    $mots = [
        new MotTranscrit('Bonjour', 0, 600),
        new MotTranscrit('tout', 700, 1000),
        new MotTranscrit('le', 1100, 1200),
        new MotTranscrit('monde', 1300, 1900),
    ];
    // La coupure tombe dans « Bonjour » : « Bon » puis « jour tout le monde ».
    $srt = "1\n00:00:00,000 --> 00:00:01,000\nBon\n\n2\n00:00:01,000 --> 00:00:03,000\njour tout le monde\n";

    expect(ValidateurSousTitres::srt($srt, $mots))->toContain('Sous-titre 0 : coupure au milieu d’un mot (position 3).');
});

it('signale un VTT sans en-tête WEBVTT', function () {
    expect(ValidateurSousTitres::vtt("00:00:00.000 --> 00:00:02.000\nBonjour\n"))
        ->toContain('L’en-tête doit être « WEBVTT », trouvé « 00:00:00.000 --> 00:00:02.000 ».');
});

// Les horodatages suivent le format de chaque format : virgule en SRT, point en VTT.
it('écrit les horodatages SRT avec une virgule et VTT avec un point', function () {
    $contenu = new ContenuTranscription('t', null, null, [], exportsMotsDepuisPhrases(['Un test d’horodatage.']));

    expect(app(ExporteurSrt::class)->exporter($contenu))
        ->toMatch('/^1\n\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}\n/');
    expect(app(ExporteurVtt::class)->exporter($contenu))
        ->toMatch('/^WEBVTT\n\n\d{2}:\d{2}:\d{2}\.\d{3} --> /');
});

// Une phrase sans ponctuation est découpée : aucun sous-titre ne dépasse 7 secondes.
it('ne dépasse jamais 7 secondes par sous-titre', function () {
    $mots = exportsCasSousTitres()['phrase très longue sans ponctuation'][0];
    $sousTitres = app(GenerateurSousTitres::class)->construire($mots);

    foreach ($sousTitres as $sousTitre) {
        expect($sousTitre->finMs - $sousTitre->debutMs)->toBeLessThanOrEqual(7000);
    }

    expect(count($sousTitres))->toBeGreaterThan(1);
});

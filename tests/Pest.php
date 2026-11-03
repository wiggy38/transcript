<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Helpers vidéo
|--------------------------------------------------------------------------
*/

/** Contenu d'une sortie yt-dlp ou d'un JSON de métadonnées enregistré dans tests/fixtures/ytdlp. */
function fixtureYtdlp(string $nom): string
{
    return file_get_contents(base_path('tests/fixtures/ytdlp/'.$nom));
}

/*
|--------------------------------------------------------------------------
| Helpers audio (FFmpeg réel, fichiers générés, aucun réseau)
|--------------------------------------------------------------------------
*/

/** Dossier de travail des tests audio, vidé à chaque test. */
function dossierAudioTest(): string
{
    $dossier = storage_path('framework/testing/audio');

    File::deleteDirectory($dossier);
    File::makeDirectory($dossier, 0775, true);

    return $dossier;
}

/**
 * Génère un média de test avec FFmpeg. $arguments contient les entrées et les
 * options de codec ; le format de sortie est imposé pour ne pas dépendre de l'extension.
 *
 * @param  list<string>  $arguments
 */
function genererMediaTest(string $chemin, string $format, array $arguments): string
{
    $resultat = Process::run([
        (string) config('voxia.tools.ffmpeg_binary'),
        '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
        ...$arguments,
        '-f', $format,
        $chemin,
    ]);

    if ($resultat->failed()) {
        throw new RuntimeException('FFmpeg (test) a échoué : '.$resultat->errorOutput());
    }

    return $chemin;
}

/** Sonde ffprobe indépendante de l'application, pour vérifier les résultats. @return array<string, mixed> */
function sondeMediaTest(string $chemin): array
{
    $resultat = Process::run([
        (string) config('voxia.tools.ffprobe_binary'),
        '-v', 'error', '-print_format', 'json', '-show_format', '-show_streams',
        $chemin,
    ]);

    return json_decode($resultat->output(), true, 512, JSON_THROW_ON_ERROR);
}

/** Valeur qui suit une option dans un tableau d'arguments de commande. */
function argumentApresOption(array $commande, string $option): ?string
{
    $position = array_search($option, $commande, true);

    return $position === false ? null : ($commande[$position + 1] ?? null);
}

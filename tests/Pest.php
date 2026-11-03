<?php

use App\Contracts\AudioExtractor;
use App\Contracts\TranscriptionEngine;
use App\Contracts\VideoFetcher;
use App\Data\MotTranscrit;
use App\Data\TranscriptionBrute;
use App\Services\Audio\FauxAudioExtractor;
use App\Services\Transcription\FakeTranscriptionEngine;
use App\Services\Video\FauxVideoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
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

/*
|--------------------------------------------------------------------------
| Helpers de traitement (chaîne de transcription, faux services, aucun réseau)
|--------------------------------------------------------------------------
*/

/** Ordre des étapes de la chaîne, tel qu'il doit apparaître dans processing_logs. */
const ETAPES_TRAITEMENT = ['metadonnees', 'telechargement', 'extraction_audio', 'transcription', 'paragraphes', 'enregistrement'];

/**
 * Prépare la chaîne avec les faux services : vidéo, extracteur audio et moteur (déjà
 * lié par Tests\TestCase). Dossier temporaire dédié, vidé à chaque appel, et disque
 * de stockage simulé. Renvoie le faux moteur pour y mettre les réponses.
 */
function preparerTraitement(?FauxVideoFetcher $videos = null, ?FauxAudioExtractor $extracteur = null): FakeTranscriptionEngine
{
    $dossier = storage_path('framework/testing/temp-traitement');

    File::deleteDirectory($dossier);
    File::makeDirectory($dossier, 0775, true);

    config([
        'voxia.tools.download.temp_directory' => $dossier,
        'voxia.traitement.delai_entre_tentatives_ms' => 0,
    ]);

    Storage::fake('local');

    app()->instance(VideoFetcher::class, $videos ?? new FauxVideoFetcher);
    app()->instance(AudioExtractor::class, $extracteur ?? new FauxAudioExtractor);

    return app(TranscriptionEngine::class);
}

/** Vrai si le dossier temporaire des tests ne contient plus aucun fichier ni sous-dossier. */
function dossierTemporaireVide(): bool
{
    $dossier = (string) config('voxia.tools.download.temp_directory');

    return File::allFiles($dossier) === [] && File::directories($dossier) === [];
}

/** Réponse de moteur à trois mots, en deux paragraphes (pause entre « monde. » et « Suite »). */
function transcriptionBruteTest(): TranscriptionBrute
{
    return new TranscriptionBrute('fr', [
        new MotTranscrit('Bonjour', 0, 500),
        new MotTranscrit('monde.', 600, 1100),
        new MotTranscrit('Suite', 3000, 3400),
    ], 'Bonjour monde. Suite', 0.0367);
}

<?php

use App\Contracts\VideoFetcher;
use App\Data\LienVideo;
use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Exceptions\ErreurVideo;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

// Aucun appel réseau : chaque processus yt-dlp est simulé par Process::fake.
// preventStrayProcesses garantit qu'aucun vrai yt-dlp ne part pendant les tests.

beforeEach(function () {
    Process::preventStrayProcesses();

    config([
        'voxia.tools.ytdlp_binary' => 'yt-dlp-test',
        'voxia.tools.proxy_url' => null,
        'voxia.tools.download.attempts' => 3,
        'voxia.tools.download.temp_directory' => storage_path('framework/testing/voxia-temp'),
    ]);
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/voxia-temp'));
});

function lienYoutubeTest(): LienVideo
{
    return new LienVideo(SourceTranscription::Youtube, 'dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
}

/** Simule un yt-dlp qui écrit un fichier audio dans le dossier passé à -P. */
function simulerTelechargementReussi(): Closure
{
    return function (PendingProcess $processus): ProcessResult {
        File::put(argumentApresOption($processus->command, '-P').'/dQw4w9WgXcQ.m4a', 'audio');

        return Process::result();
    };
}

it('lit les métadonnées sans rien télécharger', function () {
    Process::fake(['*--dump-single-json*' => Process::result(output: fixtureYtdlp('metadonnees_youtube.json'))]);

    $metadonnees = app(VideoFetcher::class)->metadonnees(lienYoutubeTest());

    expect($metadonnees->id)->toBe('dQw4w9WgXcQ')
        ->and($metadonnees->titre)->toBe('Exemple de vidéo publique')
        ->and($metadonnees->dureeSecondes)->toBe(600)
        ->and($metadonnees->miniatureUrl)->toStartWith('https://i.ytimg.com/')
        ->and($metadonnees->chaine)->toBe('Chaîne exemple')
        ->and($metadonnees->estLive)->toBeFalse();

    Process::assertRan(fn (PendingProcess $p) => in_array('--skip-download', $p->command, true));
});

it('signale un live et ne lui donne pas de durée', function () {
    Process::fake(['*--dump-single-json*' => Process::result(output: fixtureYtdlp('metadonnees_live.json'))]);

    $metadonnees = app(VideoFetcher::class)->metadonnees(lienYoutubeTest());

    expect($metadonnees->estLive)->toBeTrue()
        ->and($metadonnees->dureeSecondes)->toBe(0)
        ->and($metadonnees->miniatureUrl)->toBeNull();
});

it('passe le proxy de la configuration à yt-dlp quand il est défini', function () {
    config(['voxia.tools.proxy_url' => 'http://proxy.test:8080']);
    Process::fake(['*--dump-single-json*' => Process::result(output: fixtureYtdlp('metadonnees_youtube.json'))]);

    app(VideoFetcher::class)->metadonnees(lienYoutubeTest());

    Process::assertRan(fn (PendingProcess $p) => argumentApresOption($p->command, '--proxy') === 'http://proxy.test:8080');
});

it('n\'ajoute aucun proxy quand la configuration est vide', function () {
    Process::fake(['*--dump-single-json*' => Process::result(output: fixtureYtdlp('metadonnees_youtube.json'))]);

    app(VideoFetcher::class)->metadonnees(lienYoutubeTest());

    Process::assertRan(fn (PendingProcess $p) => ! in_array('--proxy', $p->command, true));
});

it('transmet les arguments comme un tableau, sans shell', function () {
    Process::fake(['*--dump-single-json*' => Process::result(output: fixtureYtdlp('metadonnees_youtube.json'))]);

    app(VideoFetcher::class)->metadonnees(lienYoutubeTest());

    Process::assertRan(fn (PendingProcess $p) => is_array($p->command)
        && end($p->command) === 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
});

it('ne laisse jamais la sortie brute de yt-dlp dans le message utilisateur', function () {
    Process::fake(['*--dump-single-json*' => Process::result(errorOutput: fixtureYtdlp('video_privee.txt'), exitCode: 1)]);

    try {
        app(VideoFetcher::class)->metadonnees(lienYoutubeTest());
        $this->fail('Une ErreurVideo était attendue.');
    } catch (ErreurVideo $erreur) {
        expect($erreur->motif)->toBe(MotifEchec::VideoPrivee)
            ->and($erreur->messageUtilisateur())->not->toContain('ERROR')
            ->and($erreur->messageUtilisateur())->not->toContain('Private video');
    }
});

it('télécharge la piste audio seule dans un dossier propre à la tâche', function () {
    Process::fake(simulerTelechargementReussi());

    $audio = app(VideoFetcher::class)->telecharger(lienYoutubeTest());

    expect(File::exists($audio->chemin))->toBeTrue()
        ->and(str_starts_with($audio->dossier, storage_path('framework/testing/voxia-temp')))->toBeTrue()
        ->and(dirname($audio->chemin))->toBe($audio->dossier);

    Process::assertRan(function (PendingProcess $p) {
        return argumentApresOption($p->command, '-f') === 'bestaudio'
            && in_array('--match-filter', $p->command, true)
            && argumentApresOption($p->command, '--match-filter') === '!is_live';
    });

    $audio->supprimer();

    expect(File::exists($audio->dossier))->toBeFalse();
});

it('refait une tentative après une erreur technique, puis réussit', function () {
    $appels = 0;
    Process::fake(function (PendingProcess $processus) use (&$appels): ProcessResult {
        $appels++;

        if ($appels === 1) {
            return Process::result(errorOutput: fixtureYtdlp('erreur_technique.txt'), exitCode: 1);
        }

        return simulerTelechargementReussi()($processus);
    });

    $audio = app(VideoFetcher::class)->telecharger(lienYoutubeTest());

    expect($appels)->toBe(2)
        ->and(File::exists($audio->chemin))->toBeTrue();
});

it('ne refait pas de tentative pour une vidéo privée', function () {
    $appels = 0;
    Process::fake(function () use (&$appels): ProcessResult {
        $appels++;

        return Process::result(errorOutput: fixtureYtdlp('video_privee.txt'), exitCode: 1);
    });

    expect(fn () => app(VideoFetcher::class)->telecharger(lienYoutubeTest()))
        ->toThrow(fn (ErreurVideo $erreur) => $erreur->motif === MotifEchec::VideoPrivee);

    expect($appels)->toBe(1)
        ->and(File::directories(storage_path('framework/testing/voxia-temp')))->toBeEmpty();
});

it('abandonne après le nombre de tentatives configuré et supprime le dossier', function () {
    config(['voxia.tools.download.attempts' => 2]);
    $appels = 0;
    Process::fake(function () use (&$appels): ProcessResult {
        $appels++;

        return Process::result(errorOutput: fixtureYtdlp('erreur_technique.txt'), exitCode: 1);
    });

    expect(fn () => app(VideoFetcher::class)->telecharger(lienYoutubeTest()))
        ->toThrow(fn (ErreurVideo $erreur) => $erreur->motif === MotifEchec::ErreurTechnique);

    expect($appels)->toBe(2)
        ->and(File::directories(storage_path('framework/testing/voxia-temp')))->toBeEmpty();
});

it('refuse un live sans fichier produit, sans nouvelle tentative', function () {
    $appels = 0;
    Process::fake(function () use (&$appels): ProcessResult {
        $appels++;

        return Process::result(output: fixtureYtdlp('live_filtre.txt'));
    });

    expect(fn () => app(VideoFetcher::class)->telecharger(lienYoutubeTest()))
        ->toThrow(fn (ErreurVideo $erreur) => $erreur->motif === MotifEchec::LiveEnCours);

    expect($appels)->toBe(1);
});

it('applique le délai maximal configuré aux appels yt-dlp', function () {
    config(['voxia.tools.download.timeout_seconds' => 123]);
    Process::fake(simulerTelechargementReussi());

    app(VideoFetcher::class)->telecharger(lienYoutubeTest());

    Process::assertRan(fn (PendingProcess $p) => $p->timeout === 123);
});

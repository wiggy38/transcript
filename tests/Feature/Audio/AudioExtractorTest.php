<?php

use App\Contracts\AudioExtractor;
use App\Data\ExtraitAudio;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use App\Services\Audio\NomFichierAudio;
use Illuminate\Support\Facades\File;

// Critères F3 : le MP3 a la même durée que la source à 1 s près, et son nom est valide.
// FFmpeg réel sur de petits fichiers générés ; aucun appel réseau.

/** Un bip de N secondes (entrée lavfi). */
function bipTest(int $secondes = 5): array
{
    return ['-f', 'lavfi', '-i', "sine=frequency=880:duration={$secondes}"];
}

/** Arguments de génération pour un format de test, avec un son de 5 secondes. */
function argumentsFormatTest(string $format): array
{
    return match ($format) {
        'wav' => bipTest(),
        'mp3' => [...bipTest(), '-c:a', 'libmp3lame'],
        'ipod' => [...bipTest(), '-c:a', 'aac'],
        'webm' => [...bipTest(), '-c:a', 'libopus'],
        'mov' => [...bipTest(), '-c:a', 'aac'],
        // Vidéo avec piste audio : la durée de référence est celle de la vidéo.
        'mp4' => ['-f', 'lavfi', '-i', 'testsrc=size=64x64:rate=10:duration=5', ...bipTest(), '-c:v', 'mpeg4', '-c:a', 'aac', '-shortest'],
    };
}

function extraireAudioTest(string $source, string $sortie, string $titre = 'Titre de test', ?int $decoupe = null): ExtraitAudio
{
    return app(AudioExtractor::class)->extraire($source, $sortie, $titre, $decoupe);
}

/** Motif de l'erreur levée par l'extraction, ou échec du test si rien n'est levé. */
function motifEchecExtraction(string $source, string $sortie): MotifEchec
{
    try {
        extraireAudioTest($source, $sortie);
    } catch (ErreurVideo $erreur) {
        return $erreur->motif;
    }

    throw new RuntimeException('L’extraction aurait dû échouer.');
}

it('produit un MP3 à 192 kbit/s dont la durée est celle de la vidéo source, à 1 s près', function () {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/video.mp4', 'mp4', argumentsFormatTest('mp4'));
    $dureeSource = (float) sondeMediaTest($source)['format']['duration'];

    $extrait = extraireAudioTest($source, $dossier.'/sortie', 'Ma vidéo');

    expect($extrait->chemin)->toEndWith('.mp3')
        ->and(is_file($extrait->chemin))->toBeTrue()
        ->and(abs($extrait->dureeSecondes - $dureeSource))->toBeLessThanOrEqual(1);

    $sonde = sondeMediaTest($extrait->chemin);
    $debit = (int) $sonde['format']['bit_rate'];

    expect($sonde['streams'])->toHaveCount(1)
        ->and($sonde['streams'][0]['codec_name'])->toBe('mp3')
        ->and($debit)->toBeGreaterThan(180000)
        ->and($debit)->toBeLessThan(200000);
});

it('lit le contenu réel, pas l’extension', function (string $format, string $nomFichier) {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/'.$nomFichier, $format, argumentsFormatTest($format));

    $extrait = extraireAudioTest($source, $dossier.'/sortie');

    expect(abs($extrait->dureeSecondes - 5))->toBeLessThanOrEqual(1);
})->with([
    'WAV' => ['wav', 'bip.wav'],
    'MP3' => ['mp3', 'bip.mp3'],
    'M4A' => ['ipod', 'bip.m4a'],
    'WEBM' => ['webm', 'bip.webm'],
    'MOV' => ['mov', 'bip.mov'],
    'MP4 avec vidéo' => ['mp4', 'bip.mp4'],
    'WAV déguisé en MP4' => ['wav', 'deguise.mp4'],
    'WEBM déguisé en MP3' => ['webm', 'deguise.mp3'],
]);

it('refuse un fichier qui n’est pas un média, même nommé .mp3', function (string $contenu, string $nom) {
    $dossier = dossierAudioTest();
    $source = $dossier.'/'.$nom;

    if ($contenu === 'texte') {
        file_put_contents($source, 'Ceci n’est pas un fichier audio.');
    } else {
        genererMediaTest($source, 'image2', ['-f', 'lavfi', '-i', 'color=c=red:s=16x16', '-frames:v', '1']);
    }

    expect(motifEchecExtraction($source, $dossier.'/sortie'))->toBe(MotifEchec::FormatNonSupporte);
})->with([
    'texte nommé .mp3' => ['texte', 'faux.mp3'],
    'image PNG' => ['image', 'image.png'],
]);

it('renvoie sans_audio pour une vidéo muette et ne produit aucun fichier', function () {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/muette.mp4', 'mp4', [
        '-f', 'lavfi', '-i', 'color=c=black:s=64x64:d=3',
        '-c:v', 'mpeg4',
    ]);

    expect(motifEchecExtraction($source, $dossier.'/sortie'))->toBe(MotifEchec::SansAudio);
    expect(is_dir($dossier.'/sortie') ? File::files($dossier.'/sortie') : [])->toBe([]);
});

it('ne garde que les N premières secondes quand une découpe est demandée', function () {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/bip.wav', 'wav', bipTest(5));

    $extrait = extraireAudioTest($source, $dossier.'/sortie', 'Bip', 2);

    expect(abs($extrait->dureeSecondes - 2))->toBeLessThanOrEqual(1)
        ->and($extrait->dureeSecondes)->toBeLessThan(5);
});

it('ne coupe pas au-delà de la durée de la source', function () {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/bip.wav', 'wav', bipTest(5));

    $extrait = extraireAudioTest($source, $dossier.'/sortie', 'Bip', 60);

    expect(abs($extrait->dureeSecondes - 5))->toBeLessThanOrEqual(1);
});

it('renvoie erreur_technique pour une source absente, sans fichier partiel', function () {
    $dossier = dossierAudioTest();

    $motif = motifEchecExtraction($dossier.'/absent.mp3', $dossier.'/sortie');

    expect($motif)->toBe(MotifEchec::ErreurTechnique)
        ->and(is_dir($dossier.'/sortie') ? File::files($dossier.'/sortie') : [])->toBe([]);
});

it('crée le fichier sous un nom valide pour un titre piégé', function () {
    $dossier = dossierAudioTest();
    $source = genererMediaTest($dossier.'/bip.wav', 'wav', bipTest(2));
    $titre = 'CON: vidéo / "test"?';

    $extrait = extraireAudioTest($source, $dossier.'/sortie', $titre);

    expect($extrait->nomFichier)->toBe('CON vidéo test.mp3')
        ->and($extrait->nomFichier)->toBe(NomFichierAudio::depuisTitre($titre))
        ->and(is_file($dossier.'/sortie/'.$extrait->nomFichier))->toBeTrue();
});

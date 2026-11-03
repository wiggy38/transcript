<?php

namespace App\Services\Audio;

use App\Contracts\AudioExtractor;
use App\Data\ExtraitAudio;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Implémentation de AudioExtractor avec FFmpeg et ffprobe, appelés par le composant
 * Process (tableau d'arguments, donc sans shell).
 *
 * Le format est lu dans le contenu (ffprobe), jamais dans l'extension. Les noms de
 * conteneurs ci-dessous correspondent à ceux que ffprobe renvoie pour les formats
 * acceptés (MP3, M4A, MP4, MOV, WAV, WEBM). ffprobe ne distingue pas M4A, MP4 et
 * MOV : ils partagent donc la même famille.
 */
final class FfmpegAudioExtractor implements AudioExtractor
{
    /** Noms de format renvoyés par ffprobe, pour les formats acceptés. */
    private const FORMATS_ACCEPTES = [
        'mp3',
        'wav',
        'mov', 'mp4', 'm4a', '3gp', '3g2', 'mj2',
        'matroska', 'webm',
    ];

    public function extraire(
        string $source,
        string $dossierSortie,
        string $titre,
        ?int $decoupeSecondes = null,
    ): ExtraitAudio {
        if (! is_file($source)) {
            Log::error('ffmpeg: source introuvable', ['chemin' => $source]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'source introuvable');
        }

        if ($decoupeSecondes !== null && $decoupeSecondes < 1) {
            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'découpe invalide');
        }

        $sonde = $this->sonder($source);
        $this->verifierFormat($sonde);
        $this->verifierPisteAudio($sonde);

        File::ensureDirectoryExists($dossierSortie);

        $nom = NomFichierAudio::depuisTitre($titre, 'mp3');
        $chemin = rtrim($dossierSortie, '/\\').DIRECTORY_SEPARATOR.$nom;

        $this->encoder($source, $chemin, $titre, $decoupeSecondes);

        return new ExtraitAudio(
            chemin: $chemin,
            nomFichier: $nom,
            dureeSecondes: $this->dureeMesuree($chemin),
            dureeSourceSecondes: (int) round((float) ($sonde['format']['duration'] ?? 0)),
        );
    }

    /**
     * @return array{format?: array{format_name?: string, duration?: string}, streams?: list<array<string, mixed>>}
     *
     * @throws ErreurVideo
     */
    private function sonder(string $chemin): array
    {
        $resultat = $this->executer(
            [
                (string) config('voxia.tools.ffprobe_binary'),
                '-v', 'error',
                '-print_format', 'json',
                '-show_format',
                '-show_streams',
                $chemin,
            ],
            (int) config('voxia.audio.probe_timeout_seconds'),
            'sonde',
        );

        $donnees = json_decode($resultat->output(), true);

        if ($resultat->failed() || ! is_array($donnees)) {
            // ffprobe ne sait pas lire le fichier : ce n'est pas un média.
            Log::info('ffprobe: fichier illisible', ['code' => $resultat->exitCode()]);

            throw new ErreurVideo(MotifEchec::FormatNonSupporte, 'ffprobe ne lit pas le fichier');
        }

        return $donnees;
    }

    /** @param  array<string, mixed>  $sonde */
    private function verifierFormat(array $sonde): void
    {
        $nomsFormat = explode(',', (string) ($sonde['format']['format_name'] ?? ''));

        if (array_intersect($nomsFormat, self::FORMATS_ACCEPTES) === []) {
            Log::info('ffprobe: format non accepté', ['format' => $sonde['format']['format_name'] ?? null]);

            throw new ErreurVideo(MotifEchec::FormatNonSupporte, 'format '.($sonde['format']['format_name'] ?? 'inconnu'));
        }
    }

    /** @param  array<string, mixed>  $sonde */
    private function verifierPisteAudio(array $sonde): void
    {
        foreach ($sonde['streams'] ?? [] as $flux) {
            if (($flux['codec_type'] ?? null) === 'audio') {
                return;
            }
        }

        throw new ErreurVideo(MotifEchec::SansAudio, 'aucun flux audio');
    }

    private function encoder(string $source, string $chemin, string $titre, ?int $decoupeSecondes): void
    {
        $commande = [
            (string) config('voxia.tools.ffmpeg_binary'),
            '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
            '-i', $source,
            // Première piste audio seulement : pas d'image de couverture, pas de vidéo.
            '-map', '0:a:0',
            '-c:a', 'libmp3lame',
            '-b:a', (int) config('voxia.audio.mp3_bitrate_kbps').'k',
            '-map_metadata', '-1',
            '-metadata', 'title='.$titre,
        ];

        if ($decoupeSecondes !== null) {
            array_push($commande, '-t', (string) $decoupeSecondes);
        }

        $commande[] = $chemin;

        $resultat = $this->executer($commande, (int) config('voxia.audio.extraction_timeout_seconds'), 'extraction');

        if ($resultat->failed() || ! is_file($chemin)) {
            File::delete($chemin);

            Log::warning('ffmpeg: extraction échouée', [
                'code' => $resultat->exitCode(),
                'sortie' => $resultat->errorOutput(),
            ]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'extraction échouée');
        }
    }

    /**
     * Durée du MP3 produit, lue par ffprobe et arrondie à la seconde.
     *
     * @throws ErreurVideo
     */
    private function dureeMesuree(string $chemin): int
    {
        $resultat = $this->executer(
            [
                (string) config('voxia.tools.ffprobe_binary'),
                '-v', 'error',
                '-show_entries', 'format=duration',
                '-of', 'default=noprint_wrappers=1:nokey=1',
                $chemin,
            ],
            (int) config('voxia.audio.probe_timeout_seconds'),
            'duree',
        );

        $duree = (float) trim($resultat->output());

        if ($resultat->failed() || $duree <= 0) {
            File::delete($chemin);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'durée du MP3 illisible');
        }

        return (int) round($duree);
    }

    /**
     * @param  list<string>  $commande
     *
     * @throws ErreurVideo
     */
    private function executer(array $commande, int $delai, string $etape): ProcessResult
    {
        try {
            return Process::timeout($delai)->run($commande);
        } catch (ProcessTimedOutException) {
            Log::warning('ffmpeg: délai dépassé', ['etape' => $etape, 'delai' => $delai]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'délai dépassé');
        } catch (Throwable $erreur) {
            Log::error('ffmpeg: exécution impossible', ['etape' => $etape, 'erreur' => $erreur->getMessage()]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'exécution impossible');
        }
    }
}

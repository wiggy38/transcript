<?php

namespace App\Services\Video;

use App\Contracts\VideoFetcher;
use App\Data\AudioTelecharge;
use App\Data\LienVideo;
use App\Data\MetadonneesVideo;
use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

/**
 * Implémentation de VideoFetcher avec yt-dlp. Chaque appel passe par le composant
 * Process (tableau d'arguments, donc sans shell). La sortie brute va dans les
 * journaux ; l'utilisateur ne voit que le motif traduit.
 */
final class YtDlpVideoFetcher implements VideoFetcher
{
    public function __construct(
        private readonly ReconnaissanceLien $reconnaissance,
    ) {}

    public function reconnaitre(string $url): LienVideo
    {
        return $this->reconnaissance->reconnaitre($url);
    }

    public function metadonnees(LienVideo $lien): MetadonneesVideo
    {
        $resultat = $this->executer(
            ['--skip-download', '--dump-single-json', '--no-playlist', $lien->url],
            (int) config('voxia.tools.download.metadata_timeout_seconds'),
            'metadonnees',
        );

        $donnees = json_decode($resultat->output(), true);

        if (! is_array($donnees) || empty($donnees['id'])) {
            Log::warning('yt-dlp: métadonnées illisibles', ['sortie' => $resultat->output()]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'métadonnées illisibles');
        }

        return new MetadonneesVideo(
            id: (string) $donnees['id'],
            titre: (string) ($donnees['title'] ?? ''),
            dureeSecondes: (int) round((float) ($donnees['duration'] ?? 0)),
            miniatureUrl: is_string($donnees['thumbnail'] ?? null) ? $donnees['thumbnail'] : null,
            chaine: is_string($donnees['channel'] ?? null)
                ? $donnees['channel']
                : (is_string($donnees['uploader'] ?? null) ? $donnees['uploader'] : null),
            estLive: ($donnees['is_live'] ?? false) === true || ($donnees['live_status'] ?? null) === 'is_live',
        );
    }

    public function telecharger(LienVideo $lien): AudioTelecharge
    {
        $dossier = $this->creerDossierTache();
        $tentatives = max(1, (int) config('voxia.tools.download.attempts'));

        try {
            for ($essai = 1; ; $essai++) {
                File::cleanDirectory($dossier);

                try {
                    return $this->lancerTelechargement($lien, $dossier);
                } catch (ErreurVideo $erreur) {
                    if (! $erreur->estTechnique() || $essai >= $tentatives) {
                        throw $erreur;
                    }

                    Log::info('yt-dlp: nouvelle tentative', ['essai' => $essai + 1, 'sur' => $tentatives]);
                }
            }
        } catch (Throwable $erreur) {
            File::deleteDirectory($dossier);

            throw $erreur;
        }
    }

    private function lancerTelechargement(LienVideo $lien, string $dossier): AudioTelecharge
    {
        $resultat = $this->executer(
            [
                '--no-playlist',
                '--no-part',
                '--no-progress',
                // Un live en cours ne doit jamais être téléchargé (il ne se termine pas).
                '--match-filter', '!is_live',
                '-f', 'bestaudio',
                '-P', $dossier,
                '-o', '%(id)s.%(ext)s',
                $lien->url,
            ],
            (int) config('voxia.tools.download.timeout_seconds'),
            'telechargement',
        );

        $fichiers = File::files($dossier);

        if (count($fichiers) !== 1) {
            $motif = TraducteurErreurYtdlp::depuisSortie($resultat->output().$resultat->errorOutput());

            Log::warning('yt-dlp: aucun fichier audio produit', [
                'motif' => $motif->value,
                'sortie' => $resultat->output()."\n".$resultat->errorOutput(),
            ]);

            throw new ErreurVideo($motif, 'aucun fichier audio');
        }

        return new AudioTelecharge($fichiers[0]->getPathname(), $dossier);
    }

    /**
     * @param  list<string>  $arguments
     *
     * @throws ErreurVideo
     */
    private function executer(array $arguments, int $delai, string $etape): ProcessResult
    {
        $commande = [
            (string) config('voxia.tools.ytdlp_binary'),
            '--ignore-config',
            ...$this->proxy(),
            ...$arguments,
        ];

        try {
            $resultat = Process::timeout($delai)->run($commande);
        } catch (ProcessTimedOutException) {
            Log::warning('yt-dlp: délai dépassé', ['etape' => $etape, 'delai' => $delai]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'délai dépassé');
        } catch (Throwable $erreur) {
            Log::error('yt-dlp: exécution impossible', ['etape' => $etape, 'erreur' => $erreur->getMessage()]);

            throw new ErreurVideo(MotifEchec::ErreurTechnique, 'exécution impossible');
        }

        if ($resultat->failed()) {
            $sortie = $resultat->output()."\n".$resultat->errorOutput();
            $motif = TraducteurErreurYtdlp::depuisSortie($sortie);

            Log::warning('yt-dlp: échec', [
                'etape' => $etape,
                'motif' => $motif->value,
                'code' => $resultat->exitCode(),
                'sortie' => $sortie,
            ]);

            throw new ErreurVideo($motif, 'code '.$resultat->exitCode());
        }

        return $resultat;
    }

    /** @return list<string> */
    private function proxy(): array
    {
        $proxy = config('voxia.tools.proxy_url');

        return is_string($proxy) && $proxy !== '' ? ['--proxy', $proxy] : [];
    }

    private function creerDossierTache(): string
    {
        $dossier = rtrim((string) config('voxia.tools.download.temp_directory'), '/\\')
            .DIRECTORY_SEPARATOR.Str::uuid()->toString();

        File::makeDirectory($dossier, 0775, true);

        return $dossier;
    }
}

<?php

namespace App\Services\Video;

use App\Contracts\VideoFetcher;
use App\Data\AudioTelecharge;
use App\Data\LienVideo;
use App\Data\MetadonneesVideo;
use App\Exceptions\ErreurVideo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Fausse implémentation de VideoFetcher pour les tests : aucun appel réseau, aucun
 * processus externe. La reconnaissance du lien reste réelle (elle est locale).
 */
final class FauxVideoFetcher implements VideoFetcher
{
    public function __construct(
        public ?MetadonneesVideo $metadonnees = null,
        public ?ErreurVideo $erreur = null,
    ) {}

    public function reconnaitre(string $url): LienVideo
    {
        return (new ReconnaissanceLien)->reconnaitre($url);
    }

    public function metadonnees(LienVideo $lien): MetadonneesVideo
    {
        $this->lancerErreurSiConfiguree();

        return $this->metadonnees ?? new MetadonneesVideo(
            id: $lien->idVideo ?? 'faux-id',
            titre: 'Vidéo factice',
            dureeSecondes: 600,
            miniatureUrl: null,
            chaine: 'Chaîne factice',
            estLive: false,
        );
    }

    public function telecharger(LienVideo $lien): AudioTelecharge
    {
        $this->lancerErreurSiConfiguree();

        $dossier = rtrim((string) config('voxia.tools.download.temp_directory'), '/\\')
            .DIRECTORY_SEPARATOR.Str::uuid()->toString();

        File::makeDirectory($dossier, 0775, true);
        File::put($dossier.DIRECTORY_SEPARATOR.'faux.m4a', 'audio factice');

        return new AudioTelecharge($dossier.DIRECTORY_SEPARATOR.'faux.m4a', $dossier);
    }

    private function lancerErreurSiConfiguree(): void
    {
        if ($this->erreur !== null) {
            throw $this->erreur;
        }
    }
}

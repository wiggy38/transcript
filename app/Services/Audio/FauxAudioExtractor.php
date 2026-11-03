<?php

namespace App\Services\Audio;

use App\Contracts\AudioExtractor;
use App\Data\ExtraitAudio;
use App\Exceptions\ErreurVideo;
use Illuminate\Support\Facades\File;

/**
 * Fausse implémentation de AudioExtractor pour les tests de la chaîne : écrit un
 * faux MP3 dans le dossier de sortie, sans FFmpeg. Le vrai extracteur est testé à
 * part (tests/Feature/Audio).
 */
final class FauxAudioExtractor implements AudioExtractor
{
    public function __construct(
        public int $dureeSecondes = 600,
        public ?ErreurVideo $erreur = null,
    ) {}

    public function extraire(
        string $source,
        string $dossierSortie,
        string $titre,
        ?int $decoupeSecondes = null,
    ): ExtraitAudio {
        if ($this->erreur !== null) {
            throw $this->erreur;
        }

        File::ensureDirectoryExists($dossierSortie);

        $nom = 'faux.mp3';
        $chemin = rtrim($dossierSortie, '/\\').DIRECTORY_SEPARATOR.$nom;
        File::put($chemin, 'mp3 factice');

        return new ExtraitAudio(
            chemin: $chemin,
            nomFichier: $nom,
            dureeSecondes: $decoupeSecondes !== null ? min($decoupeSecondes, $this->dureeSecondes) : $this->dureeSecondes,
            dureeSourceSecondes: $this->dureeSecondes,
        );
    }
}

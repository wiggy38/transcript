<?php

namespace App\Data;

use Illuminate\Support\Facades\File;

final readonly class AudioTelecharge
{
    public function __construct(
        /** Fichier audio (conteneur d'origine, sans conversion). */
        public string $chemin,
        /** Dossier temporaire propre à la tâche, qui contient le fichier. */
        public string $dossier,
    ) {}

    public function supprimer(): void
    {
        File::deleteDirectory($this->dossier);
    }
}

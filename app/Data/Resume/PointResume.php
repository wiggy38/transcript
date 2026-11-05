<?php

namespace App\Data\Resume;

/** Point clé validé : son paragraphe source et l'horodatage lu dans la transcription (jamais fourni par le modèle). */
final readonly class PointResume
{
    public function __construct(
        public string $texte,
        public int $paragraphe,
        public int $debutMs,
    ) {}
}

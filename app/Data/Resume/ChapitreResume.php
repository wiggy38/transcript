<?php

namespace App\Data\Resume;

/** Chapitre validé : titre, début (lu dans la transcription) et deux phrases. */
final readonly class ChapitreResume
{
    public function __construct(
        public string $titre,
        public int $paragraphe,
        public int $debutMs,
        public string $resume,
    ) {}
}

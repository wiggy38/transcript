<?php

namespace App\Data\Resume;

/** Paragraphe transmis au modèle : son numéro dans la transcription (« Paragraphe N »), son début et son texte. */
final readonly class ParagrapheResume
{
    public function __construct(
        public int $numero,
        public int $debutMs,
        public string $texte,
    ) {}
}

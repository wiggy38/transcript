<?php

namespace App\Data;

final readonly class MotTranscrit
{
    public function __construct(
        public string $texte,
        /** Début du mot, en millisecondes depuis le début du fichier. */
        public int $debutMs,
        /** Fin du mot, en millisecondes. */
        public int $finMs,
    ) {}
}

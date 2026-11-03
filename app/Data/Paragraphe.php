<?php

namespace App\Data;

final readonly class Paragraphe
{
    public function __construct(
        /** Horodatage du premier mot du paragraphe, en millisecondes. */
        public int $debutMs,
        /** Fin du dernier mot, en millisecondes. */
        public int $finMs,
        /** Texte du paragraphe, ponctuation du moteur conservée. */
        public string $texte,
    ) {}
}

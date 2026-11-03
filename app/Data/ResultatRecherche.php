<?php

namespace App\Data;

use App\Models\Transcription;

/**
 * Un passage trouvé par la recherche : la transcription, l'horodatage de son paragraphe (ms)
 * et l'extrait déjà échappé, avec les termes trouvés entourés de <mark>.
 */
final readonly class ResultatRecherche
{
    public function __construct(
        public Transcription $transcription,
        public int $debutMs,
        public string $extraitHtml,
    ) {}
}

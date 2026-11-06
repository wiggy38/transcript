<?php

namespace App\Data;

use App\Models\Transcription;

/**
 * Un passage trouvé par la recherche : la transcription, l'horodatage de son paragraphe (ms)
 * et l'extrait déjà échappé, avec les termes trouvés entourés de <mark>.
 * Un résultat trouvé dans le résumé ou dans une traduction n'a pas de passage : debutMs vaut null.
 * « langueTraduction » est renseigné seulement pour une traduction (code de la langue cible).
 */
final readonly class ResultatRecherche
{
    public function __construct(
        public Transcription $transcription,
        public ?int $debutMs,
        public string $extraitHtml,
        public ?string $langueTraduction = null,
    ) {}

    public function estDuResume(): bool
    {
        return $this->debutMs === null && $this->langueTraduction === null;
    }

    public function estUneTraduction(): bool
    {
        return $this->langueTraduction !== null;
    }
}

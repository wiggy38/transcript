<?php

namespace App\Data;

final readonly class MetadonneesVideo
{
    public function __construct(
        public string $id,
        public string $titre,
        public int $dureeSecondes,
        public ?string $miniatureUrl,
        public ?string $chaine,
        public bool $estLive,
    ) {}
}

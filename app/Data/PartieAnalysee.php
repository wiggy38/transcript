<?php

namespace App\Data;

use App\Enums\TypePartieScript;

/**
 * Une partie du script. Côté fournisseur, les bornes sont brutes ; après validation, elles sont
 * celles des mots réels et le texte est reconstruit à partir de ces mots. Une partie absente
 * a detectee = false et aucune borne ni texte.
 */
final readonly class PartieAnalysee
{
    public function __construct(
        public TypePartieScript $type,
        public bool $detectee,
        public ?int $debutMs = null,
        public ?int $finMs = null,
        public ?string $texte = null,
    ) {}
}

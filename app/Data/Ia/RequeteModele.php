<?php

namespace App\Data\Ia;

/**
 * Une demande au modèle de langage.
 *
 * « donnees » : textes à traiter (transcription, etc.). Ce sont des données non fiables : elles
 * sont échappées avant envoi et ne peuvent pas refermer leur balise.
 *
 * @param  list<string>  $donnees
 */
final readonly class RequeteModele
{
    public function __construct(
        public string $fonctionnalite,
        public string $instructions,
        public array $donnees,
        public int $maxTokens,
        public ?int $userId = null,
        public ?int $aiOutputId = null,
    ) {}
}

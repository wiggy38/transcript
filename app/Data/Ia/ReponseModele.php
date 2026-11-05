<?php

namespace App\Data\Ia;

/**
 * Réponse d'un appel réussi. « texte » pour generer(), « structure » pour genererStructure().
 * Les coûts et jetons sont ceux du dernier appel réussi ; les appels précédents sont dans appels_ia.
 */
final readonly class ReponseModele
{
    /**
     * @param  array<string, mixed>|null  $structure
     */
    public function __construct(
        public ?string $texte,
        public ?array $structure,
        public string $modele,
        public int $tokensEntree,
        public int $tokensSortie,
        public float $coutUsd,
        public int $dureeMs,
    ) {}
}

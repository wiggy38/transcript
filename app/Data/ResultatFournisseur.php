<?php

namespace App\Data;

/** Réponse d'un fournisseur d'analyse, avec ce que l'appel a coûté. */
final readonly class ResultatFournisseur
{
    /** @param  list<PartieAnalysee>  $parties  Cinq parties, dans l'ordre des types. */
    public function __construct(
        public array $parties,
        public string $fournisseur,
        public string $modele,
        public int $tokensEntree,
        public int $tokensSortie,
        public float $coutUsd,
    ) {}
}

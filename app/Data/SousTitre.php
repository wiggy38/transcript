<?php

namespace App\Data;

final readonly class SousTitre
{
    /**
     * @param  list<string>  $lignes  Une à deux lignes, jamais coupées au milieu d'un mot.
     */
    public function __construct(
        /** Début d'affichage, en millisecondes. */
        public int $debutMs,
        /** Fin d'affichage, en millisecondes. */
        public int $finMs,
        public array $lignes,
    ) {}
}

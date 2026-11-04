<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Analyse impossible : réponse invalide, refusée, tronquée, ou appel en échec. « coutUsd »
 * porte ce que l'appel a coûté malgré l'échec, pour le journal (la sortie refusée reste facturée).
 */
class ErreurAnalyse extends RuntimeException
{
    public function __construct(string $message, public readonly float $coutUsd = 0.0)
    {
        parent::__construct($message);
    }
}

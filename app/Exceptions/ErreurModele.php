<?php

namespace App\Exceptions;

use App\Enums\MotifErreurModele;
use RuntimeException;

/**
 * Appel au modèle de langage impossible. « coutUsd » porte ce que les appels ont coûté malgré
 * l'échec (une sortie refusée reste facturée) ; il est aussi écrit dans appels_ia.
 */
class ErreurModele extends RuntimeException
{
    public function __construct(
        public readonly MotifErreurModele $motif,
        public readonly float $coutUsd = 0.0,
    ) {
        parent::__construct($motif->value);
    }
}

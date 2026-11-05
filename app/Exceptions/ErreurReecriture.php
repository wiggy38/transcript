<?php

namespace App\Exceptions;

use RuntimeException;

/** Sortie du modèle qui ne respecte pas les règles d'un format. Une relance est tentée, puis l'échec, sans décompte. */
class ErreurReecriture extends RuntimeException
{
    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

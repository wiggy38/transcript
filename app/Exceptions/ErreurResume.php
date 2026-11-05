<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Sortie du modèle qui ne respecte pas les règles (phrases, points, chapitres, chiffres absents du texte).
 * Le motif sert au journal ; une erreur de ce type ne décompte rien. Une relance est tentée avant l'échec.
 */
class ErreurResume extends RuntimeException
{
    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

/** Sortie du modèle qui ne correspond pas aux segments envoyés. Une relance est tentée, puis l'échec, sans décompte. */
class ErreurTraduction extends RuntimeException
{
    public const NOMBRE_SEGMENTS = 'nombre_segments';

    public const IDENTIFIANT = 'identifiant';

    public const ORDRE = 'ordre';

    public const TEXTE_VIDE = 'texte_vide';

    public const STRUCTURE = 'structure';

    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

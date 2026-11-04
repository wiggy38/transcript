<?php

namespace App\Exceptions;

use RuntimeException;

/** Lancement d'une analyse refusé avant tout appel. Le motif sert à choisir le message. */
class AnalyseRefusee extends RuntimeException
{
    public const DUREE_TROP_LONGUE = 'duree_trop_longue';

    public const DUREE_INCONNUE = 'duree_inconnue';

    public const NON_TERMINEE = 'non_terminee';

    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

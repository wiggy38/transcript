<?php

namespace App\Exceptions;

use RuntimeException;

/** Lancement d'une analyse refusé avant tout appel. Le motif sert à choisir le message. */
class AnalyseRefusee extends RuntimeException
{
    public const DUREE_TROP_LONGUE = 'duree_trop_longue';

    public const DUREE_INCONNUE = 'duree_inconnue';

    public const NON_TERMINEE = 'non_terminee';

    /** Pas de crédit IA disponible : Gratuit et Pack n'y ont pas accès, le Pro en a un volume mensuel. */
    public const CREDIT_IA_INSUFFISANT = 'credit_ia_insuffisant';

    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

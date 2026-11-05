<?php

namespace App\Exceptions;

use RuntimeException;

/** Lancement de réécriture refusé avant toute écriture ni appel au modèle. Le motif sert à choisir le message. */
class ReecritureRefusee extends RuntimeException
{
    public const NON_TERMINEE = 'non_terminee';

    public const PLAN_SANS_ACCES = 'plan_sans_acces';

    public const CREDIT_INSUFFISANT = 'credit_ia_insuffisant';

    /** Au moins un format demandé, mais aucun n'est valide. */
    public const AUCUN_FORMAT = 'aucun_format';

    public const LANGUE_INVALIDE = 'langue_invalide';

    public const CONFIRMATION_REQUISE = 'confirmation_requise';

    public function __construct(public readonly string $motif, public readonly int $credits = 0)
    {
        parent::__construct($motif);
    }
}

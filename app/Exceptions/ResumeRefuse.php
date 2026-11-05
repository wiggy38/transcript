<?php

namespace App\Exceptions;

use RuntimeException;

/** Lancement d'un résumé refusé avant tout appel au modèle. Le motif sert à choisir le message. */
class ResumeRefuse extends RuntimeException
{
    public const NON_TERMINEE = 'non_terminee';

    public const DUREE_INCONNUE = 'duree_inconnue';

    /** Gratuit et Pack n'ont pas de crédits IA : pas d'accès au résumé. */
    public const PLAN_SANS_ACCES = 'plan_sans_acces';

    public const CREDIT_INSUFFISANT = 'credit_ia_insuffisant';

    /** Régénérer coûte de nouveaux crédits : l'utilisateur doit le confirmer. */
    public const CONFIRMATION_REQUISE = 'confirmation_requise';

    public const LANGUE_INVALIDE = 'langue_invalide';

    public function __construct(public readonly string $motif)
    {
        parent::__construct($motif);
    }
}

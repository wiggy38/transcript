<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Action du back-office refusée avant toute écriture. Le code sert au message affiché
 * (lang/fr|en/administration.php, clé « refus.<code> »).
 */
class ActionAdministrationRefusee extends RuntimeException
{
    public const MOTIF_VIDE = 'motif_vide';

    public const QUANTITE_NULLE = 'quantite_nulle';

    public const CIBLE_INDISPONIBLE = 'cible_indisponible';

    public const PAS_EN_ECHEC = 'pas_en_echec';

    public const RIEN_A_REMBOURSER = 'rien_a_rembourser';

    public function __construct(public readonly string $raison)
    {
        parent::__construct($raison);
    }
}

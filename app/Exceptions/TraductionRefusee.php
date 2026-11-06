<?php

namespace App\Exceptions;

use RuntimeException;

/** Lancement de traduction refusé avant toute écriture ni appel au modèle. Le motif sert à choisir le message. */
class TraductionRefusee extends RuntimeException
{
    public const NON_TERMINEE = 'non_terminee';

    public const TRANSCRIPTION_VIDE = 'transcription_vide';

    public const SOURCE_NON_PRISE_EN_CHARGE = 'source_non_prise_en_charge';

    public const LANGUE_INVALIDE = 'langue_invalide';

    public const MEME_LANGUE = 'meme_langue';

    public const AUCUNE_LANGUE = 'aucune_langue';

    public const CREDIT_INSUFFISANT = 'credit_ia_insuffisant';

    public function __construct(public readonly string $motif, public readonly int $credits = 0)
    {
        parent::__construct($motif);
    }
}

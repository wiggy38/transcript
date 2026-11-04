<?php

namespace App\Enums;

/**
 * Compteur ajustable depuis le back-office. Les heures se saisissent en heures ; la ligne de
 * consommation est écrite en secondes.
 */
enum CibleAjustement: string
{
    case Videos = 'videos';
    case HeuresMois = 'heures_mois';
    case HeuresPack = 'heures_pack';
    case CreditsIa = 'credits_ia';

    public function unite(): UniteConsommation
    {
        return match ($this) {
            self::Videos => UniteConsommation::Video,
            self::HeuresMois, self::HeuresPack => UniteConsommation::Secondes,
            self::CreditsIa => UniteConsommation::CreditIa,
        };
    }
}

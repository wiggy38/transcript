<?php

namespace App\Support;

/** Montants en dollars US (offre), écrits selon la langue de l'interface : « 12 $ » en français, « $12 » en anglais. */
final class Montant
{
    public static function dollars(float $valeur): string
    {
        $decimales = fmod($valeur, 1.0) === 0.0 ? 0 : 2;

        if (app()->getLocale() === 'fr') {
            return number_format($valeur, $decimales, ',', '')."\u{00A0}$";
        }

        return '$'.number_format($valeur, $decimales, '.', '');
    }
}

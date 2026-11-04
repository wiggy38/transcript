<?php

namespace App\Support;

use Carbon\CarbonInterface;
use NumberFormatter;

/**
 * Dates et nombres écrits selon la langue de l'interface : « 4 novembre 2026 » en français,
 * « November 4, 2026 » en anglais ; « 1 234,5 » en français, « 1,234.5 » en anglais.
 */
final class Formats
{
    /** Date seule, en toutes lettres. */
    public static function date(CarbonInterface $date): string
    {
        return $date->copy()->locale(app()->getLocale())->isoFormat('LL');
    }

    /** Date et heure : « 4 novembre 2026 à 10:30 », « November 4, 2026 at 10:30 AM ». */
    public static function dateHeure(CarbonInterface $date): string
    {
        $format = app()->getLocale() === 'fr' ? 'LL [à] HH:mm' : 'LL [at] h:mm A';

        return $date->copy()->locale(app()->getLocale())->isoFormat($format);
    }

    /** Nombre décimal avec séparateurs de la langue. */
    public static function nombre(float|int $valeur, int $decimales = 0): string
    {
        $formateur = new NumberFormatter(self::localeIntl(), NumberFormatter::DECIMAL);
        $formateur->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimales);
        $formateur->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimales);

        return (string) $formateur->format($valeur);
    }

    /** Locale ICU de la langue courante (fr_FR, en_US). */
    public static function localeIntl(): string
    {
        return app()->getLocale() === 'fr' ? 'fr_FR' : 'en_US';
    }
}

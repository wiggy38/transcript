<?php

namespace App\Support;

/**
 * Formate un horodatage en millisecondes pour l'affichage : mm:ss, ou h:mm:ss au-delà d'une heure.
 */
final class Horodatage
{
    public static function format(int $millisecondes): string
    {
        $total = intdiv(max(0, $millisecondes), 1000);
        $heures = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $secondes = $total % 60;

        return $heures > 0
            ? sprintf('%d:%02d:%02d', $heures, $minutes, $secondes)
            : sprintf('%02d:%02d', $minutes, $secondes);
    }

    /** Horodatage SRT : 00:00:00,000 (virgule avant les millisecondes). */
    public static function srt(int $millisecondes): string
    {
        return self::sousTitre($millisecondes, ',');
    }

    /** Horodatage WebVTT : 00:00:00.000 (point avant les millisecondes). */
    public static function vtt(int $millisecondes): string
    {
        return self::sousTitre($millisecondes, '.');
    }

    private static function sousTitre(int $millisecondes, string $separateur): string
    {
        $millisecondes = max(0, $millisecondes);
        $total = intdiv($millisecondes, 1000);

        return sprintf(
            '%02d:%02d:%02d%s%03d',
            intdiv($total, 3600),
            intdiv($total % 3600, 60),
            $total % 60,
            $separateur,
            $millisecondes % 1000,
        );
    }

    /** Durée ISO 8601, pour l'attribut datetime de la balise time. */
    public static function iso(int $millisecondes): string
    {
        return sprintf('PT%.3fS', max(0, $millisecondes) / 1000);
    }
}

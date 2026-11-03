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

    /** Durée ISO 8601, pour l'attribut datetime de la balise time. */
    public static function iso(int $millisecondes): string
    {
        return sprintf('PT%.3fS', max(0, $millisecondes) / 1000);
    }
}

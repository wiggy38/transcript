<?php

namespace App\Services\Transcription;

/**
 * Coût estimé d'une transcription, à partir du tarif de config (voxia.costs). Le
 * silence compte dans la durée facturée : on prend la durée du fichier, pas celle
 * des mots.
 */
final class CoutTranscription
{
    public static function estimer(int $dureeSecondes): float
    {
        $tarifHoraire = (float) config('voxia.costs.transcription_usd_per_hour');

        return round(max(0, $dureeSecondes) / 3600 * $tarifHoraire, 4);
    }
}

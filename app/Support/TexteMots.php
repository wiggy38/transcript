<?php

namespace App\Support;

use App\Data\MotTranscrit;

/**
 * Reconstruit un texte à partir de mots horodatés, sans le réécrire : la ponctuation seule est
 * rattachée au mot précédent, les autres mots sont séparés par un espace. Partagé par les
 * paragraphes (F2) et l'analyse de script (F7), pour que les deux textes soient identiques.
 */
final class TexteMots
{
    /** Ponctuation seule, rattachée au mot précédent. */
    private const PONCTUATION_SEULE = '/^[.,;:!?…)»”"\]]+$/u';

    /** @param  list<MotTranscrit>  $mots */
    public static function joindre(array $mots): string
    {
        $texte = '';

        foreach ($mots as $mot) {
            $texte = $texte === ''
                ? $mot->texte
                : (preg_match(self::PONCTUATION_SEULE, $mot->texte) === 1
                    ? $texte.$mot->texte
                    : $texte.' '.$mot->texte);
        }

        return $texte;
    }
}

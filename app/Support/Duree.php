<?php

namespace App\Support;

/** Durée affichée dans les compteurs : « 12 h 40 », ou « 40 min » sous une heure. */
final class Duree
{
    public static function formater(int $secondes): string
    {
        $minutes = intdiv(max(0, $secondes), 60);

        if ($minutes < 60) {
            return $minutes.' min';
        }

        return intdiv($minutes, 60).' h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}

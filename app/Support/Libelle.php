<?php

namespace App\Support;

/** Forme de comparaison d'un nom saisi : espaces réduits, sans tenir compte de la casse. */
final class Libelle
{
    public static function normaliser(string $nom): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $nom) ?? ''));
    }
}

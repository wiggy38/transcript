<?php

namespace App\Services\Audio;

use Normalizer;

/**
 * Construit un nom de fichier à partir d'un titre de vidéo, valide sous Windows,
 * macOS et Linux :
 *  - accents conservés, mais en forme composée (NFC), pour macOS ;
 *  - caractères interdits retirés (< > : " / \ | ? * et contrôles) ;
 *  - espaces répétés réduits, points et espaces retirés en bout de nom ;
 *  - noms réservés par Windows (CON, NUL, COM1…) préfixés par « _ » ;
 *  - longueur limitée en octets, sans couper un caractère UTF-8.
 */
final class NomFichierAudio
{
    private const NOM_PAR_DEFAUT = 'audio';

    private const CARACTERES_INTERDITS = '/[<>:"\/\\\\|?*\x00-\x1F\x7F]/u';

    private const NOMS_RESERVES = [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ];

    public static function depuisTitre(string $titre, string $extension = 'mp3', ?int $longueurMax = null): string
    {
        $longueurMax ??= (int) config('voxia.audio.filename_max_bytes');
        $extension = ltrim($extension, '.');

        $base = self::nettoyer($titre);
        $base = $base === '' ? self::NOM_PAR_DEFAUT : $base;

        // Un octet est réservé au préfixe « _ » éventuel (noms réservés).
        $budget = $longueurMax - strlen($extension) - 2;
        $base = self::tronquer($base, $budget);

        if (self::estReserve($base)) {
            $base = '_'.$base;
        }

        return $base.'.'.$extension;
    }

    private static function nettoyer(string $titre): string
    {
        if (class_exists(Normalizer::class)) {
            $titre = Normalizer::normalize($titre, Normalizer::FORM_C) ?: $titre;
        }

        // Les tabulations et sauts de ligne deviennent des espaces avant suppression.
        $titre = preg_replace('/\s+/u', ' ', $titre);
        $titre = preg_replace(self::CARACTERES_INTERDITS, '', $titre);
        $titre = preg_replace('/\s+/u', ' ', $titre);

        return trim($titre, " .\t");
    }

    private static function tronquer(string $base, int $budget): string
    {
        if (strlen($base) <= $budget) {
            return $base;
        }

        // mb_strcut ne coupe jamais au milieu d'un caractère UTF-8.
        return trim(mb_strcut($base, 0, max(1, $budget), 'UTF-8'), " .\t");
    }

    private static function estReserve(string $base): bool
    {
        $radical = strtoupper(trim(explode('.', $base, 2)[0]));

        return in_array($radical, self::NOMS_RESERVES, true);
    }
}

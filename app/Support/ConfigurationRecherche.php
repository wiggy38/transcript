<?php

namespace App\Support;

/**
 * Configuration PostgreSQL de recherche (« french », « english »…) d'une transcription, selon
 * sa langue. Les valeurs viennent de config/voxia.php (« recherche »). La liste fermée sert
 * aussi à écrire les requêtes : seul un nom de cette liste entre dans le SQL.
 */
final class ConfigurationRecherche
{
    /** Configuration de la langue donnée, ou la configuration par défaut si la langue n'est pas listée. */
    public static function pourLangue(?string $langue): string
    {
        $configurations = (array) config('voxia.recherche.configurations');
        $code = strtolower((string) $langue);

        return $configurations[$code] ?? self::parDefaut();
    }

    /** @return list<string> toutes les configurations utilisées, sans doublon. */
    public static function toutes(): array
    {
        return array_values(array_unique([
            ...array_values((array) config('voxia.recherche.configurations')),
            self::parDefaut(),
        ]));
    }

    public static function parDefaut(): string
    {
        return (string) config('voxia.recherche.configuration_defaut', 'simple');
    }
}

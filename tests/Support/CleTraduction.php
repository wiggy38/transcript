<?php

namespace Tests\Support;

/**
 * Repère une clé de traduction restée visible dans une page (ex. « pages.tarifs.titre »).
 * Les groupes sont les fichiers de lang/fr : chaque clé commence par le nom de son fichier.
 */
final class CleTraduction
{
    public static function visible(string $html): ?string
    {
        $groupes = collect(glob(lang_path('fr/*.php')))
            ->map(fn (string $fichier) => preg_quote(pathinfo($fichier, PATHINFO_FILENAME), '/'))
            ->implode('|');

        return preg_match('/\b(?:'.$groupes.')\.[a-z_][a-z0-9_]*\.[a-z0-9_.]+/', $html, $trouve) === 1 ? $trouve[0] : null;
    }
}

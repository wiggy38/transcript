<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Langue de l'interface. Ordre de choix (du plus fort au plus faible) :
 * 1. la langue de l'adresse (/fr, /en) sur les pages publiques ;
 * 2. la préférence du compte, si elle est définie ;
 * 3. le choix mémorisé dans le navigateur (cookie, posé par le sélecteur ou par une adresse /fr ou /en) ;
 * 4. la langue préférée du navigateur (en-tête Accept-Language) ;
 * 5. la langue par défaut (voxia.locale_defaut).
 */
final class Langue
{
    /** Cookie du choix de langue, pour les visiteurs sans compte. */
    public const COOKIE = 'voxia_langue';

    /** Durée du cookie : un an, en minutes. */
    private const DUREE_COOKIE = 525600;

    /** @return list<string> */
    public static function disponibles(): array
    {
        return array_values((array) config('voxia.locales'));
    }

    public static function defaut(): string
    {
        return (string) config('voxia.locale_defaut');
    }

    /** La valeur si elle est une langue de l'application, sinon null. */
    public static function valide(mixed $valeur): ?string
    {
        return is_string($valeur) && in_array($valeur, self::disponibles(), true) ? $valeur : null;
    }

    /** Langue d'un destinataire (courriel) : sa préférence, sinon la langue par défaut. */
    public static function pour(?object $utilisateur): string
    {
        return self::valide($utilisateur?->locale ?? null) ?? self::defaut();
    }

    public static function resoudre(Request $request): string
    {
        return self::valide($request->route('locale'))
            ?? self::valide($request->user()?->locale)
            ?? self::valide($request->cookie(self::COOKIE))
            ?? self::depuisNavigateur($request->header('Accept-Language'))
            ?? self::defaut();
    }

    /**
     * Première langue de l'application présente dans Accept-Language, par ordre de préférence
     * (« fr-CH, fr;q=0.9, en;q=0.8 » → fr). Les sous-balises (« en-US ») comptent pour leur langue.
     */
    public static function depuisNavigateur(?string $entete): ?string
    {
        if ($entete === null || trim($entete) === '') {
            return null;
        }

        $candidates = [];

        foreach (explode(',', $entete) as $rang => $partie) {
            [$etiquette, $qualite] = array_pad(preg_split('/;\s*q=/', trim($partie), 2) ?: [], 2, '1');
            $langue = strtolower(explode('-', trim($etiquette))[0]);

            if ($langue !== '') {
                $candidates[] = ['langue' => $langue, 'q' => (float) $qualite, 'rang' => $rang];
            }
        }

        usort($candidates, fn (array $a, array $b) => [$b['q'], $a['rang']] <=> [$a['q'], $b['rang']]);

        foreach ($candidates as $candidate) {
            if (($langue = self::valide($candidate['langue'])) !== null) {
                return $langue;
            }
        }

        return null;
    }

    /**
     * Adresse de la même page dans chaque langue (pour hreflang). Vide si la page n'a pas
     * d'adresse par langue (pages de l'application, connexion, etc.).
     *
     * @return array<string, string>
     */
    public static function alternatives(Request $request): array
    {
        $route = $request->route();

        if ($route === null || ! array_key_exists('locale', $route->parameters())) {
            return [];
        }

        $adresses = [];

        foreach (self::disponibles() as $langue) {
            $adresses[$langue] = route($route->getName(), array_merge($route->parameters(), ['locale' => $langue]));
        }

        return $adresses;
    }

    /** Remplace le préfixe de langue d'un chemin (« /fr/contact » → « /en/contact »). Les autres chemins sont inchangés. */
    public static function remplacerPrefixe(string $chemin, string $langue): string
    {
        return preg_replace('#^/('.implode('|', self::disponibles()).')(?=/|\?|$)#', '/'.$langue, $chemin, 1) ?? $chemin;
    }

    /** Cookie de choix de langue, valable un an. */
    public static function cookie(string $langue): Cookie
    {
        return cookie(self::COOKIE, $langue, self::DUREE_COOKIE);
    }
}

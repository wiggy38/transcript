<?php

namespace App\Support;

/**
 * Adresse de retour après paiement : seulement un chemin de l'application (« /historique?q=… »).
 * Une adresse externe ou un chemin qui commence par « // » ou « /\ » est refusé, pour qu'un lien
 * piégé ne renvoie jamais l'utilisateur ailleurs après un paiement.
 */
final class CheminLocal
{
    /** Chemin et requête d'une adresse de cette application (« /historique?q=… »), pour l'adresse de retour. */
    public static function depuisUrl(string $url): string
    {
        $chemin = (string) parse_url($url, PHP_URL_PATH);
        $requete = (string) parse_url($url, PHP_URL_QUERY);

        return ($chemin === '' ? '/' : $chemin).($requete !== '' ? '?'.$requete : '');
    }

    /** Adresse absolue sur cette application, ou null si la valeur n'est pas un chemin sûr. */
    public static function valider(mixed $valeur): ?string
    {
        if (! is_string($valeur) || strlen($valeur) > 2048) {
            return null;
        }

        if (! preg_match('#^/(?![/\\\\])[^\x00-\x1F\x7F\\\\]*$#', $valeur)) {
            return null;
        }

        return url($valeur);
    }
}

<?php

namespace App\Services\Video;

use App\Jobs\RecupererApercu;
use Illuminate\Support\Facades\Cache;

/**
 * État de l'aperçu d'un lien (titre, durée, miniature, chaîne) pendant la saisie.
 *
 * Les métadonnées sont lues par la file (RecupererApercu) : aucune requête web n'attend
 * yt-dlp. L'écran interroge lire() jusqu'à ce que l'état ne soit plus « en_cours ».
 * L'état reste en cache, sans donnée personnelle : il ne dépend pas de l'utilisateur.
 */
final class ApercuLien
{
    public const EN_COURS = 'en_cours';

    public const PRET = 'pret';

    public const ERREUR = 'erreur';

    public static function cle(string $url): string
    {
        return 'apercu:'.sha1($url);
    }

    /** @return array{etat: string, donnees?: array<string, mixed>, motif?: string}|null */
    public function lire(string $url): ?array
    {
        return Cache::get(self::cle($url));
    }

    /** Marque l'aperçu en cours et le confie à la file. */
    public function demarrer(string $url): void
    {
        $this->enregistrer($url, ['etat' => self::EN_COURS]);

        RecupererApercu::dispatch($url);
    }

    /** @param  array{etat: string, donnees?: array<string, mixed>, motif?: string}  $etat */
    public function enregistrer(string $url, array $etat): void
    {
        Cache::put(self::cle($url), $etat, (int) config('voxia.apercu.cache_secondes'));
    }
}

<?php

namespace App\Services\Video;

use App\Enums\MotifEchec;

/**
 * Traduit la sortie de yt-dlp en motif clair. Seules les lignes « ERROR: » sont
 * analysées pour les erreurs, afin qu'un titre de vidéo ne fausse pas le motif.
 * Par défaut : erreur technique (aucun décompte de quota).
 */
final class TraducteurErreurYtdlp
{
    /** @var array<int, array{0: MotifEchec, 1: list<string>}> Ordre de priorité. */
    private const MOTIFS = [
        [MotifEchec::LienInvalide, [
            '/Unsupported URL/i',
            '/Incomplete YouTube ID/i',
            '/Invalid URL/i',
            '/is not a valid URL/i',
        ]],
        [MotifEchec::VideoPrivee, [
            '/Private video/i',
            '/This video is private/i',
        ]],
        [MotifEchec::VideoSupprimee, [
            '/has been removed/i',
            '/has been deleted/i',
            '/no longer available/i',
            '/removed by the uploader/i',
            '/account (has been )?terminated/i',
        ]],
        [MotifEchec::BloqueeRegion, [
            '/in your country/i',
            '/geo[- ]?restricted/i',
        ]],
        [MotifEchec::LiveEnCours, [
            '/live event will begin/i',
            '/live (stream|event) (is )?(currently )?(in progress|live)/i',
        ]],
    ];

    /** Filtre « !is_live » de Voxia : yt-dlp ignore le média et ne produit aucun fichier. */
    private const FILTRE_LIVE = '/does not pass filter \(!is_live\)/i';

    public static function depuisSortie(string $sortie): MotifEchec
    {
        $lignes = preg_split('/\R/', $sortie) ?: [];
        $erreurs = implode("\n", array_filter(
            $lignes,
            fn (string $ligne): bool => str_starts_with(ltrim($ligne), 'ERROR:'),
        ));

        foreach (self::MOTIFS as [$motif, $expressions]) {
            foreach ($expressions as $expression) {
                if (preg_match($expression, $erreurs) === 1) {
                    return $motif;
                }
            }
        }

        if (preg_match(self::FILTRE_LIVE, $sortie) === 1) {
            return MotifEchec::LiveEnCours;
        }

        return MotifEchec::ErreurTechnique;
    }
}

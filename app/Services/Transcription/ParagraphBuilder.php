<?php

namespace App\Services\Transcription;

use App\Data\MotTranscrit;
use App\Data\Paragraphe;

/**
 * Regroupe les mots transcrits en paragraphes lisibles. Séparé du moteur.
 *
 * Coupures : pause égale ou supérieure à voxia.paragraphes.pause_ms ; fin de phrase
 * une fois atteint voxia.paragraphes.fin_de_phrase_min_mots ; longueur maximale
 * voxia.paragraphes.max_mots. Le texte n'est pas réécrit : les mots sont joints
 * tels quels, la ponctuation et les majuscules restent celles du moteur.
 */
final class ParagraphBuilder
{
    /** Ponctuation de fin de phrase, suivie éventuellement de guillemets ou de parenthèses. */
    private const FIN_DE_PHRASE = '/[.!?…]["\'»”’)\]]*$/u';

    /** Ponctuation seule, rattachée au mot précédent. */
    private const PONCTUATION_SEULE = '/^[.,;:!?…)»”"\]]+$/u';

    /**
     * @param  list<MotTranscrit>  $mots
     * @return list<Paragraphe>
     */
    public function construire(array $mots): array
    {
        $mots = array_values($mots);
        $pauseMs = (int) config('voxia.paragraphes.pause_ms');
        $minMots = (int) config('voxia.paragraphes.fin_de_phrase_min_mots');
        $maxMots = (int) config('voxia.paragraphes.max_mots');

        $paragraphes = [];
        $courant = [];
        $total = count($mots);

        foreach ($mots as $index => $mot) {
            $courant[] = $mot;

            if ($index === $total - 1) {
                break;
            }

            $nombre = count($courant);
            $pauseLongue = $mots[$index + 1]->debutMs - $mot->finMs >= $pauseMs;
            $finDePhrase = $nombre >= $minMots && preg_match(self::FIN_DE_PHRASE, $mot->texte) === 1;
            $tropLong = $nombre >= $maxMots;

            if ($pauseLongue || $finDePhrase || $tropLong) {
                $paragraphes[] = $this->fermer($courant);
                $courant = [];
            }
        }

        if ($courant !== []) {
            $paragraphes[] = $this->fermer($courant);
        }

        return $paragraphes;
    }

    /** @param  non-empty-list<MotTranscrit>  $mots */
    private function fermer(array $mots): Paragraphe
    {
        $texte = '';

        foreach ($mots as $mot) {
            $texte = $texte === ''
                ? $mot->texte
                : (preg_match(self::PONCTUATION_SEULE, $mot->texte) === 1
                    ? $texte.$mot->texte
                    : $texte.' '.$mot->texte);
        }

        return new Paragraphe(
            debutMs: $mots[0]->debutMs,
            finMs: $mots[array_key_last($mots)]->finMs,
            texte: $texte,
        );
    }
}

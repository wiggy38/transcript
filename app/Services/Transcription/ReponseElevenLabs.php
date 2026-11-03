<?php

namespace App\Services\Transcription;

use App\Data\MotTranscrit;
use App\Data\TranscriptionBrute;
use UnexpectedValueException;

/**
 * Traduit la réponse brute de ElevenLabs Scribe v2 (POST /v1/speech-to-text) en
 * objet neutre. Seuls les éléments de type « word » deviennent des mots : les
 * « spacing » (espaces) et « audio_event » (rires, musique) sont écartés.
 *
 * Les secondes du fournisseur deviennent des millisecondes, arrondies.
 */
final class ReponseElevenLabs
{
    /**
     * @param  array<string, mixed>  $json
     *
     * @throws UnexpectedValueException si la réponse n'a pas la forme attendue
     */
    public static function versTranscription(array $json, int $dureeSecondes): TranscriptionBrute
    {
        if (! isset($json['words']) || ! is_array($json['words'])) {
            throw new UnexpectedValueException('réponse sans liste de mots');
        }

        $mots = [];

        foreach ($json['words'] as $element) {
            if (! is_array($element) || ($element['type'] ?? 'word') !== 'word') {
                continue;
            }

            $texte = trim((string) ($element['text'] ?? ''));

            if ($texte === '') {
                continue;
            }

            if (! is_numeric($element['start'] ?? null) || ! is_numeric($element['end'] ?? null)) {
                throw new UnexpectedValueException('mot sans horodatage');
            }

            $mots[] = new MotTranscrit(
                texte: $texte,
                debutMs: (int) round((float) $element['start'] * 1000),
                finMs: (int) round((float) $element['end'] * 1000),
            );
        }

        $langue = is_string($json['language_code'] ?? null) && $json['language_code'] !== ''
            ? strtolower($json['language_code'])
            : null;

        return new TranscriptionBrute(
            langueDetectee: $langue,
            mots: $mots,
            texte: trim((string) ($json['text'] ?? '')),
            coutEstimeUsd: CoutTranscription::estimer($dureeSecondes),
        );
    }
}

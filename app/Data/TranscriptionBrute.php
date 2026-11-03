<?php

namespace App\Data;

/**
 * Résultat neutre d'une transcription. Les mots ne contiennent que de vrais mots
 * (ni espaces ni événements sonores), dans l'ordre de la vidéo.
 */
final readonly class TranscriptionBrute
{
    /**
     * @param  list<MotTranscrit>  $mots
     */
    public function __construct(
        /** Code de langue détecté (ex. « fr », « en ») ; nul si le moteur ne le renvoie pas. */
        public ?string $langueDetectee,
        public array $mots,
        /** Texte complet, ponctuation et majuscules telles que rendues par le moteur. */
        public string $texte,
        /** Coût estimé en dollars US, arrondi à 4 décimales. */
        public float $coutEstimeUsd,
    ) {}
}

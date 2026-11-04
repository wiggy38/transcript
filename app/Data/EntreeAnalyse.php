<?php

namespace App\Data;

/** Entrée de l'analyse : paragraphes (contexte) et mots horodatés (ce qui est découpé). */
final readonly class EntreeAnalyse
{
    /**
     * @param  list<Paragraphe>  $paragraphes  Dans l'ordre de la vidéo.
     * @param  list<MotTranscrit>  $mots  Dans l'ordre de la vidéo.
     */
    public function __construct(
        public array $paragraphes,
        public array $mots,
        public int $dureeMs,
    ) {}
}

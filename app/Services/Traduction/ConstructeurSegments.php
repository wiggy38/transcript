<?php

namespace App\Services\Traduction;

use App\Data\MotTranscrit;
use App\Models\Transcription;
use App\Services\Exports\GenerateurSousTitres;

/**
 * Segments d'une transcription, figés au lancement d'une traduction : ce sont les sous-titres de F4
 * (1 à 7 secondes, 42 caractères par ligne, 2 lignes). Chaque traduction garde cette liste dans
 * ai_outputs.contenu, avec ses horodatages : une modification du découpage ne décale donc rien.
 */
final class ConstructeurSegments
{
    public function __construct(private readonly GenerateurSousTitres $sousTitres) {}

    /**
     * @return list<array{id: string, debut_ms: int, fin_ms: int, texte_original: string}>
     */
    public function depuis(Transcription $transcription): array
    {
        $mots = $transcription->words()->orderBy('position')->get()
            ->map(fn ($mot) => new MotTranscrit((string) $mot->texte, (int) $mot->debut_ms, (int) $mot->fin_ms))
            ->values()->all();

        $segments = [];

        foreach ($this->sousTitres->construire($mots) as $index => $sousTitre) {
            $segments[] = [
                'id' => sprintf('s%04d', $index + 1),
                'debut_ms' => $sousTitre->debutMs,
                'fin_ms' => $sousTitre->finMs,
                'texte_original' => implode(' ', $sousTitre->lignes),
            ];
        }

        return $segments;
    }
}

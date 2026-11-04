<?php

namespace App\Data;

use App\Models\Transcription;
use Carbon\CarbonInterface;

/**
 * Contenu d'une transcription, indépendant d'Eloquent : les exporteurs n'y touchent pas.
 */
final readonly class ContenuTranscription
{
    /**
     * @param  list<Paragraphe>  $paragraphes  Dans l'ordre de la vidéo.
     * @param  list<MotTranscrit>  $mots  Dans l'ordre de la vidéo, avec horodatage au mot.
     */
    public function __construct(
        public string $titre,
        public ?string $urlSource,
        public ?CarbonInterface $dateTranscription,
        public array $paragraphes,
        public array $mots,
    ) {}

    public static function depuis(Transcription $transcription): self
    {
        return new self(
            titre: (string) ($transcription->titre ?? ''),
            urlSource: $transcription->url_origine,
            dateTranscription: $transcription->date_transcription ?? $transcription->date_termine ?? $transcription->created_at,
            paragraphes: $transcription->paragraphs()->get()
                ->map(fn ($p) => new Paragraphe((int) $p->debut_ms, (int) $p->fin_ms, (string) $p->texte))
                ->values()->all(),
            mots: $transcription->words()->get()
                ->map(fn ($m) => new MotTranscrit((string) $m->texte, (int) $m->debut_ms, (int) $m->fin_ms))
                ->values()->all(),
        );
    }
}

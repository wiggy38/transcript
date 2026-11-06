<?php

namespace App\Services\Traduction;

use App\Data\ContenuTranscription;
use App\Data\Paragraphe;
use App\Data\SousTitre;
use App\Enums\FormatExport;
use App\Models\AiOutput;
use App\Services\Exports\Exporteurs;
use App\Support\Horodatage;

/**
 * Exports d'une traduction (V2). TXT, PDF, DOCX et JSON reprennent les exporteurs de la transcription, appliqués
 * aux segments traduits. SRT et VTT suivent les règles de F4 : une traduction trop longue est redécoupée dans
 * les bornes de temps de son segment d'origine (DecoupeSousTitres).
 */
final class ExporteurTraduction
{
    public function __construct(
        private readonly Exporteurs $exporteurs,
        private readonly DecoupeSousTitres $decoupe,
    ) {}

    public function exporter(AiOutput $sortie, FormatExport $format, bool $avecHorodatages = true): string
    {
        $contenu = $this->contenu($sortie);

        return match ($format) {
            FormatExport::Srt => $this->srt($sortie),
            FormatExport::Vtt => $this->vtt($sortie),
            default => $this->exporteurs->pour($format)->exporter($contenu, $avecHorodatages),
        };
    }

    /** Contenu commun aux exporteurs : un paragraphe par segment traduit. Titre et lien viennent de la transcription. */
    public function contenu(AiOutput $sortie): ContenuTranscription
    {
        $transcription = $sortie->transcription;

        return new ContenuTranscription(
            titre: (string) ($transcription->titre ?? ''),
            urlSource: $transcription->url_origine,
            dateTranscription: $transcription->date_transcription ?? $transcription->date_termine ?? $transcription->created_at,
            paragraphes: array_map(
                fn (array $segment) => new Paragraphe((int) $segment['debut_ms'], (int) $segment['fin_ms'], (string) $segment['texte']),
                $this->segments($sortie),
            ),
            mots: [],
        );
    }

    private function srt(AiOutput $sortie): string
    {
        $blocs = [];

        foreach ($this->sousTitres($sortie) as $index => $sousTitre) {
            $blocs[] = ($index + 1)."\n"
                .Horodatage::srt($sousTitre->debutMs).' --> '.Horodatage::srt($sousTitre->finMs)."\n"
                .implode("\n", $sousTitre->lignes);
        }

        return $blocs === [] ? '' : implode("\n\n", $blocs)."\n";
    }

    private function vtt(AiOutput $sortie): string
    {
        $fichier = "WEBVTT\n\n";

        foreach ($this->sousTitres($sortie) as $sousTitre) {
            $fichier .= Horodatage::vtt($sousTitre->debutMs).' --> '.Horodatage::vtt($sousTitre->finMs)."\n"
                .implode("\n", $sousTitre->lignes)."\n\n";
        }

        return $fichier;
    }

    /** @return list<SousTitre> */
    private function sousTitres(AiOutput $sortie): array
    {
        $sousTitres = [];

        foreach ($this->segments($sortie) as $segment) {
            array_push($sousTitres, ...$this->decoupe->sousTitres((int) $segment['debut_ms'], (int) $segment['fin_ms'], (string) $segment['texte']));
        }

        return $sousTitres;
    }

    /** @return list<array<string, mixed>> */
    private function segments(AiOutput $sortie): array
    {
        return $sortie->contenu['segments'] ?? [];
    }
}

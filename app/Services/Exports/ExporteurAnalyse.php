<?php

namespace App\Services\Exports;

use App\Enums\FormatExport;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use App\Support\Horodatage;
use InvalidArgumentException;

/**
 * Export d'une analyse de script (F7) en TXT ou PDF. Une partie absente y figure comme « non détectée ».
 */
final class ExporteurAnalyse
{
    public function __construct(private readonly RendeurPdf $pdf) {}

    public function exporter(ScriptAnalysis $analyse, FormatExport $format): string
    {
        $parties = $this->parties($analyse);
        $titre = (string) ($analyse->transcription->titre ?? '');
        $titre = $titre !== '' ? $titre : __('analyse.pdf.titre');

        return match ($format) {
            FormatExport::Txt => $this->txt($parties),
            FormatExport::Pdf => $this->pdf->rendre($titre, view('exports.analyse-pdf', [
                'titre' => $titre,
                'libelleAnalyse' => __('analyse.pdf.titre'),
                'parties' => $parties,
            ])->render()),
            default => throw new InvalidArgumentException("Format d'analyse non géré : {$format->value}"),
        };
    }

    /**
     * Les parties dans l'ordre de la vidéo, avec leur libellé et leur horodatage lisible.
     *
     * @return list<array{libelle: string, detectee: bool, horodatage: ?string, texte: ?string}>
     */
    public function parties(ScriptAnalysis $analyse): array
    {
        return $analyse->parts->map(fn (ScriptPart $partie) => [
            'libelle' => __('analyse.types.'.$partie->type->value),
            'detectee' => $partie->detectee,
            'horodatage' => $partie->detectee
                ? Horodatage::format($partie->debut_ms).' → '.Horodatage::format($partie->fin_ms)
                : null,
            'texte' => $partie->texte,
        ])->values()->all();
    }

    /** @param  list<array{libelle: string, detectee: bool, horodatage: ?string, texte: ?string}>  $parties */
    private function txt(array $parties): string
    {
        $blocs = array_map(function (array $partie) {
            $entete = $partie['libelle'].($partie['detectee'] ? ' ['.$partie['horodatage'].']' : ' : '.__('analyse.non_detectee'));

            return $partie['detectee'] ? $entete."\n".$partie['texte'] : $entete;
        }, $parties);

        return implode("\n\n", $blocs)."\n";
    }
}

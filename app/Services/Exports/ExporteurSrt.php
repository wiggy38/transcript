<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Support\Horodatage;

/** SRT : numéroté, horodatages 00:00:00,000 (cahier F4). */
final class ExporteurSrt implements Exporteur
{
    public function __construct(private readonly GenerateurSousTitres $generateur) {}

    public function format(): FormatExport
    {
        return FormatExport::Srt;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $blocs = [];

        foreach ($this->generateur->construire($contenu->mots) as $index => $sousTitre) {
            $blocs[] = ($index + 1)."\n"
                .Horodatage::srt($sousTitre->debutMs).' --> '.Horodatage::srt($sousTitre->finMs)."\n"
                .implode("\n", $sousTitre->lignes);
        }

        return $blocs === [] ? '' : implode("\n\n", $blocs)."\n";
    }
}

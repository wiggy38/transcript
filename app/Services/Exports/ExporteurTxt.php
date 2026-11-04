<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Support\Horodatage;

/** Texte brut : un paragraphe par bloc, avec ou sans horodatage « [mm:ss] ». */
final class ExporteurTxt implements Exporteur
{
    public function format(): FormatExport
    {
        return FormatExport::Txt;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $blocs = array_map(
            fn ($paragraphe) => $avecHorodatages
                ? '['.Horodatage::format($paragraphe->debutMs).'] '.$paragraphe->texte
                : $paragraphe->texte,
            $contenu->paragraphes,
        );

        return $blocs === [] ? '' : implode("\n\n", $blocs)."\n";
    }
}

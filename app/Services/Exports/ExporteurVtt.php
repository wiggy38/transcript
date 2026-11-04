<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Support\Horodatage;

/** WebVTT : en-tête WEBVTT, horodatages 00:00:00.000 (cahier F4). */
final class ExporteurVtt implements Exporteur
{
    public function __construct(private readonly GenerateurSousTitres $generateur) {}

    public function format(): FormatExport
    {
        return FormatExport::Vtt;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $fichier = "WEBVTT\n\n";

        foreach ($this->generateur->construire($contenu->mots) as $sousTitre) {
            $fichier .= Horodatage::vtt($sousTitre->debutMs).' --> '.Horodatage::vtt($sousTitre->finMs)."\n"
                .implode("\n", $sousTitre->lignes)."\n\n";
        }

        return $fichier;
    }
}

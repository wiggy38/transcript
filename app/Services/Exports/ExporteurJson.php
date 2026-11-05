<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;

/**
 * JSON : titre, lien source, date, et la liste des segments horodatés (début et fin en millisecondes,
 * texte). Réservé au Business. Toujours avec horodatages : le paramètre « avecHorodatages » ne s'applique pas.
 */
final class ExporteurJson implements Exporteur
{
    public function format(): FormatExport
    {
        return FormatExport::Json;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $segments = array_map(
            fn ($paragraphe) => [
                'debut_ms' => $paragraphe->debutMs,
                'fin_ms' => $paragraphe->finMs,
                'texte' => $paragraphe->texte,
            ],
            $contenu->paragraphes,
        );

        return json_encode([
            'titre' => $contenu->titre,
            'source' => $contenu->urlSource,
            'date' => $contenu->dateTranscription?->toIso8601String(),
            'segments' => $segments,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    }
}

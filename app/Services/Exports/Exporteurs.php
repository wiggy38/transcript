<?php

namespace App\Services\Exports;

use App\Enums\FormatExport;

/** Choisit l'exporteur d'un format. */
final class Exporteurs
{
    public function pour(FormatExport $format): Exporteur
    {
        return match ($format) {
            FormatExport::Txt => app(ExporteurTxt::class),
            FormatExport::Pdf => app(ExporteurPdf::class),
            FormatExport::Srt => app(ExporteurSrt::class),
            FormatExport::Vtt => app(ExporteurVtt::class),
            FormatExport::Docx => app(ExporteurDocx::class),
            FormatExport::Json => app(ExporteurJson::class),
        };
    }
}

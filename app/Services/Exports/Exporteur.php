<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;

/**
 * Interface commune des exports (cahier F4). Chaque format a sa classe.
 */
interface Exporteur
{
    public function format(): FormatExport;

    /**
     * Contenu du fichier, en UTF-8. Les SRT et VTT ont toujours leurs horodatages :
     * le paramètre les ignore pour ces formats.
     */
    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string;
}

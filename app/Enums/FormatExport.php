<?php

namespace App\Enums;

/** Formats d'export (cahier F4). Les droits par plan sont dans config/voxia.php (« exports »). */
enum FormatExport: string
{
    case Txt = 'txt';
    case Pdf = 'pdf';
    case Srt = 'srt';
    case Vtt = 'vtt';
    /** Word, avec horodatages, titre, lien source et date : réservé au Business. */
    case Docx = 'docx';
    /** Segments horodatés en JSON : réservé au Business. */
    case Json = 'json';

    public function libelle(): string
    {
        return strtoupper($this->value);
    }

    public function extension(): string
    {
        return $this->value;
    }

    public function typeMime(): string
    {
        return match ($this) {
            self::Txt => 'text/plain; charset=UTF-8',
            self::Pdf => 'application/pdf',
            self::Srt => 'application/x-subrip; charset=UTF-8',
            self::Vtt => 'text/vtt; charset=UTF-8',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            self::Json => 'application/json; charset=UTF-8',
        };
    }
}

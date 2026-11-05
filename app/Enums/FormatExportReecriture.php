<?php

namespace App\Enums;

/** Exports d'une réécriture : TXT (Pro et Business), Markdown et DOCX (Business). Droits : voxia.plans.*.exports_reecriture. */
enum FormatExportReecriture: string
{
    case Txt = 'txt';
    case Markdown = 'md';
    case Docx = 'docx';

    public function libelle(): string
    {
        return match ($this) {
            self::Txt => 'TXT',
            self::Markdown => 'Markdown',
            self::Docx => 'DOCX',
        };
    }

    public function typeMime(): string
    {
        return match ($this) {
            self::Txt => 'text/plain; charset=UTF-8',
            self::Markdown => 'text/markdown; charset=UTF-8',
            self::Docx => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }
}

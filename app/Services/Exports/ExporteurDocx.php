<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Support\Horodatage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

/**
 * DOCX (Word) : titre, lien source, date de transcription, puis un paragraphe par bloc avec son
 * horodatage « [mm:ss] ». Réservé au Business (cahier V2, décisions du 5 novembre 2026).
 * Le texte est écrit tel quel en UTF-8 : accents et écritures non latines sont conservés.
 */
final class ExporteurDocx implements Exporteur
{
    public function format(): FormatExport
    {
        return FormatExport::Docx;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $document = new PhpWord;
        $section = $document->addSection();

        $section->addTitle($contenu->titre !== '' ? $contenu->titre : 'Transcription', 1);
        $section->addText('Source : '.($contenu->urlSource ?? '—'));
        $section->addText('Date : '.($contenu->dateTranscription?->format('d/m/Y') ?? '—'));

        foreach ($contenu->paragraphes as $paragraphe) {
            $texte = $avecHorodatages
                ? '['.Horodatage::format($paragraphe->debutMs).'] '.$paragraphe->texte
                : $paragraphe->texte;

            $section->addText($texte);
        }

        // PhpWord écrit dans un fichier, pas en mémoire : on le relit puis on le supprime.
        $chemin = tempnam(sys_get_temp_dir(), 'voxia-docx-');

        if ($chemin === false) {
            throw new RuntimeException('Fichier temporaire impossible pour le DOCX.');
        }

        try {
            IOFactory::createWriter($document, 'Word2007')->save($chemin);

            return (string) file_get_contents($chemin);
        } finally {
            @unlink($chemin);
        }
    }
}

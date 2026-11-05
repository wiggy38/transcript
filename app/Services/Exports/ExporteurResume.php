<?php

namespace App\Services\Exports;

use App\Enums\FormatExport;
use App\Models\AiOutput;
use App\Support\Horodatage;
use InvalidArgumentException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Export du résumé : TXT, PDF (Pro et Business) et DOCX (Business). Les horodatages sont ceux de la
 * transcription (« [mm:ss] »), jamais ceux du modèle.
 */
final class ExporteurResume
{
    public function __construct(private readonly RendeurPdf $pdf) {}

    public function exporter(AiOutput $sortie, FormatExport $format): string
    {
        $contenu = $sortie->contenu ?? [];
        $titre = (string) ($sortie->transcription->titre ?? '');
        $titre = $titre !== '' ? $titre : __('resume.export.titre_defaut');

        return match ($format) {
            FormatExport::Txt => $this->txt($contenu),
            FormatExport::Pdf => $this->pdf->rendre($titre, view('exports.resume-pdf', [
                'titre' => $titre,
                'libelles' => __('resume.export'),
                'contenu' => $contenu,
            ])->render()),
            FormatExport::Docx => $this->docx($titre, $contenu),
            default => throw new InvalidArgumentException("Format de résumé non géré : {$format->value}"),
        };
    }

    /**
     * Texte brut du résumé, utilisé pour la copie et l'export TXT.
     *
     * @param  array<string, mixed>  $contenu
     */
    public function txt(array $contenu): string
    {
        $libelles = __('resume.export');
        $blocs = [$libelles['resume']."\n".($contenu['resume'] ?? '')];

        if (($contenu['points'] ?? []) !== []) {
            $lignes = array_map(fn (array $point) => '['.Horodatage::format((int) $point['debut_ms']).'] '.$point['texte'], $contenu['points']);
            $blocs[] = $libelles['points']."\n".implode("\n", $lignes);
        }

        if (($contenu['chapitres'] ?? []) !== []) {
            $lignes = array_map(fn (array $chapitre) => '['.Horodatage::format((int) $chapitre['debut_ms']).'] '.$chapitre['titre']."\n".$chapitre['resume'], $contenu['chapitres']);
            $blocs[] = $libelles['chapitres']."\n".implode("\n\n", $lignes);
        }

        return implode("\n\n", $blocs)."\n";
    }

    /** @param  array<string, mixed>  $contenu */
    private function docx(string $titre, array $contenu): string
    {
        $libelles = __('resume.export');
        $document = new PhpWord;
        $section = $document->addSection();

        $section->addTitle($titre, 1);
        $section->addTitle($libelles['resume'], 2);
        $section->addText((string) ($contenu['resume'] ?? ''));

        if (($contenu['points'] ?? []) !== []) {
            $section->addTitle($libelles['points'], 2);

            foreach ($contenu['points'] as $point) {
                $section->addText('['.Horodatage::format((int) $point['debut_ms']).'] '.$point['texte']);
            }
        }

        if (($contenu['chapitres'] ?? []) !== []) {
            $section->addTitle($libelles['chapitres'], 2);

            foreach ($contenu['chapitres'] as $chapitre) {
                $section->addText('['.Horodatage::format((int) $chapitre['debut_ms']).'] '.$chapitre['titre'], ['bold' => true]);
                $section->addText($chapitre['resume']);
            }
        }

        // PhpWord n'écrit que dans un fichier : on passe par un fichier temporaire, supprimé aussitôt.
        $chemin = tempnam(sys_get_temp_dir(), 'resume');
        IOFactory::createWriter($document, 'Word2007')->save($chemin);
        $contenu = (string) file_get_contents($chemin);
        unlink($chemin);

        return $contenu;
    }
}

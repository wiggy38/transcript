<?php

namespace App\Services\Exports;

use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;

/**
 * Mise en PDF commune aux exports de transcription (F4) et d'analyse de script (F7) : A4, marges de
 * voxia.exports.pdf, polices Geist et Geist Mono intégrées (resources/fonts), Noto Sans en secours,
 * pied de page avec la pagination.
 */
final class RendeurPdf
{
    /** Polices déclarées auprès de mPDF : nom CSS => fichiers par variante (resources/fonts). */
    private const POLICES = [
        'geist' => ['R' => 'Geist-Regular.ttf', 'B' => 'Geist-SemiBold.ttf'],
        'geistmono' => ['R' => 'GeistMono-Regular.ttf'],
        'notosans' => ['R' => 'NotoSans-Regular.ttf'],
    ];

    /** @param  string  $html  Page HTML déjà échappée. */
    public function rendre(string $titre, string $html): string
    {
        $dossierTemporaire = storage_path('app/mpdf');
        File::ensureDirectoryExists($dossierTemporaire);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => (int) config('voxia.exports.pdf.marge_mm'),
            'margin_right' => (int) config('voxia.exports.pdf.marge_mm'),
            'margin_top' => (int) config('voxia.exports.pdf.marge_mm'),
            'margin_bottom' => (int) config('voxia.exports.pdf.marge_mm'),
            'tempDir' => $dossierTemporaire,
            'fontDir' => [resource_path('fonts')],
            'fontdata' => self::POLICES,
            'default_font' => 'geist',
            'useSubstitutions' => true,
            'backupSubsFont' => ['notosans'],
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        $mpdf->SetTitle($titre);
        $mpdf->SetAuthor('Voxia');
        $mpdf->SetCreator('Voxia');
        $mpdf->SetFooter('<div style="font-family: geistmono; font-size: 8pt; text-align: right; color: #6B6A65;">'
            .__('exports.pdf.page', ['page' => '{PAGENO}']).'</div>');

        $mpdf->WriteHTML($html);

        return $mpdf->OutputBinaryData();
    }
}

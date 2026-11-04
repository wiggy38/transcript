<?php

namespace App\Services\Exports;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Support\Horodatage;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;

/**
 * PDF sobre : titre, lien source, date de transcription, puis le texte avec ou sans horodatages.
 *
 * Polices (resources/fonts) : Geist pour le texte, Geist Mono pour les horodatages. Geist couvre
 * l'alphabet latin, le grec et le cyrillique ; les caractères qu'elle n'a pas sont pris dans Noto Sans.
 * Les polices sont intégrées au fichier. L'arabe et les écritures CJK ne sont pas couverts : leur
 * mise en forme demande une prise en charge que mPDF n'offre pas avec ces polices (voir avancement).
 */
final class ExporteurPdf implements Exporteur
{
    /** Polices déclarées auprès de mPDF : nom CSS => fichiers par variante (resources/fonts). */
    private const POLICES = [
        'geist' => ['R' => 'Geist-Regular.ttf', 'B' => 'Geist-SemiBold.ttf'],
        'geistmono' => ['R' => 'GeistMono-Regular.ttf'],
        'notosans' => ['R' => 'NotoSans-Regular.ttf'],
    ];

    public function format(): FormatExport
    {
        return FormatExport::Pdf;
    }

    public function exporter(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        $titre = $contenu->titre !== '' ? $contenu->titre : __('exports.pdf.titre_defaut');
        $html = $this->html($contenu, $avecHorodatages);

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

    /**
     * Page HTML que mPDF met en PDF. Public pour les tests : le PDF compressé ne laisse pas lire son texte.
     */
    public function html(ContenuTranscription $contenu, bool $avecHorodatages = true): string
    {
        return view('exports.transcription-pdf', [
            'titre' => $this->echappe($contenu->titre !== '' ? $contenu->titre : __('exports.pdf.titre_defaut')),
            'urlSource' => $contenu->urlSource !== null ? $this->echappe($contenu->urlSource) : null,
            'libelleSource' => __('exports.pdf.source'),
            'libelleFichier' => __('exports.pdf.fichier'),
            'libelleDate' => __('exports.pdf.transcrit_le'),
            'date' => $contenu->dateTranscription?->locale(app()->getLocale())->isoFormat('LL'),
            'avecHorodatages' => $avecHorodatages,
            'paragraphes' => array_map(fn ($p) => [
                'heure' => Horodatage::format($p->debutMs),
                'texte' => $this->echappe($p->texte),
            ], $contenu->paragraphes),
        ])->render();
    }

    /** Échappe le texte pour le HTML (le moteur PDF lit l'UTF-8 tel quel). */
    private function echappe(string $texte): string
    {
        return htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\FormatExportReecriture;
use App\Enums\FormatReecriture;
use App\Enums\StatutSortieIa;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use App\Services\Exports\ExporteurReecriture;
use App\Services\Plans\PlanFeatures;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement d'une réécriture terminée en TXT (Pro et Business), Markdown ou DOCX (Business).
 * Le format d'export doit être dans le plan du compte (403 sinon). Une transcription d'un autre compte,
 * ou sans réécriture terminée dans ce format, répond 404.
 */
class ExportReecritureController extends Controller
{
    public function __invoke(
        Request $request,
        Transcription $transcription,
        FormatReecriture $format,
        FormatExportReecriture $export,
        PlanFeatures $plans,
        ExporteurReecriture $exporteur,
    ): Response {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        $sortie = $transcription->aiOutputs()
            ->where('fonctionnalite', 'reecriture')
            ->where('format', $format->value)
            ->first();
        abort_unless($sortie !== null && $sortie->statut === StatutSortieIa::Termine, 404);
        abort_unless($plans->peutExporterReecriture($request->user(), $export), 403);

        $contenu = $exporteur->exporter($sortie, $export);
        $nom = NomFichierAudio::depuisTitre(
            $exporteur->titre($format, $sortie->contenu ?? []).' - '.(string) $transcription->titre,
            $export->value,
        );
        $repli = preg_replace('/[^\x20-\x7E]/', '_', $nom);

        return response($contenu, 200, [
            'Content-Type' => $export->typeMime(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, $repli),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

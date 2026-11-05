<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\FormatExport;
use App\Enums\StatutSortieIa;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use App\Services\Exports\ExporteurResume;
use App\Services\Plans\PlanFeatures;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement du résumé en TXT, PDF ou DOCX. Le format doit être dans le plan du compte (403 sinon).
 * Une transcription d'un autre compte, ou sans résumé terminé, répond 404.
 */
class ExportResumeController extends Controller
{
    public function __invoke(
        Request $request,
        Transcription $transcription,
        FormatExport $format,
        PlanFeatures $plans,
        ExporteurResume $exporteur,
    ): Response {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        $sortie = $transcription->aiOutputs()->where('fonctionnalite', 'resume')->first();
        abort_unless($sortie !== null && $sortie->statut === StatutSortieIa::Termine, 404);
        abort_unless(in_array($format, [FormatExport::Txt, FormatExport::Pdf, FormatExport::Docx], true), 404);
        abort_unless($plans->peutExporter($request->user(), $format), 403);

        $contenu = $exporteur->exporter($sortie, $format);
        $nom = NomFichierAudio::depuisTitre('Résumé - '.(string) $transcription->titre, $format->extension());
        $repli = preg_replace('/[^\x20-\x7E]/', '_', $nom);

        return response($contenu, 200, [
            'Content-Type' => $format->typeMime(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, $repli),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

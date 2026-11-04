<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\FormatExport;
use App\Enums\StatutAnalyseScript;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use App\Services\Exports\ExporteurAnalyse;
use App\Services\Plans\PlanFeatures;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement de l'analyse de script (F7) en TXT ou PDF. Le format doit être dans le plan du
 * compte (403 sinon). Une transcription d'un autre compte, ou sans analyse terminée, répond 404.
 */
class ExportAnalyseController extends Controller
{
    public function __invoke(
        Request $request,
        Transcription $transcription,
        FormatExport $format,
        PlanFeatures $plans,
        ExporteurAnalyse $exporteur,
    ): Response {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        $analyse = $transcription->scriptAnalysis;
        abort_unless($analyse !== null && $analyse->statut === StatutAnalyseScript::Termine, 404);
        abort_unless($plans->peutExporter($request->user(), $format), 403);

        $contenu = $exporteur->exporter($analyse, $format);
        $nom = NomFichierAudio::depuisTitre('Analyse - '.(string) $transcription->titre, $format->extension());
        $repli = preg_replace('/[^\x20-\x7E]/', '_', $nom);

        return response($contenu, 200, [
            'Content-Type' => $format->typeMime(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, $repli),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

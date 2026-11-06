<?php

namespace App\Http\Controllers\Comptes;

use App\Enums\FormatExport;
use App\Enums\StatutSortieIa;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use App\Services\Plans\PlanFeatures;
use App\Services\Traduction\ExporteurTraduction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement d'une traduction terminée : TXT, PDF, SRT, VTT (tous les plans), DOCX et JSON (Business).
 * Option « horodatages=0 » pour le TXT et le PDF. Une transcription d'un autre compte, ou sans traduction
 * terminée dans cette langue, répond 404 ; un format hors du plan répond 403.
 */
class ExportTraductionController extends Controller
{
    public function __invoke(
        Request $request,
        Transcription $transcription,
        string $langue,
        FormatExport $export,
        PlanFeatures $plans,
        ExporteurTraduction $exporteur,
    ): Response {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        abort_unless(in_array($langue, (array) config('voxia.ia.traduction.langues'), true), 404);

        $sortie = $transcription->aiOutputs()
            ->where('fonctionnalite', 'traduction')
            ->where('format', $langue)
            ->first();
        abort_unless($sortie !== null && $sortie->statut === StatutSortieIa::Termine, 404);
        abort_unless($plans->peutExporter($request->user(), $export), 403);

        $contenu = $exporteur->exporter($sortie, $export, $request->boolean('horodatages', true));
        $nom = NomFichierAudio::depuisTitre(
            (string) ($transcription->titre ?: 'transcription').'.'.$langue,
            $export->extension(),
        );
        $repli = preg_replace('/[^\x20-\x7E]/', '_', $nom);

        return response($contenu, 200, [
            'Content-Type' => $export->typeMime(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, $repli),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Comptes;

use App\Data\ContenuTranscription;
use App\Enums\FormatExport;
use App\Enums\StatutTranscription;
use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use App\Services\Exports\Exporteurs;
use App\Services\Plans\PlanFeatures;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement d'un export (cahier F4). Le format doit être dans le plan du compte ; un format
 * verrouillé répond 403. Une transcription d'un autre compte, ou pas encore terminée, répond 404.
 */
class ExportController extends Controller
{
    public function __invoke(
        Request $request,
        Transcription $transcription,
        FormatExport $format,
        PlanFeatures $plans,
        Exporteurs $exporteurs,
    ): Response {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        abort_unless($transcription->statut === StatutTranscription::Termine, 404);
        abort_unless($plans->peutExporter($request->user(), $format), 403);

        $exporteur = $exporteurs->pour($format);
        $contenu = $exporteur->exporter(
            ContenuTranscription::depuis($transcription),
            $request->boolean('horodatages', true),
        );

        $nom = NomFichierAudio::depuisTitre((string) $transcription->titre, $format->extension());
        $repli = preg_replace('/[^\x20-\x7E]/', '_', $nom);

        return response($contenu, 200, [
            'Content-Type' => $format->typeMime(),
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, $repli),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

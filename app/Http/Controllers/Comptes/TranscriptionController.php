<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Historique\SuppressionTranscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Page d'une transcription. Le lancement passe par le composant Livewire NouvelleTranscription ;
 * cette page ne sert qu'à suivre le statut. Une transcription d'un autre compte renvoie 404.
 */
class TranscriptionController extends Controller
{
    /**
     * Ouvre la transcription. « ?t= » (millisecondes) place la lecture et le texte sur le
     * paragraphe de cet horodatage, comme le font les liens de la recherche.
     */
    public function show(Request $request, Transcription $transcription): View
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        // Position bornée à la durée de la transcription : un « ?t= » hors texte n'envoie nulle part.
        $t = $request->query('t');
        $dureeMs = (int) $transcription->duree_secondes * 1000;
        $positionInitiale = is_string($t) && ctype_digit($t) ? min((int) $t, $dureeMs) : null;

        return view('app.transcription', [
            'transcription' => $transcription,
            'positionInitiale' => $positionInitiale,
        ]);
    }

    /** Page de confirmation avant suppression. Rien n'est supprimé avant la validation. */
    public function confirmerSuppression(Request $request, Transcription $transcription): View
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        return view('app.supprimer-transcription', ['transcription' => $transcription]);
    }

    /** Suppression de la transcription, de son texte et de son MP3 (cahier F5). */
    public function supprimer(Request $request, Transcription $transcription, SuppressionTranscription $suppression): RedirectResponse
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        $suppression->supprimerUne($transcription);

        return redirect()->route('historique')->with('statut', 'transcription-supprimee');
    }

    /**
     * MP3 pour le lecteur audio (TikTok et fichiers). Sur un disque local, le fichier part avec ses requêtes d'intervalle
     * (Range) : sans elles, le déplacement dans le son n'est pas précis. Sur un disque distant,
     * le navigateur reçoit une adresse temporaire.
     */
    public function audio(Request $request, Transcription $transcription): BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        abort_if($transcription->mp3_chemin === null, 404);

        $disque = (string) config('voxia.stockage.disque');
        $stockage = Storage::disk($disque);
        abort_unless($stockage->exists($transcription->mp3_chemin), 404);

        if (config("filesystems.disks.{$disque}.driver") === 'local') {
            return response()->file($stockage->path($transcription->mp3_chemin), [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, max-age=600',
            ]);
        }

        return redirect()->away($stockage->temporaryUrl($transcription->mp3_chemin, now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes'))));
    }
}

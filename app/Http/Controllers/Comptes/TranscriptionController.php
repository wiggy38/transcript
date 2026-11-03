<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Page d'une transcription. Le lancement passe par le composant Livewire NouvelleTranscription ;
 * cette page ne sert qu'à suivre le statut. Une transcription d'un autre compte renvoie 404.
 */
class TranscriptionController extends Controller
{
    public function show(Request $request, Transcription $transcription): View
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);

        return view('app.transcription', ['transcription' => $transcription]);
    }

    /**
     * MP3 pour le lecteur audio. Sur un disque local, le fichier part avec ses requêtes d'intervalle
     * (Range) : sans elles, le déplacement dans le son n'est pas précis. Sur un disque distant,
     * le navigateur reçoit une adresse temporaire.
     */
    public function audio(Request $request, Transcription $transcription): BinaryFileResponse|RedirectResponse
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

        return redirect()->away($stockage->temporaryUrl($transcription->mp3_chemin, now()->addMinutes(15)));
    }
}

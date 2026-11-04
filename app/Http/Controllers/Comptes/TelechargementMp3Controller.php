<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use App\Services\Audio\NomFichierAudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Téléchargement du MP3 (cahier F3). La route n'est atteignable qu'avec un lien signé et non expiré
 * (middleware « signed ») ; le propriétaire est vérifié en plus, si bien qu'un lien ne sert jamais
 * le fichier d'un autre compte. Le MP3 n'a aucune adresse publique : sur un disque local, il part
 * par la réponse ; sur un disque distant, le navigateur reçoit une adresse temporaire.
 * Un MP3 supprimé par la conservation (mp3_chemin vide) ou absent du disque répond 404.
 */
class TelechargementMp3Controller extends Controller
{
    public function __invoke(Request $request, Transcription $transcription): BinaryFileResponse|RedirectResponse
    {
        abort_unless($transcription->user_id === $request->user()->getKey(), 404);
        abort_if($transcription->mp3_chemin === null, 404);

        $disque = (string) config('voxia.stockage.disque');
        $stockage = Storage::disk($disque);
        abort_unless($stockage->exists($transcription->mp3_chemin), 404);

        // Le fichier porte le nom calculé à partir du titre (cahier F3, NomFichierAudio).
        $nom = NomFichierAudio::depuisTitre((string) $transcription->titre, 'mp3');
        $duree = (int) config('voxia.telechargement.duree_lien_minutes');

        if (config("filesystems.disks.{$disque}.driver") === 'local') {
            return response()->download($stockage->path($transcription->mp3_chemin), $nom, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return redirect()->away($stockage->temporaryUrl($transcription->mp3_chemin, now()->addMinutes($duree), [
            'ResponseContentDisposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $nom, preg_replace('/[^\x20-\x7E]/', '_', $nom)),
        ]));
    }
}

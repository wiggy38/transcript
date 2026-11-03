<?php

namespace App\Http\Controllers\Comptes;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
}

<?php

namespace App\Livewire;

use App\Enums\StatutTranscription;
use App\Models\Transcription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Statut d'une transcription. Interroge le serveur tant que le traitement n'est pas fini ;
 * l'utilisateur peut fermer la page, le traitement continue dans la file.
 */
class SuiviTranscription extends Component
{
    public int $transcriptionId;

    public function mount(Transcription $transcription): void
    {
        $this->transcriptionId = $transcription->getKey();
    }

    public function render()
    {
        // Relu à chaque interrogation, et toujours filtré sur le compte connecté.
        $transcription = Auth::user()->transcriptions()->findOrFail($this->transcriptionId);

        return view('livewire.suivi-transcription', [
            'transcription' => $transcription,
            'enCours' => ! in_array($transcription->statut, [StatutTranscription::Termine, StatutTranscription::Echec], true),
        ]);
    }
}

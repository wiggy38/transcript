<?php

namespace App\Livewire;

use App\Enums\StatutTranscription;
use App\Models\Transcription;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Statut d'une transcription. Interroge le serveur tant que le traitement n'est pas fini ;
 * l'utilisateur peut fermer la page, le traitement continue dans la file. Une fois terminée,
 * la transcription s'affiche : son texte est chargé ici, filtré sur le compte connecté.
 */
class SuiviTranscription extends Component
{
    public int $transcriptionId;

    /** Position de départ de la lecture, en millisecondes (lien depuis la recherche). */
    public ?int $positionInitiale = null;

    public function mount(Transcription $transcription, ?int $positionInitiale = null): void
    {
        $this->transcriptionId = $transcription->getKey();
        $this->positionInitiale = $positionInitiale;
    }

    public function render()
    {
        // Relu à chaque interrogation, et toujours filtré sur le compte connecté.
        $transcription = Auth::user()->transcriptions()->findOrFail($this->transcriptionId);
        $termine = $transcription->statut === StatutTranscription::Termine;

        if ($termine) {
            $transcription->load('paragraphs');
        }

        return view('livewire.suivi-transcription', [
            'transcription' => $transcription,
            'enCours' => ! $termine && $transcription->statut !== StatutTranscription::Echec,
            'termine' => $termine,
            'positionInitiale' => $this->positionInitiale,
        ]);
    }
}

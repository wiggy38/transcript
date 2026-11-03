<?php

namespace App\Jobs;

use App\Enums\MotifEchec;
use App\Models\Transcription;
use App\Services\Traitement\ChaineTranscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Une tâche par vidéo (cahier §8). Pas de nouvelle tentative au niveau de la tâche :
 * chaque étape réessaie déjà ce qui est récupérable, et relancer la tâche
 * retéléchargerait tout. Le délai total est un garde-fou : une tâche bloquée finit
 * en échec, jamais en attente.
 */
final class ProcessTranscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $maxExceptions = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    /** @param  string|null  $cheminFichier  Fichier local, pour une source « fichier ». */
    public function __construct(
        public int $transcriptionId,
        public ?string $cheminFichier = null,
    ) {
        $this->timeout = (int) config('voxia.traitement.delai_total_secondes');
    }

    public function handle(ChaineTranscription $chaine): void
    {
        $transcription = Transcription::find($this->transcriptionId);

        // Supprimée entre la mise en file et l'exécution : rien à faire.
        if ($transcription === null) {
            return;
        }

        $chaine->executer($transcription, $this->cheminFichier);
    }

    /** Appelé quand Laravel abandonne la tâche (délai total, arrêt du worker). */
    public function failed(?Throwable $exception): void
    {
        $transcription = Transcription::find($this->transcriptionId);

        if ($transcription !== null) {
            app(ChaineTranscription::class)->interrompre($transcription, MotifEchec::ErreurTechnique);
        }
    }
}

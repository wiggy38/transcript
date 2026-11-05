<?php

namespace App\Jobs;

use App\Enums\StatutSortieIa;
use App\Models\AiOutput;
use App\Services\Resume\ServiceResume;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Résumé d'une transcription en tâche de fond (V2). Une tâche, pas de nouvelle tentative : les relances
 * sont dans ServiceResume. Le délai est un garde-fou : une génération bloquée finit en échec, sans décompte.
 */
final class GenererResume implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $maxExceptions = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    public function __construct(public int $aiOutputId)
    {
        $this->timeout = (int) config('voxia.ia.resume.job_timeout_seconds');
    }

    public function handle(ServiceResume $service): void
    {
        $sortie = AiOutput::with('transcription')->find($this->aiOutputId);

        // Supprimée, ou déjà finie entre la mise en file et l'exécution : rien à faire.
        if ($sortie === null || $sortie->statut === StatutSortieIa::Termine) {
            return;
        }

        $service->executer($sortie);
    }

    /** Délai dépassé ou arrêt du worker : la sortie finit en échec, jamais en attente. */
    public function failed(?Throwable $exception): void
    {
        $sortie = AiOutput::find($this->aiOutputId);

        if ($sortie !== null && $sortie->statut->enCours()) {
            $sortie->update([
                'statut' => StatutSortieIa::Echec,
                'date_echec' => now(),
            ]);
        }
    }
}

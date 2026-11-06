<?php

namespace App\Jobs;

use App\Enums\StatutSortieIa;
use App\Models\AiOutput;
use App\Services\Traduction\ServiceTraduction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Traduction d'une transcription dans une langue, en tâche de fond (V2). Une tâche par langue, sans nouvelle
 * tentative : les relances sont dans ServiceTraduction. Le délai est un garde-fou : une traduction bloquée
 * finit en échec, sans décompte.
 */
final class GenererTraduction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $maxExceptions = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    public function __construct(public int $aiOutputId)
    {
        $this->timeout = (int) config('voxia.ia.traduction.job_timeout_seconds');
    }

    public function handle(ServiceTraduction $service): void
    {
        $sortie = AiOutput::with('transcription')->find($this->aiOutputId);

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

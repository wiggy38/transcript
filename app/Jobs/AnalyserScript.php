<?php

namespace App\Jobs;

use App\Enums\StatutAnalyseScript;
use App\Models\ScriptAnalysis;
use App\Services\Analyse\ServiceAnalyseScript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Analyse de script (F7) en tâche de fond. Une tâche, pas de nouvelle tentative : les relances
 * sont dans ServiceAnalyseScript. Le délai est un garde-fou : une analyse bloquée finit en échec.
 */
final class AnalyserScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $maxExceptions = 1;

    public bool $failOnTimeout = true;

    public int $timeout;

    public function __construct(public int $scriptAnalysisId)
    {
        $this->timeout = (int) config('voxia.script_analysis.job_timeout_seconds');
    }

    public function handle(ServiceAnalyseScript $service): void
    {
        $analyse = ScriptAnalysis::with('transcription')->find($this->scriptAnalysisId);

        // Supprimée ou déjà terminée entre la mise en file et l'exécution : rien à faire.
        if ($analyse === null || $analyse->statut === StatutAnalyseScript::Termine) {
            return;
        }

        $service->executer($analyse);
    }

    /** Délai dépassé ou arrêt du worker : l'analyse finit en échec, jamais en attente. */
    public function failed(?Throwable $exception): void
    {
        $analyse = ScriptAnalysis::find($this->scriptAnalysisId);

        if ($analyse !== null && ! in_array($analyse->statut, [StatutAnalyseScript::Termine, StatutAnalyseScript::Echec], true)) {
            $analyse->update([
                'statut' => StatutAnalyseScript::Echec,
                'date_echec' => now(),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\EvenementPaiement;
use App\Services\Paiement\TraiterEvenementPaiement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Traitement d'un webhook de paiement, en file. Une relecture ne refait rien : sans « traite_at »
 * la tâche traite, sinon elle s'arrête. Une erreur réessaie avec délai (aperçu encore en cours, base indisponible).
 */
class ProcesserEvenementPaiement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /** @var list<int> délais entre tentatives, en secondes */
    public array $backoff = [30, 120, 300, 900, 1800];

    public function __construct(public int $evenementId) {}

    public function handle(TraiterEvenementPaiement $traitement): void
    {
        $evenement = EvenementPaiement::query()->with('user')->find($this->evenementId);

        if ($evenement === null || $evenement->traite_at !== null) {
            return;
        }

        $traitement->traiter($evenement);

        $evenement->forceFill([
            'traite_at' => now(),
            'tentatives' => $evenement->tentatives + 1,
            'erreur' => null,
        ])->save();
    }

    public function failed(?Throwable $erreur): void
    {
        EvenementPaiement::query()->whereKey($this->evenementId)->update([
            'erreur' => Str::limit($erreur?->getMessage() ?? 'échec inconnu', 500),
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Paiement\SynchroniserPlan;
use Illuminate\Console\Command;

/**
 * Recalcule le plan des comptes payants : un Pack expiré ou un abonnement arrivé à son terme ramène
 * le compte au plan qui lui reste (Pro, Pack, ou Gratuit). Lancée chaque jour (routes/console.php).
 */
class RecalculerPlans extends Command
{
    protected $signature = 'voxia:recalculer-plans';

    protected $description = 'Recalcule le plan des comptes Pro et Pack (Packs expirés, abonnements terminés)';

    public function handle(SynchroniserPlan $plans): int
    {
        $comptes = 0;

        User::query()->whereIn('plan', ['pro', 'pack'])->chunkById(200, function ($lot) use ($plans, &$comptes) {
            foreach ($lot as $utilisateur) {
                $plans->pour($utilisateur);
                $comptes++;
            }
        });

        $this->info("{$comptes} compte(s) vérifié(s).");

        return self::SUCCESS;
    }
}

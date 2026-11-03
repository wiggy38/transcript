<?php

namespace App\Services\Comptes;

use App\Enums\Plan;
use App\Models\User;

/**
 * Limite de comptes gratuits créés depuis une même adresse IP (cahier §5, abus du plan
 * gratuit). Vaut pour l'inscription par email comme pour la connexion Google.
 */
final class LimiteComptesGratuits
{
    /** Vrai si une nouvelle création gratuite depuis cette IP dépasse la limite. */
    public function atteinte(?string $ip): bool
    {
        $limite = config('voxia.comptes.gratuits_par_ip');

        if ($limite === null || $ip === null) {
            return false;
        }

        $nombre = User::query()
            ->where('inscription_ip', $ip)
            ->where('plan', Plan::Free)
            ->count();

        return $nombre >= (int) $limite;
    }
}

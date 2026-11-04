<?php

namespace App\Services\Paiement;

use App\Enums\Plan;
use App\Models\PackAchat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Calcule le plan d'un compte à partir de son état chez le prestataire et de ses Packs, puis l'écrit
 * sur users.plan. Règle :
 * - abonnement Pro valide (actif, ou résilié mais encore dans la période payée) : Pro ;
 * - sinon, un Pack non expiré : Pack ;
 * - sinon : Gratuit.
 *
 * L'ancre du cycle (users.abonnement_debut_at) est posée au passage à Pro : elle sert au décompte
 * mensuel (QuotaService::debutCycleAnniversaire). Un changement de formule ne la déplace pas.
 *
 * Les compteurs de quotas ne sont pas stockés : ils se recalculent à partir des décomptes, donc
 * un changement de plan les met à jour sans traitement supplémentaire.
 */
final class SynchroniserPlan
{
    public function pour(User $utilisateur): Plan
    {
        return DB::transaction(function () use ($utilisateur) {
            $compte = User::query()->whereKey($utilisateur->getKey())->lockForUpdate()->firstOrFail();

            $abonnement = $compte->subscription((string) config('voxia.paiement.type_abonnement_pro'));
            $cible = match (true) {
                $compte->subscribed((string) config('voxia.paiement.type_abonnement_pro')) => Plan::Pro,
                $this->aUnPackValide($compte) => Plan::Pack,
                default => Plan::Free,
            };

            $attributs = [];

            if ($cible !== $compte->plan) {
                $attributs['plan'] = $cible;
            }

            if ($cible === Plan::Pro && ($compte->plan !== Plan::Pro || $compte->abonnement_debut_at === null)) {
                $attributs['abonnement_debut_at'] = $abonnement?->created_at ?? now();
            }

            if ($attributs !== []) {
                $compte->forceFill($attributs)->save();
            }

            $utilisateur->setRawAttributes($compte->getAttributes());

            return $cible;
        });
    }

    private function aUnPackValide(User $compte): bool
    {
        return PackAchat::query()
            ->where('user_id', $compte->getKey())
            ->where('expire_at', '>', now())
            ->exists();
    }
}

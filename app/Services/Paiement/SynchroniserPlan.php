<?php

namespace App\Services\Paiement;

use App\Enums\Plan;
use App\Models\PackAchat;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Calcule le plan d'un compte à partir de son état chez le prestataire et de ses Packs, puis l'écrit
 * sur users.plan. Règle, dans l'ordre :
 * - abonnement payant valide dont le prix est une formule Business : Business ;
 * - abonnement payant valide, autre prix : Pro ;
 * - sinon, un Pack non expiré : Pack ;
 * - sinon : Gratuit.
 *
 * Un compte n'a qu'un abonnement : passer de Pro à Business (ou l'inverse) change seulement son prix.
 * Le plan suit donc le prix porté par l'abonnement, qui change au moment où Paddle le change
 * (immédiatement pour Pro → Business, en fin de période pour Business → Pro).
 *
 * L'ancre du cycle (users.abonnement_debut_at) est posée au passage d'un état non payant à un état
 * payant. Un changement entre Pro et Business ne la déplace pas.
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
            $payant = $compte->subscribed((string) config('voxia.paiement.type_abonnement_pro'));

            $cible = match (true) {
                $payant && $this->estFormuleBusiness($abonnement) => Plan::Business,
                $payant => Plan::Pro,
                $this->aUnPackValide($compte) => Plan::Pack,
                default => Plan::Free,
            };

            $attributs = [];

            if ($cible !== $compte->plan) {
                $attributs['plan'] = $cible;
            }

            $etaitPayant = in_array($compte->plan, [Plan::Pro, Plan::Business], true);
            $estPayant = in_array($cible, [Plan::Pro, Plan::Business], true);

            if ($estPayant && (! $etaitPayant || $compte->abonnement_debut_at === null)) {
                $attributs['abonnement_debut_at'] = $abonnement?->created_at ?? now();
            }

            if ($attributs !== []) {
                $compte->forceFill($attributs)->save();
            }

            $utilisateur->setRawAttributes($compte->getAttributes());

            return $cible;
        });
    }

    /**
     * Vrai si un article de l'abonnement porte un identifiant de prix Business (mensuel ou annuel).
     * Un abonnement sans article (tests, webhook en retard) est compté comme Pro.
     */
    public function estFormuleBusiness(?object $abonnement): bool
    {
        if ($abonnement === null) {
            return false;
        }

        $prixBusiness = array_filter([
            config('voxia.paiement.prix.business_mensuel'),
            config('voxia.paiement.prix.business_annuel'),
        ], fn ($prix) => is_string($prix) && $prix !== '');

        return $abonnement->items()->whereIn('price_id', $prixBusiness)->exists();
    }

    private function aUnPackValide(User $compte): bool
    {
        return PackAchat::query()
            ->where('user_id', $compte->getKey())
            ->where('expire_at', '>', now())
            ->exists();
    }
}

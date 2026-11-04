<?php

namespace App\Services\Quota;

use App\Enums\Plan;
use App\Models\PackAchat;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Enregistre un achat de Pack, appelé par le paiement (étape 4). Le Pack vaut 10 h, valables
 * 6 mois à partir de l'achat. Un compte Gratuit passe au plan Pack ; un abonné Pro garde son plan
 * et consomme d'abord son volume Pro (voir QuotaService::consommerTranscription).
 */
final class AchatPack
{
    public function __construct(private readonly PlanFeatures $droits) {}

    public function enregistrer(User $utilisateur, ?CarbonInterface $date = null): PackAchat
    {
        if (! $this->droits->estVendable(Plan::Pack)) {
            throw new LogicException('Le Pack n’est pas vendable.');
        }

        return DB::transaction(function () use ($utilisateur, $date) {
            $compte = User::query()->whereKey($utilisateur->getKey())->lockForUpdate()->firstOrFail();

            $achete = CarbonImmutable::instance($date ?? now())->utc();

            $pack = PackAchat::create([
                'user_id' => $compte->getKey(),
                'secondes' => $this->droits->secondesPack(),
                'achete_at' => $achete,
                'expire_at' => $achete->addMonthsNoOverflow($this->droits->validitePackMois()),
            ]);

            // Le plan enregistré, pas planDe() : l'achat ne doit jamais rétrograder un abonné Pro.
            if ($compte->plan === Plan::Free) {
                $compte->plan = Plan::Pack;
                $compte->save();
            }

            return $pack;
        });
    }
}

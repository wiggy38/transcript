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
 * Enregistre un achat de Pack, appelé par le traitement du paiement (webhook transaction.completed).
 * Le Pack vaut 10 h, valables 6 mois à partir de l'achat. Un compte Gratuit passe au plan Pack ;
 * un abonné Pro garde son plan et consomme d'abord son volume Pro (voir QuotaService::consommerTranscription).
 *
 * Idempotent : une transaction Paddle déjà enregistrée renvoie le Pack existant, sans en créer un second.
 */
final class AchatPack
{
    public function __construct(private readonly PlanFeatures $droits) {}

    public function enregistrer(User $utilisateur, ?CarbonInterface $date = null, ?string $idTransaction = null): PackAchat
    {
        if (! $this->droits->estVendable(Plan::Pack)) {
            throw new LogicException('Le Pack n’est pas vendable.');
        }

        return DB::transaction(function () use ($utilisateur, $date, $idTransaction) {
            $compte = User::query()->whereKey($utilisateur->getKey())->lockForUpdate()->firstOrFail();

            if ($idTransaction !== null) {
                $existant = PackAchat::query()->where('paddle_transaction_id', $idTransaction)->first();

                if ($existant !== null) {
                    return $existant;
                }
            }

            $achete = CarbonImmutable::instance($date ?? now())->utc();

            $pack = PackAchat::create([
                'user_id' => $compte->getKey(),
                'secondes' => $this->droits->secondesPack(),
                'achete_at' => $achete,
                'expire_at' => $achete->addMonthsNoOverflow($this->droits->validitePackMois()),
                'paddle_transaction_id' => $idTransaction,
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

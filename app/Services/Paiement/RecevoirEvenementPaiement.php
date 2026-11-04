<?php

namespace App\Services\Paiement;

use App\Jobs\ProcesserEvenementPaiement;
use App\Models\EvenementPaiement;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;
use Laravel\Paddle\Customer;

/**
 * Enregistre un webhook reçu et le met en file. Idempotent : un « event_id » déjà connu ne crée
 * pas de ligne et ne relance pas le traitement, même reçu deux fois ou en même temps (contrainte
 * d'unicité en base).
 */
final class RecevoirEvenementPaiement
{
    /**
     * @param  array<string, mixed>  $charge  corps du webhook, tel que reçu
     * @return ?EvenementPaiement la ligne créée, ou null si l'événement était déjà connu
     *
     * @throws InvalidArgumentException événement sans identifiant ni type
     */
    public function enregistrer(array $charge): ?EvenementPaiement
    {
        $identifiant = (string) ($charge['event_id'] ?? '');
        $type = (string) ($charge['event_type'] ?? '');

        if ($identifiant === '' || $type === '') {
            throw new InvalidArgumentException('Webhook sans identifiant ni type.');
        }

        if (EvenementPaiement::query()->where('event_id', $identifiant)->exists()) {
            return null;
        }

        try {
            $evenement = EvenementPaiement::create([
                'event_id' => $identifiant,
                'type' => $type,
                'user_id' => $this->utilisateurDuClient($charge),
                'charge' => $charge,
                'recu_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Réception simultanée du même événement : l'autre requête l'a déjà enregistré.
            return null;
        }

        ProcesserEvenementPaiement::dispatch($evenement->getKey());

        return $evenement;
    }

    /** @param  array<string, mixed>  $charge */
    private function utilisateurDuClient(array $charge): ?int
    {
        $client = (string) ($charge['data']['customer_id'] ?? '');

        if ($client === '') {
            return null;
        }

        $identifiant = Customer::query()->where('paddle_id', $client)->value('billable_id');

        return $identifiant === null ? null : (int) $identifiant;
    }
}

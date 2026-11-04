<?php

namespace App\Services\Paiement;

use App\Models\EvenementPaiement;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Quota\AchatPack;
use App\Services\Traitement\LancerTranscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Subscription;

/**
 * Effets d'un webhook sur le compte : statut et articles de l'abonnement, plan et ancre du cycle,
 * achat de Pack, relance d'un aperçu. Chaque effet est idempotent (il ne change rien si l'état voulu
 * est déjà là), donc une relecture du même événement ne fait jamais deux fois le même effet.
 *
 * Types gérés :
 * - subscription.* (créé, modifié, résilié, impayé…) : statut et articles recopiés du webhook, puis plan ;
 * - transaction.completed : achat de Pack si le prix est celui du Pack, puis plan, puis relance d'aperçu ;
 * - transaction.payment_failed : journalisé ; l'accès suit l'état de l'abonnement (impayé, puis résilié).
 */
final class TraiterEvenementPaiement
{
    public function __construct(
        private readonly SynchroniserPlan $plans,
        private readonly AchatPack $packs,
        private readonly LancerTranscription $lancement,
    ) {}

    public function traiter(EvenementPaiement $evenement): void
    {
        $utilisateur = $evenement->user;

        if ($utilisateur === null) {
            // Client inconnu de l'application : rien à mettre à jour, l'événement reste journalisé.
            Log::info('paiement: événement sans compte connu', ['event_id' => $evenement->event_id, 'type' => $evenement->type]);

            return;
        }

        $donnees = (array) ($evenement->charge['data'] ?? []);

        if (str_starts_with($evenement->type, 'subscription.')) {
            $this->synchroniserAbonnement($donnees);

            $this->plans->pour($utilisateur);

            return;
        }

        if ($evenement->type === 'transaction.completed') {
            $this->transactionReglee($utilisateur, $donnees);

            return;
        }

        if ($evenement->type === 'transaction.payment_failed') {
            Log::warning('paiement: échec signalé', ['utilisateur' => $utilisateur->getKey(), 'transaction' => $donnees['id'] ?? null]);
        }
    }

    /**
     * Cashier ne recopie pas tout le contenu de chaque webhook : le statut (subscription.past_due, par
     * exemple) et les articles (changement de formule) sont donc écrits depuis le webhook, sur
     * l'abonnement local s'il existe. Les articles sont remplacés, pas ajoutés : une relecture reste sans effet.
     *
     * @param  array<string, mixed>  $donnees
     */
    private function synchroniserAbonnement(array $donnees): void
    {
        $identifiant = $donnees['id'] ?? null;

        if (! is_string($identifiant)) {
            return;
        }

        $abonnement = Subscription::query()->where('paddle_id', $identifiant)->first();

        if ($abonnement === null) {
            return;
        }

        if (isset($donnees['status']) && is_string($donnees['status']) && $abonnement->status !== $donnees['status']) {
            $abonnement->forceFill(['status' => $donnees['status']])->save();
        }

        if (! isset($donnees['items']) || ! is_array($donnees['items'])) {
            return;
        }

        $abonnement->items()->delete();

        foreach ($donnees['items'] as $article) {
            $abonnement->items()->create([
                'product_id' => (string) ($article['price']['product_id'] ?? ''),
                'price_id' => (string) ($article['price']['id'] ?? ''),
                'status' => (string) ($article['status'] ?? 'active'),
                'quantity' => (int) ($article['quantity'] ?? 1),
            ]);
        }
    }

    /** @param  array<string, mixed>  $donnees */
    private function transactionReglee(User $utilisateur, array $donnees): void
    {
        if ($this->estAchatPack($donnees)) {
            $this->packs->enregistrer(
                $utilisateur,
                CarbonImmutable::parse((string) ($donnees['billed_at'] ?? now())),
                (string) $donnees['id'],
            );
        }

        // Le plan est recalculé avant la relance : la transcription complète se décompte au nouveau plan.
        $this->plans->pour($utilisateur);

        $apercu = $this->apercuARelancer($utilisateur, $donnees);

        if ($apercu !== null) {
            $this->lancement->relancerVersionComplete($apercu);
        }
    }

    /** @param  array<string, mixed>  $donnees */
    private function estAchatPack(array $donnees): bool
    {
        $prixPack = config('voxia.paiement.prix.pack');

        if (! is_string($prixPack) || $prixPack === '') {
            return false;
        }

        return collect($donnees['items'] ?? [])->contains(
            fn ($article) => ($article['price']['id'] ?? null) === $prixPack,
        );
    }

    /**
     * Aperçu transmis dans les données personnalisées du paiement, s'il appartient bien au compte.
     *
     * @param  array<string, mixed>  $donnees
     */
    private function apercuARelancer(User $utilisateur, array $donnees): ?Transcription
    {
        $identifiant = $donnees['custom_data']['transcription_id'] ?? null;

        if ($identifiant === null || $identifiant === '') {
            return null;
        }

        return Transcription::query()
            ->where('user_id', $utilisateur->getKey())
            ->whereKey((int) $identifiant)
            ->first();
    }
}

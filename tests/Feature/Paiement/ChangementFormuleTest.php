<?php

use App\Exceptions\PaiementIndisponible;
use App\Models\User;
use App\Services\Paiement\PaddleBillingProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Subscription;

// Changement de formule chez Paddle (étape 2) : prorata immédiat vers Business, retour au Pro à la fin de période.
uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'cashier.api_key' => 'cle_api_test',
        'voxia.paiement.prix.pro_mensuel' => 'pri_pro_mensuel',
        'voxia.paiement.prix.pro_annuel' => 'pri_pro_annuel',
        'voxia.paiement.prix.business_mensuel' => 'pri_business_mensuel',
        'voxia.paiement.prix.business_annuel' => 'pri_business_annuel',
    ]);

    // Réponse type de Paddle à un PATCH d'abonnement. Aucun appel réseau réel.
    Http::fake(['*' => Http::response(['data' => [
        'id' => 'sub_test',
        'status' => 'active',
        'items' => [['price' => ['id' => 'pri_pro_mensuel', 'product_id' => 'pro_test'], 'status' => 'active', 'quantity' => 1]],
    ]], 200)]);
});

/** Compte dont l'abonnement porte un prix donné (type « pro », comme Cashier). */
function changementAbonnement(string $prix): User
{
    $utilisateur = User::factory()->create(['email_verified_at' => now()]);

    $abonnement = Subscription::create([
        'billable_type' => User::class,
        'billable_id' => $utilisateur->getKey(),
        'type' => 'pro',
        'paddle_id' => 'sub_test',
        'status' => 'active',
    ]);

    $abonnement->items()->create([
        'product_id' => 'pro_test',
        'price_id' => $prix,
        'status' => 'active',
        'quantity' => 1,
    ]);

    return $utilisateur->refresh();
}

/** Corps JSON de la dernière requête PATCH envoyée à Paddle. */
function changementCorpsEnvoye(): array
{
    $requete = collect(Http::recorded())->map(fn ($paire) => $paire[0])->last();

    return json_decode($requete->body(), true, 512, JSON_THROW_ON_ERROR);
}

test('Pro mensuel vers Business mensuel : prorata immédiat, prix Business', function () {
    $utilisateur = changementAbonnement('pri_pro_mensuel');

    (new PaddleBillingProvider)->changerFormule($utilisateur, 'business_mensuel');

    $corps = changementCorpsEnvoye();
    expect($corps['proration_billing_mode'])->toBe('prorated_immediately')
        ->and($corps['items'][0]['price_id'])->toBe('pri_business_mensuel')
        ->and($corps)->not->toHaveKey('effective_from');
});

test('Pro annuel vers Business annuel : prorata immédiat', function () {
    $utilisateur = changementAbonnement('pri_pro_annuel');

    (new PaddleBillingProvider)->changerFormule($utilisateur, 'business_annuel');

    expect(changementCorpsEnvoye())->toMatchArray([
        'proration_billing_mode' => 'prorated_immediately',
        'items' => [['price_id' => 'pri_business_annuel', 'quantity' => 1]],
    ]);
});

test('Business mensuel vers annuel : immédiat, au prorata', function () {
    $utilisateur = changementAbonnement('pri_business_mensuel');

    (new PaddleBillingProvider)->changerFormule($utilisateur, 'business_annuel');

    expect(changementCorpsEnvoye()['proration_billing_mode'])->toBe('prorated_immediately');
});

test('Business vers Pro : effectif à la fin de la période, sans facture d\'écart', function () {
    $utilisateur = changementAbonnement('pri_business_mensuel');

    (new PaddleBillingProvider)->changerFormule($utilisateur, 'pro_mensuel');

    expect(changementCorpsEnvoye())->toMatchArray([
        'effective_from' => 'next_billing_period',
        'proration_billing_mode' => 'do_not_bill',
        'items' => [['price_id' => 'pri_pro_mensuel', 'quantity' => 1]],
    ]);
});

test('Pro mensuel vers annuel : reste immédiat, au prorata (comportement existant)', function () {
    $utilisateur = changementAbonnement('pri_pro_mensuel');

    (new PaddleBillingProvider)->changerFormule($utilisateur, 'pro_annuel');

    expect(changementCorpsEnvoye()['proration_billing_mode'])->toBe('prorated_immediately')
        ->and(changementCorpsEnvoye())->not->toHaveKey('effective_from');
});

test('sans abonnement payant : changement refusé, aucun appel à Paddle', function () {
    $utilisateur = User::factory()->create(['email_verified_at' => now()]);

    expect(fn () => (new PaddleBillingProvider)->changerFormule($utilisateur, 'business_mensuel'))
        ->toThrow(PaiementIndisponible::class);

    Http::assertNothingSent();
});

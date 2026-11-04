<?php

use App\Contracts\BillingProvider;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Enums\UniteConsommation;
use App\Exceptions\PaiementIndisponible;
use App\Jobs\ProcessTranscription;
use App\Models\EvenementPaiement;
use App\Models\PackAchat;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Paiement\PaddleBillingProvider;
use App\Services\Quota\QuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Subscription;

uses(RefreshDatabase::class);

const SECRET_WEBHOOK_TEST = 'whsec_voxia_test';

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC'));

    config([
        'cashier.webhook_secret' => SECRET_WEBHOOK_TEST,
        'cashier.client_side_token' => 'test_jeton_public',
        'voxia.paiement.prix.pro_mensuel' => 'pri_pro_mensuel',
        'voxia.paiement.prix.pro_annuel' => 'pri_pro_annuel',
        'voxia.paiement.prix.pack' => 'pri_pack',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

/** Compte vérifié, avec son client Paddle (sans lui, Cashier ne rattache pas les webhooks au compte). */
function paiementCompte(array $attributs = []): User
{
    $utilisateur = User::factory()->create(['email_verified_at' => now()] + $attributs);

    Customer::create([
        'billable_type' => User::class,
        'billable_id' => $utilisateur->getKey(),
        'paddle_id' => 'ctm_'.$utilisateur->getKey(),
        'name' => $utilisateur->name,
        'email' => $utilisateur->email,
    ]);

    return $utilisateur->refresh();
}

/** Enveloppe d'un webhook Paddle : identifiant d'événement unique, type, données. */
function paiementEvenement(string $type, array $donnees, ?string $identifiant = null): array
{
    return [
        'event_id' => $identifiant ?? 'evt_'.Str::random(12),
        'event_type' => $type,
        'occurred_at' => now()->toIso8601String(),
        'notification_id' => 'ntf_'.Str::random(8),
        'data' => $donnees,
    ];
}

/** Données d'un abonnement Pro chez Paddle. */
function paiementDonneesAbonnement(User $utilisateur, string $statut = 'active', string $prix = 'pri_pro_mensuel', ?array $changementPrevu = null, ?string $identifiant = null): array
{
    return [
        'id' => $identifiant ?? 'sub_'.$utilisateur->getKey(),
        'customer_id' => 'ctm_'.$utilisateur->getKey(),
        'status' => $statut,
        'custom_data' => ['subscription_type' => 'pro'],
        'items' => [[
            'price' => ['id' => $prix, 'product_id' => 'pro_test'],
            'status' => 'active',
            'quantity' => 1,
        ]],
        'canceled_at' => null,
        'scheduled_change' => $changementPrevu,
        'paused_at' => null,
    ];
}

/** Données d'une transaction réglée (achat, renouvellement). */
function paiementDonneesTransaction(User $utilisateur, string $prix, ?string $abonnement = null, array $donneesPerso = [], ?string $identifiant = null): array
{
    return [
        'id' => $identifiant ?? 'txn_'.Str::random(10),
        'customer_id' => 'ctm_'.$utilisateur->getKey(),
        'subscription_id' => $abonnement,
        'invoice_number' => 'INV-'.Str::random(6),
        'status' => 'completed',
        'currency_code' => 'USD',
        'billed_at' => '2026-11-04T10:00:00Z',
        'details' => ['totals' => ['total' => '1200', 'tax' => '0']],
        'items' => [['price' => ['id' => $prix, 'product_id' => 'pro_test'], 'quantity' => 1]],
        'custom_data' => $donneesPerso,
    ];
}

/**
 * Envoie un webhook signé, comme le fait Paddle : corps JSON, en-tête Paddle-Signature (HMAC-SHA256 de
 * « ts:corps », avec le secret de test). Aucun appel réseau.
 */
function paiementWebhook(array $evenement, ?string $secret = SECRET_WEBHOOK_TEST): TestResponse
{
    $corps = json_encode($evenement, JSON_THROW_ON_ERROR);
    $horodatage = time();
    $entete = "ts={$horodatage};h1=".hash_hmac('sha256', "{$horodatage}:{$corps}", (string) $secret);

    return test()->call('POST', '/paddle/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => $entete,
    ], $corps);
}

/** Abonnement Pro enregistré comme le fait le webhook subscription.created (Cashier). */
function paiementAbonnementPro(User $utilisateur, string $statut = 'active', string $prix = 'pri_pro_mensuel'): Subscription
{
    $abonnement = Subscription::create([
        'billable_type' => User::class,
        'billable_id' => $utilisateur->getKey(),
        'type' => 'pro',
        'paddle_id' => 'sub_'.$utilisateur->getKey(),
        'status' => $statut,
    ]);

    $abonnement->items()->create([
        'product_id' => 'pro_test',
        'price_id' => $prix,
        'status' => 'active',
        'quantity' => 1,
    ]);

    $utilisateur->forceFill(['plan' => Plan::Pro, 'abonnement_debut_at' => now()])->save();

    return $abonnement;
}

/** Aperçu partiel d'un lien, du compte gratuit : transcription terminée, marquée aperçu. */
function paiementApercu(User $utilisateur): Transcription
{
    return Transcription::factory()->for($utilisateur)->create([
        'statut' => StatutTranscription::Termine,
        'duree_secondes' => 1800,
        'est_apercu' => true,
        'titre' => 'Vidéo test',
    ]);
}

// ---------------------------------------------------------------------------------------------
// Webhooks : chaque cas, puis le même webhook reçu deux fois.
// ---------------------------------------------------------------------------------------------

test('abonnement créé : le plan passe à Pro et l\'ancre du cycle est posée', function () {
    $utilisateur = paiementCompte();

    $reponse = paiementWebhook(paiementEvenement('subscription.created', paiementDonneesAbonnement($utilisateur)));

    $reponse->assertOk();
    $utilisateur->refresh();

    expect($utilisateur->plan)->toBe(Plan::Pro)
        ->and($utilisateur->abonnement_debut_at)->not->toBeNull()
        ->and(Subscription::query()->where('paddle_id', 'sub_'.$utilisateur->getKey())->where('type', 'pro')->exists())->toBeTrue();
});

test('abonnement renouvelé : le plan reste Pro et aucun Pack n\'est créé', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);
    $ancre = now()->subMonth();
    $utilisateur->forceFill(['abonnement_debut_at' => $ancre])->save();

    $renouvellement = paiementDonneesTransaction($utilisateur, 'pri_pro_mensuel', 'sub_'.$utilisateur->getKey());
    paiementWebhook(paiementEvenement('transaction.completed', $renouvellement))->assertOk();

    $utilisateur->refresh();

    expect($utilisateur->plan)->toBe(Plan::Pro)
        ->and($utilisateur->abonnement_debut_at->toIso8601String())->toBe($ancre->toIso8601String())
        ->and(PackAchat::query()->count())->toBe(0);
});

test('abonnement modifié : passage au tarif annuel, Pro conservé', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);

    paiementWebhook(paiementEvenement('subscription.updated', paiementDonneesAbonnement($utilisateur, 'active', 'pri_pro_annuel')))->assertOk();

    $utilisateur->refresh();

    expect($utilisateur->plan)->toBe(Plan::Pro)
        ->and(Subscription::query()->where('paddle_id', 'sub_'.$utilisateur->getKey())->first()->items()->value('price_id'))->toBe('pri_pro_annuel');
});

test('résiliation : l\'accès Pro reste ouvert jusqu\'à la fin de la période, puis le plan revient au Gratuit', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);

    // Résiliation programmée : la date de fin est dans le futur, le statut reste « active ».
    $programmee = paiementDonneesAbonnement($utilisateur, 'active', 'pri_pro_mensuel', [
        'action' => 'cancel',
        'effective_at' => '2026-12-04T10:00:00Z',
    ]);
    paiementWebhook(paiementEvenement('subscription.updated', $programmee))->assertOk();

    expect($utilisateur->refresh()->plan)->toBe(Plan::Pro)
        ->and(app(QuotaService::class)->resume($utilisateur)['secondes_mois'])->toBe(30 * 3600);

    // Fin de période : Paddle envoie l'annulation effective.
    $annulee = paiementDonneesAbonnement($utilisateur, 'canceled');
    $annulee['canceled_at'] = '2026-12-04T10:00:00Z';
    paiementWebhook(paiementEvenement('subscription.canceled', $annulee))->assertOk();

    expect($utilisateur->refresh()->plan)->toBe(Plan::Free);
});

test('paiement échoué : événement traité, Pro conservé ; impayé signalé, accès Pro retiré (défaut Cashier)', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);

    paiementWebhook(paiementEvenement('transaction.payment_failed', paiementDonneesTransaction($utilisateur, 'pri_pro_mensuel', 'sub_'.$utilisateur->getKey())))->assertOk();

    expect(EvenementPaiement::query()->where('type', 'transaction.payment_failed')->value('traite_at'))->not->toBeNull()
        ->and($utilisateur->refresh()->plan)->toBe(Plan::Pro);

    // Paddle signale ensuite l'impayé de l'abonnement : le statut local le suit.
    $impayee = paiementDonneesAbonnement($utilisateur, 'past_due');
    paiementWebhook(paiementEvenement('subscription.past_due', $impayee))->assertOk();

    expect(Subscription::query()->where('paddle_id', 'sub_'.$utilisateur->getKey())->value('status'))->toBe('past_due')
        ->and($utilisateur->refresh()->plan)->toBe(Plan::Free);
});

test('achat du Pack : un Pack de 10 h est créé et le compte passe au plan Pack', function () {
    $utilisateur = paiementCompte();

    paiementWebhook(paiementEvenement('transaction.completed', paiementDonneesTransaction($utilisateur, 'pri_pack', null, [], 'txn_pack_1')))->assertOk();

    $pack = PackAchat::query()->where('user_id', $utilisateur->getKey())->sole();

    expect($pack->secondes)->toBe(10 * 3600)
        ->and($pack->paddle_transaction_id)->toBe('txn_pack_1')
        ->and($pack->expire_at->toDateString())->toBe('2027-05-04')
        ->and($utilisateur->refresh()->plan)->toBe(Plan::Pack);
});

test('même webhook reçu deux fois : un seul effet, la réponse reste identique', function () {
    Queue::fake([ProcessTranscription::class]);

    $utilisateur = paiementCompte();
    $evenement = paiementEvenement('transaction.completed', paiementDonneesTransaction($utilisateur, 'pri_pack', null, [], 'txn_double_1'), 'evt_double_1');

    paiementWebhook($evenement)->assertOk();
    paiementWebhook($evenement)->assertOk();

    expect(EvenementPaiement::query()->where('event_id', 'evt_double_1')->count())->toBe(1)
        ->and(PackAchat::query()->where('user_id', $utilisateur->getKey())->count())->toBe(1)
        ->and(EvenementPaiement::query()->where('event_id', 'evt_double_1')->value('traite_at'))->not->toBeNull();
});

test('même abonnement reçu deux fois : une seule ligne d\'abonnement, plan inchangé', function () {
    $utilisateur = paiementCompte();
    $evenement = paiementEvenement('subscription.created', paiementDonneesAbonnement($utilisateur), 'evt_abo_double');

    paiementWebhook($evenement)->assertOk();
    $ancre = $utilisateur->refresh()->abonnement_debut_at;
    paiementWebhook($evenement)->assertOk();

    expect(Subscription::query()->where('paddle_id', 'sub_'.$utilisateur->getKey())->count())->toBe(1)
        ->and($utilisateur->refresh()->plan)->toBe(Plan::Pro)
        ->and($utilisateur->abonnement_debut_at->toIso8601String())->toBe($ancre->toIso8601String());
});

test('signature invalide : webhook refusé et rien n\'est enregistré', function () {
    $utilisateur = paiementCompte();

    paiementWebhook(paiementEvenement('transaction.completed', paiementDonneesTransaction($utilisateur, 'pri_pack')), 'mauvais_secret')
        ->assertForbidden();

    expect(EvenementPaiement::query()->count())->toBe(0)
        ->and(PackAchat::query()->count())->toBe(0);
});

test('sans secret configuré : aucun webhook n\'est accepté', function () {
    config(['cashier.webhook_secret' => null]);
    $utilisateur = paiementCompte();

    paiementWebhook(paiementEvenement('subscription.created', paiementDonneesAbonnement($utilisateur)), '')
        ->assertForbidden();

    expect(EvenementPaiement::query()->count())->toBe(0);
});

test('webhook d\'un client inconnu : journalisé, traité, sans effet sur les comptes', function () {
    $evenement = paiementEvenement('transaction.completed', [
        'id' => 'txn_inconnu',
        'customer_id' => 'ctm_inconnu',
        'items' => [['price' => ['id' => 'pri_pack']]],
    ]);

    paiementWebhook($evenement)->assertOk();

    $ligne = EvenementPaiement::query()->where('event_id', $evenement['event_id'])->sole();

    expect($ligne->user_id)->toBeNull()
        ->and($ligne->traite_at)->not->toBeNull()
        ->and(PackAchat::query()->count())->toBe(0);
});

test('après paiement : le plan est mis à jour et les quotas se recalculent au nouveau plan', function () {
    $utilisateur = paiementCompte();

    // Avant : Gratuit, 5 vidéos par mois.
    expect(app(QuotaService::class)->resume($utilisateur)['plan'])->toBe(Plan::Free);

    paiementWebhook(paiementEvenement('subscription.created', paiementDonneesAbonnement($utilisateur)))->assertOk();

    $apres = app(QuotaService::class)->resume($utilisateur->refresh());

    expect($apres['plan'])->toBe(Plan::Pro)
        ->and($apres['secondes_mois'])->toBe(30 * 3600)
        ->and($apres['credits_ia_limite'])->toBe(30);
});

test('aperçu partiel : après paiement, la transcription repart en version complète sans recompter le Gratuit', function () {
    Queue::fake([ProcessTranscription::class]);

    $utilisateur = paiementCompte();
    $apercu = paiementApercu($utilisateur);

    // Décompte Gratuit déjà fait pour cet aperçu : une seule vidéo.
    UsageRecord::factory()->for($utilisateur)->create([
        'transcription_id' => $apercu->getKey(),
        'unite' => UniteConsommation::Video,
        'plan' => Plan::Free,
        'periode' => app(QuotaService::class)->periode($utilisateur, Plan::Free),
        'quantite' => 1,
    ]);

    $paiement = paiementDonneesTransaction($utilisateur, 'pri_pro_mensuel', 'sub_'.$utilisateur->getKey(), ['transcription_id' => $apercu->getKey()]);
    paiementAbonnementPro($utilisateur);
    paiementWebhook(paiementEvenement('transaction.completed', $paiement))->assertOk();

    expect($apercu->refresh()->est_apercu)->toBeFalse()
        ->and($apercu->statut)->toBe(StatutTranscription::EnAttente);

    Queue::assertPushed(ProcessTranscription::class, fn (ProcessTranscription $job) => $job->transcriptionId === $apercu->getKey());

    // Le Gratuit n'est pas recompté : toujours une seule vidéo gratuite pour ce compte.
    expect(UsageRecord::query()->where('user_id', $utilisateur->getKey())->where('plan', Plan::Free->value)->sum('quantite'))->toBe(1);
});

test('aperçu d\'un autre compte : jamais relancé par un paiement qui ne lui appartient pas', function () {
    Queue::fake([ProcessTranscription::class]);

    $payeur = paiementCompte();
    $autre = paiementCompte();
    $apercu = paiementApercu($autre);

    paiementWebhook(paiementEvenement('transaction.completed', paiementDonneesTransaction($payeur, 'pri_pack', null, ['transcription_id' => $apercu->getKey()])))->assertOk();

    expect($apercu->refresh()->est_apercu)->toBeTrue();
    Queue::assertNotPushed(ProcessTranscription::class);
});

// ---------------------------------------------------------------------------------------------
// Pages : paiement, espace Abonnement.
// ---------------------------------------------------------------------------------------------

test('page de paiement : formule ouverte via le fournisseur, aperçu transmis', function () {
    $utilisateur = paiementCompte();
    $apercu = paiementApercu($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('paiement.show', ['produit' => 'pro_mensuel', 'transcription' => $apercu->getKey()]))
        ->assertOk()
        ->assertSee('Paddle.Checkout.open', false);

    $faux = app(BillingProvider::class);

    expect($faux->appels)->toContain(['operation' => 'ouvrir', 'utilisateur' => $utilisateur->getKey(), 'valeur' => ['pro_mensuel', $apercu->getKey()]]);
});

test('page de paiement : aperçu d\'un autre compte ignoré', function () {
    $utilisateur = paiementCompte();
    $autre = paiementCompte();
    $apercu = paiementApercu($autre);

    $this->actingAs($utilisateur)->get(route('paiement.show', ['produit' => 'pack', 'transcription' => $apercu->getKey()]))->assertOk();

    expect(app(BillingProvider::class)->appels)->toContain(['operation' => 'ouvrir', 'utilisateur' => $utilisateur->getKey(), 'valeur' => ['pack', null]]);
});

test('page de paiement : formule inconnue refusée, compte non vérifié redirigé', function () {
    $utilisateur = paiementCompte();

    $this->actingAs($utilisateur)->get('/paiement/business')->assertNotFound();

    $nonVerifie = User::factory()->create(['email_verified_at' => null]);
    $this->actingAs($nonVerifie)->get(route('paiement.show', ['produit' => 'pack']))->assertRedirect(route('verification.notice'));
});

test('page de paiement : un abonné Pro n\'ouvre pas une deuxième souscription Pro', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('paiement.show', ['produit' => 'pro_annuel']))
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'deja-pro');
});

test('prix : lus depuis la config, et refus clair si un identifiant manque', function () {
    expect(PaddleBillingProvider::prixDe('pro_annuel'))->toBe('pri_pro_annuel');

    config(['voxia.paiement.prix.pack' => '']);

    expect(fn () => PaddleBillingProvider::prixDe('pack'))->toThrow(PaiementIndisponible::class);
});

test('espace Abonnement : formule, Packs et leur expiration, factures', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur, 'active', 'pri_pro_annuel');
    PackAchat::create([
        'user_id' => $utilisateur->getKey(),
        'secondes' => 10 * 3600,
        'achete_at' => now()->subMonth(),
        'expire_at' => Carbon::parse('2027-05-04 10:00:00', 'UTC'),
    ]);

    app(BillingProvider::class)->factures = [[
        'id' => 'txn_facture_1',
        'numero' => 'INV-42',
        'date' => now()->subDay(),
        'montant' => '96.00 $',
        'statut' => 'completed',
    ]];

    $this->actingAs($utilisateur)
        ->get(route('abonnement'))
        ->assertOk()
        ->assertSee('annuelle')
        ->assertSee('10 h de transcription')
        ->assertSee('4 mai 2027')
        ->assertSee('INV-42')
        ->assertSee('96.00 $');
});

test('espace Abonnement : passage du mensuel à l\'annuel sans changer le plan localement', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur, 'active', 'pri_pro_mensuel');

    $this->actingAs($utilisateur)
        ->put(route('abonnement.formule'), ['formule' => 'pro_annuel'])
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'formule-changee');

    expect(app(BillingProvider::class)->appels)->toContain(['operation' => 'changer', 'utilisateur' => $utilisateur->getKey(), 'valeur' => 'pro_annuel'])
        ->and($utilisateur->refresh()->plan)->toBe(Plan::Pro);
});

test('espace Abonnement : formule déjà en place, aucun appel au fournisseur', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur, 'active', 'pri_pro_mensuel');

    $this->actingAs($utilisateur)->put(route('abonnement.formule'), ['formule' => 'pro_mensuel'])->assertRedirect(route('abonnement'));

    expect(collect(app(BillingProvider::class)->appels)->where('operation', 'changer'))->toBeEmpty();
});

test('espace Abonnement : résiliation demandée, accès affiché jusqu\'à la fin de la période', function () {
    $utilisateur = paiementCompte();
    $abonnement = paiementAbonnementPro($utilisateur);
    $abonnement->forceFill(['ends_at' => Carbon::parse('2026-12-04 10:00:00', 'UTC')])->save();

    $this->actingAs($utilisateur)
        ->post(route('abonnement.resilier'))
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'resiliee');

    expect(app(BillingProvider::class)->appels)->toContain(['operation' => 'resilier', 'utilisateur' => $utilisateur->getKey(), 'valeur' => null]);

    $this->actingAs($utilisateur)->get(route('abonnement'))->assertOk()->assertSee('4 décembre 2026');
});

test('espace Abonnement : résiliation annulée avant la fin de la période', function () {
    $utilisateur = paiementCompte();
    paiementAbonnementPro($utilisateur);

    $this->actingAs($utilisateur)
        ->post(route('abonnement.reprendre'))
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'resiliation-annulee');

    expect(app(BillingProvider::class)->appels)->toContain(['operation' => 'annuler_resiliation', 'utilisateur' => $utilisateur->getKey(), 'valeur' => null]);
});

test('espace Abonnement : facture téléchargée par son lien, sans sortir du compte', function () {
    $utilisateur = paiementCompte();

    $this->actingAs($utilisateur)
        ->get(route('abonnement.facture', ['facture' => 'txn_facture_1']))
        ->assertRedirect('https://faux.test/factures/txn_facture_1');
});

test('recalcul des plans : un Pack expiré ramène le compte au Gratuit', function () {
    $utilisateur = paiementCompte();
    PackAchat::create([
        'user_id' => $utilisateur->getKey(),
        'secondes' => 10 * 3600,
        'achete_at' => now()->subMonths(7),
        'expire_at' => now()->subDay(),
    ]);
    $utilisateur->forceFill(['plan' => Plan::Pack])->save();

    $this->artisan('voxia:recalculer-plans')->assertSuccessful();

    expect($utilisateur->refresh()->plan)->toBe(Plan::Free);
});

test('recalcul des plans : un abonnement résilié garde le Pack encore valable', function () {
    $utilisateur = paiementCompte();
    PackAchat::create([
        'user_id' => $utilisateur->getKey(),
        'secondes' => 10 * 3600,
        'achete_at' => now()->subMonth(),
        'expire_at' => now()->addMonths(5),
    ]);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();

    $this->artisan('voxia:recalculer-plans')->assertSuccessful();

    expect($utilisateur->refresh()->plan)->toBe(Plan::Pack);
});

test('bandeau d\'aperçu : la fenêtre mène aux formules avec cet aperçu et l\'écran d\'origine', function () {
    $utilisateur = paiementCompte();
    $apercu = paiementApercu($utilisateur);

    // Le bandeau ouvre la fenêtre d'upgrade ; son lien garde l'aperçu (relance après paiement) et le retour.
    $this->actingAs($utilisateur)
        ->get(route('transcriptions.show', $apercu))
        ->assertOk()
        ->assertSee(e(route('paiement.show', ['produit' => 'pro_mensuel', 'retour' => '/transcriptions/'.$apercu->getKey(), 'transcription' => $apercu->getKey()])), false);
});

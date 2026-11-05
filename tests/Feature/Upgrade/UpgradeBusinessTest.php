<?php

use App\Contracts\BillingProvider;
use App\Contracts\VideoFetcher;
use App\Data\MetadonneesVideo;
use App\Enums\Plan;
use App\Enums\UniteConsommation;
use App\Livewire\NouvelleTranscription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Plans\PresentationTarifs;
use App\Services\Quota\QuotaService;
use App\Services\Video\FauxVideoFetcher;
use App\Support\Montant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Paddle\Subscription;
use Livewire\Livewire;

// Écrans d'upgrade vers le Business (étape 4 de la V2) : déclencheurs Pro, carte, liens selon l'abonnement.
uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC'));
    config([
        'voxia.droits.plan_reel' => true,
        'voxia.paiement.prix.pro_mensuel' => 'pri_pro_mensuel',
        'voxia.paiement.prix.pro_annuel' => 'pri_pro_annuel',
        'voxia.paiement.prix.business_mensuel' => 'pri_business_mensuel',
        'voxia.paiement.prix.business_annuel' => 'pri_business_annuel',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

/** Compte vérifié, au plan demandé, cycle anniversaire au 15 janvier. */
function upgradeBusinessCompte(Plan $plan): User
{
    return User::factory()->create([
        'plan' => $plan,
        'email_verified_at' => now(),
        'abonnement_debut_at' => '2026-01-15 08:00:00',
    ]);
}

/** Abonnement payant du compte, avec le prix donné. */
function upgradeBusinessAbonnement(User $utilisateur, string $prix): void
{
    $abonnement = Subscription::create([
        'billable_type' => User::class,
        'billable_id' => $utilisateur->getKey(),
        'type' => 'pro',
        'paddle_id' => 'sub_'.$utilisateur->getKey(),
        'status' => 'active',
    ]);

    $abonnement->items()->create([
        'product_id' => 'pro_test',
        'price_id' => $prix,
        'status' => 'active',
        'quantity' => 1,
    ]);
}

/** Vidéo de dix minutes détectée au collage du lien. */
function upgradeBusinessVideo(int $dureeSecondes = 600): void
{
    app()->instance(VideoFetcher::class, new FauxVideoFetcher(metadonnees: new MetadonneesVideo(
        id: 'dQw4w9WgXcQ',
        titre: 'Ma vidéo de test',
        dureeSecondes: $dureeSecondes,
        miniatureUrl: null,
        chaine: null,
        estLive: false,
    )));
}

test('Pro à 30 h : lancer une vidéo ouvre la fenêtre Business, pas la fenêtre Gratuit', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($utilisateur, 'pri_pro_mensuel');
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Pro,
        'periode' => app(QuotaService::class)->periode($utilisateur, Plan::Pro), 'quantite' => 30 * 3600,
    ]);
    upgradeBusinessVideo();
    $this->actingAs($utilisateur);

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->call('lancerLien')
        ->assertDispatched('voxia-ouvrir', id: 'upgrade-quota-business')
        ->assertNotDispatched('voxia-ouvrir', id: 'upgrade-quota');

    expect(UsageRecord::where('unite', UniteConsommation::Video)->count())->toBe(0);
});

test('la page de tableau de bord contient les fenêtres Business : quota Pro et durée supérieure à 3 h', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($utilisateur, 'pri_pro_mensuel');
    $this->actingAs($utilisateur);

    $this->get(route('tableau-de-bord'))
        ->assertOk()
        ->assertSee('id="upgrade-quota-business"', false)
        ->assertSee('id="upgrade-duree-business"', false);
});

test('la carte Business présente 150 h, 5 h par vidéo, 25 liens par lot et la file prioritaire, au prix de la config', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Free);
    $this->actingAs($utilisateur);

    $this->get(route('tableau-de-bord'))
        ->assertOk()
        ->assertSee(Montant::dollars(29))
        ->assertSee(Montant::dollars(276))
        ->assertSee(__('upgrade.lignes.heures_mois', ['heures' => 150]))
        ->assertSee(__('upgrade.lignes.lots', ['liens' => 25]))
        ->assertSee(__('upgrade.lignes.prioritaire'));
});

test('un Gratuit qui choisit le Business va au paiement de la formule mensuelle', function () {
    $carte = app(PresentationTarifs::class)->cartes(['business'], route('tableau-de-bord'))[0];

    expect($carte['cle'])->toBe('business')
        ->and($carte['actions'][0]['url'])->toContain('/paiement/business_mensuel')
        ->and($carte['actions'][1]['url'])->toContain('/paiement/business_annuel');
});

test('un abonné Pro voit « Gérer ma formule » à la place d\'un second paiement', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($utilisateur, 'pri_pro_mensuel');
    $this->actingAs($utilisateur);

    $carte = app(PresentationTarifs::class)->cartes(['business'], route('tableau-de-bord'))[0];

    expect($carte['actions'])->toHaveCount(1)
        ->and($carte['actions'][0]['url'])->toBe(route('abonnement').'#formule')
        ->and($carte['actions'][0]['libelle'])->toBe(__('upgrade.actions.gerer'));
});

test('un Pro qui ouvre le paiement Business est renvoyé vers son abonnement, sans second abonnement', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($utilisateur, 'pri_pro_mensuel');
    $this->actingAs($utilisateur);

    $this->get(route('paiement.show', ['produit' => 'business_mensuel']))
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'deja-abonne');
});

test('un Pro voit le bouton « Passer au Business » ; un Business voit le retour au Pro, en fin de période', function () {
    $pro = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($pro, 'pri_pro_mensuel');
    $this->actingAs($pro);

    $this->get(route('abonnement'))
        ->assertOk()
        ->assertSee(__('abonnement.formule.passer_business'))
        ->assertSee('value="business_mensuel"', false);

    $business = upgradeBusinessCompte(Plan::Business);
    upgradeBusinessAbonnement($business, 'pri_business_mensuel');
    $this->actingAs($business);

    $this->get(route('abonnement'))
        ->assertOk()
        ->assertSee(__('abonnement.formule.passer_pro'))
        ->assertSee('value="pro_mensuel"', false);
});

test('Business vers Pro : statut « retour au Pro à la fin de la période », et le fournisseur reçoit le changement', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Business);
    upgradeBusinessAbonnement($utilisateur, 'pri_business_mensuel');
    $this->actingAs($utilisateur);

    $this->put(route('abonnement.formule'), ['formule' => 'pro_mensuel'])
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'retour-pro-programme');

    expect(collect(app(BillingProvider::class)->appels)->last())
        ->toMatchArray(['operation' => 'changer', 'valeur' => 'pro_mensuel']);
});

test('Pro vers Business : statut « formule changée », immédiat', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Pro);
    upgradeBusinessAbonnement($utilisateur, 'pri_pro_mensuel');
    $this->actingAs($utilisateur);

    $this->put(route('abonnement.formule'), ['formule' => 'business_mensuel'])
        ->assertSessionHas('statut', 'formule-changee');
});

test('un Business qui ouvre un paiement Pro est renvoyé vers son abonnement, avec le message « déjà abonné »', function () {
    $utilisateur = upgradeBusinessCompte(Plan::Business);
    upgradeBusinessAbonnement($utilisateur, 'pri_business_mensuel');
    $this->actingAs($utilisateur);

    $this->get(route('paiement.show', ['produit' => 'pro_mensuel']))
        ->assertRedirect(route('abonnement'))
        ->assertSessionHas('statut', 'deja-abonne');
});

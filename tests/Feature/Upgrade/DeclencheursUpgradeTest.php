<?php

use App\Contracts\BillingProvider;
use App\Contracts\VideoFetcher;
use App\Data\MetadonneesVideo;
use App\Enums\FormatExport;
use App\Enums\Plan;
use App\Enums\UniteConsommation;
use App\Livewire\AnalyseScript;
use App\Livewire\NouvelleTranscription;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Paiement\FauxBillingProvider;
use App\Services\Plans\PlanFeatures;
use App\Services\Plans\PresentationTarifs;
use App\Services\Quota\QuotaService;
use App\Services\Video\FauxVideoFetcher;
use App\Support\CheminLocal;
use App\Support\Montant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Parcours d'upgrade (cahier §2) : une fenêtre superposée par déclencheur, sans quitter l'écran d'origine.
uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC')));

afterEach(fn () => Carbon::setTestNow());

const UPGRADE_LIEN = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

/** Compte vérifié. Sans plan_reel, tout le monde est Gratuit (voir PlanFeatures) ; les autres tests activent le drapeau. */
function upgradeCompte(Plan $plan = Plan::Free): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()]);
}

/** Vidéo de 10 minutes dans le fournisseur faux : le lancement passe l'aperçu sans réseau. */
function upgradeVideoDeDixMinutes(): void
{
    app()->instance(VideoFetcher::class, new FauxVideoFetcher(metadonnees: new MetadonneesVideo(
        id: 'dQw4w9WgXcQ',
        titre: 'Ma vidéo de test',
        dureeSecondes: 600,
        miniatureUrl: null,
        chaine: null,
        estLive: false,
    )));
}

/** Transcription terminée avec deux paragraphes (la lecture ne s'affiche que pour une transcription terminée). */
function upgradeTranscription(User $utilisateur, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create($attributs);

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1, 'debut_ms' => 0, 'fin_ms' => 13_400, 'texte' => 'Bienvenue dans l’atelier.',
    ]);

    return $transcription;
}

/** Vidéos du mois déjà consommées par un compte Gratuit. */
function upgradeVideosGratuites(User $utilisateur, int $nombre): void
{
    $periode = app(QuotaService::class)->periode($utilisateur, Plan::Free);

    UsageRecord::factory()->for($utilisateur)->count($nombre)->create([
        'unite' => UniteConsommation::Video,
        'plan' => Plan::Free,
        'periode' => $periode,
        'quantite' => 1,
    ]);
}

// Déclencheur principal : la 6e vidéo du mois en Gratuit.
it('ouvre la fenêtre d\'upgrade à la 6e vidéo du mois sans créer de transcription ni décompter', function () {
    upgradeVideoDeDixMinutes();
    $utilisateur = upgradeCompte();
    upgradeVideosGratuites($utilisateur, 5);
    $this->actingAs($utilisateur);

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', UPGRADE_LIEN)
        ->call('lancerLien')
        ->assertDispatched('voxia-ouvrir')
        ->assertSee(__('videos.motifs.quota_epuise'));

    expect(Transcription::count())->toBe(0)
        ->and(UsageRecord::where('unite', UniteConsommation::Video)->count())->toBe(5);
});

it('présente les plans Pro et Pack côte à côte, avec les prix et le Pack de la config', function () {
    $utilisateur = upgradeCompte();
    upgradeVideosGratuites($utilisateur, 5);
    $this->actingAs($utilisateur);

    $this->get(route('tableau-de-bord'))
        ->assertOk()
        ->assertSee('id="upgrade-quota"', false)
        ->assertSee(Montant::dollars(12))
        ->assertSee(Montant::dollars(96 / 12))
        ->assertSee(Montant::dollars(96))
        ->assertSee(Montant::dollars(5))
        ->assertSee(__('upgrade.actions.pack', ['prix' => Montant::dollars(5)]))
        ->assertSee('5 / 5', false);
});

it('reprend le lien au retour du paiement, sans le relancer', function () {
    upgradeVideoDeDixMinutes();
    $utilisateur = upgradeCompte();
    upgradeVideosGratuites($utilisateur, 5);
    $this->actingAs($utilisateur);

    // Le refus de la 6e vidéo garde le lien pour le retour du paiement.
    Livewire::test(NouvelleTranscription::class)
        ->set('lien', UPGRADE_LIEN)
        ->call('lancerLien');

    // Retour sur le tableau de bord, une fois le paiement fait : le lien revient dans le champ.
    Livewire::test(NouvelleTranscription::class)
        ->assertSet('lien', UPGRADE_LIEN)
        ->assertSet('repriseActive', true)
        ->assertSee(__('upgrade.reprise'));

    expect(Transcription::count())->toBe(0)
        ->and(session()->has(NouvelleTranscription::CLE_REPRISE))->toBeFalse();
});

// Déclencheur secondaire : le bandeau de fin d'aperçu de 10 minutes.
it('affiche le bandeau d\'aperçu avec les minutes non transcrites et une fenêtre liée à la transcription', function () {
    $utilisateur = upgradeCompte();
    $transcription = upgradeTranscription($utilisateur, ['est_apercu' => true, 'duree_secondes' => 1500]);
    $this->actingAs($utilisateur);

    $retour = route('transcriptions.show', $transcription);

    $this->get($retour)
        ->assertOk()
        ->assertSee('id="upgrade-apercu"', false)
        ->assertSee(__('upgrade.apercu.texte', ['minutes' => 15, 'duree' => '25 min']))
        ->assertSee(e(route('paiement.show', ['produit' => 'pro_mensuel', 'retour' => '/transcriptions/'.$transcription->getKey(), 'transcription' => $transcription->getKey()])), false)
        ->assertSee(e(route('paiement.show', ['produit' => 'pack', 'retour' => '/transcriptions/'.$transcription->getKey(), 'transcription' => $transcription->getKey()])), false);

    expect(app(PlanFeatures::class)->minutesNonTranscrites(600))->toBe(0)
        ->and(app(PlanFeatures::class)->minutesNonTranscrites(601))->toBe(1)
        ->and(app(PlanFeatures::class)->minutesNonTranscrites(1500))->toBe(15);
});

// Déclencheur annexe : export verrouillé (PDF, VTT).
it('ouvre la fenêtre d\'upgrade au clic sur un export verrouillé, avec les plans qui le donnent', function (FormatExport $format) {
    $utilisateur = upgradeCompte();
    $transcription = upgradeTranscription($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('aria-haspopup="dialog"', false)
        ->assertSee("id: 'upgrade-export-{$format->value}'", false)
        ->assertSee('id="upgrade-export-'.$format->value.'"', false)
        ->assertSee(e(route('paiement.show', ['produit' => 'pro_mensuel', 'retour' => '/transcriptions/'.$transcription->getKey(), 'transcription' => $transcription->getKey()])), false)
        ->assertSee(__('upgrade.export.texte', ['format' => $format->libelle(), 'plan' => 'Pro']), false);
})->with([FormatExport::Pdf, FormatExport::Vtt]);

// Déclencheur annexe : recherche dans l'historique.
it('ouvre la fenêtre d\'upgrade dès qu\'un Gratuit cherche dans son historique', function () {
    $utilisateur = upgradeCompte();
    $this->actingAs($utilisateur);

    $this->get(route('historique', ['q' => 'atelier']))
        ->assertOk()
        ->assertSee('id="upgrade-recherche"', false)
        ->assertSee('x-init="$refs.dialogue.showModal()"', false)
        ->assertSee(__('upgrade.recherche.texte'));

    $this->get(route('historique'))
        ->assertOk()
        ->assertDontSee('x-init="$refs.dialogue.showModal()"', false);
});

// Déclencheur annexe : analyse de script.
it('ouvre la fenêtre d\'upgrade à l\'analyse de script d\'un plan sans crédits IA', function () {
    $utilisateur = upgradeCompte();
    $transcription = upgradeTranscription($utilisateur, ['duree_secondes' => 120]);
    $this->actingAs($utilisateur);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->call('analyser')
        ->assertSet('refus', 'credit_ia_insuffisant')
        ->assertDispatched('voxia-ouvrir');
});

it('n\'ouvre pas la fenêtre d\'upgrade à un Pro qui a épuisé ses crédits IA', function () {
    config(['voxia.droits.plan_reel' => true]);
    $utilisateur = upgradeCompte(Plan::Pro);
    $transcription = upgradeTranscription($utilisateur, ['duree_secondes' => 120]);
    $periode = app(QuotaService::class)->periode($utilisateur, Plan::Pro);
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::CreditIa,
        'plan' => Plan::Pro,
        'periode' => $periode,
        'quantite' => (int) config('voxia.plans.pro.ai_credits_per_month'),
    ]);
    $this->actingAs($utilisateur);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->call('analyser')
        ->assertSet('refus', 'credit_ia_insuffisant')
        ->assertNotDispatched('voxia-ouvrir');
});

// Après paiement : retour à l'écran d'origine, et seulement vers cette application.
it('transmet l\'écran d\'origine au paiement et refuse une adresse externe', function () {
    $utilisateur = upgradeCompte();
    $this->actingAs($utilisateur);
    $faux = app(BillingProvider::class);

    $this->get(route('paiement.show', ['produit' => 'pro_mensuel', 'retour' => '/historique?q=atelier']))->assertOk();
    $this->get(route('paiement.show', ['produit' => 'pro_mensuel', 'retour' => 'https://evil.test/piege']))->assertOk();

    expect($faux->appels)->toContain(['operation' => 'ouvrir', 'utilisateur' => $utilisateur->getKey(), 'valeur' => ['pro_mensuel', null, url('/historique?q=atelier')]])
        ->and($faux->appels)->toContain(['operation' => 'ouvrir', 'utilisateur' => $utilisateur->getKey(), 'valeur' => ['pro_mensuel', null]]);
});

it('ne laisse passer que des chemins de cette application', function (mixed $valeur, bool $accepte) {
    expect(CheminLocal::valider($valeur) !== null)->toBe($accepte);
})->with([
    'chemin simple' => ['/tableau-de-bord', true],
    'chemin avec requête' => ['/historique?q=atelier', true],
    'adresse externe' => ['https://evil.test', false],
    'chemin réseau' => ['//evil.test', false],
    'antislash' => ['/\\evil.test', false],
    'absent' => [null, false],
]);

it('présente les plans dans l\'ordre demandé, Pack compris', function () {
    $cartes = app(PresentationTarifs::class)->cartes(['free', 'pro', 'pack'], '/tableau-de-bord');

    expect(array_column($cartes, 'cle'))->toBe(['free', 'pro', 'pack'])
        ->and($cartes[1]['actions'])->toHaveCount(2)
        ->and($cartes[2]['prix'])->toBe(Montant::dollars(5))
        ->and(app(BillingProvider::class))->toBeInstanceOf(FauxBillingProvider::class);
});

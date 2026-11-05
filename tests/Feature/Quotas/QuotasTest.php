<?php

use App\Data\MetadonneesVideo;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Enums\UniteConsommation;
use App\Exceptions\AnalyseRefusee;
use App\Exceptions\ErreurVideo;
use App\Models\PackAchat;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\AchatPack;
use App\Services\Quota\QuotaService;
use App\Services\Traitement\ChaineTranscription;
use App\Services\Video\FauxVideoFetcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC')));

afterEach(fn () => Carbon::setTestNow());

/** Compte de test, vérifié, au plan demandé. */
function quotasCompte(Plan $plan, array $attributs = []): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()] + $attributs);
}

/** Transcription rattachée au compte, à la durée donnée. */
function quotasTranscription(User $utilisateur, ?int $duree, StatutTranscription $statut = StatutTranscription::Termine): Transcription
{
    return Transcription::factory()->for($utilisateur)->create([
        'duree_secondes' => $duree,
        'statut' => $statut,
    ]);
}

/** Lignes de vidéos Gratuit écrites directement, pour mettre le compte dans un état donné. */
function quotasVideosGratuites(User $utilisateur, int $nombre): void
{
    $periode = app(QuotaService::class)->periode($utilisateur, Plan::Free);

    for ($i = 0; $i < $nombre; $i++) {
        UsageRecord::factory()->for($utilisateur)->create([
            'unite' => UniteConsommation::Video,
            'plan' => Plan::Free,
            'periode' => $periode,
            'quantite' => 1,
        ]);
    }
}

/** Motif de refus d'une action de quota, ou null si elle passe. */
function quotasMotifRefus(Closure $action): ?MotifEchec
{
    try {
        $action();
    } catch (ErreurVideo $erreur) {
        return $erreur->motif;
    }

    return null;
}

/** Chaîne de traitement avec une vidéo de durée connue, prête à transcrire. */
function quotasChaine(int $dureeSecondes = 600)
{
    $videos = new FauxVideoFetcher(metadonnees: new MetadonneesVideo(
        id: 'abcdefghijk', titre: 'Vidéo test', dureeSecondes: $dureeSecondes,
        miniatureUrl: null, chaine: null, estLive: false,
    ));

    return preparerTraitement($videos);
}

function quotasTranscriptionEnAttente(User $utilisateur): Transcription
{
    return Transcription::factory()->for($utilisateur)->create([
        'url_origine' => 'https://www.youtube.com/watch?v=abcdefghijk',
        'id_video' => null,
        'titre' => null,
        'duree_secondes' => null,
        'miniature_url' => null,
        'chaine' => null,
        'statut' => StatutTranscription::EnAttente,
    ]);
}

/* Gratuit */

it('refuse la 6e vidéo du mois avant lancement, sans rien écrire', function () {
    $utilisateur = quotasCompte(Plan::Free);
    quotasVideosGratuites($utilisateur, 5);

    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur)))
        ->toBe(MotifEchec::QuotaEpuise)
        ->and(UsageRecord::count())->toBe(5);
});

it('remet le compteur gratuit à zéro le 1er du mois, en UTC', function () {
    $utilisateur = quotasCompte(Plan::Free);
    Carbon::setTestNow(Carbon::parse('2026-11-30 23:59:00', 'UTC'));
    quotasVideosGratuites($utilisateur, 5);

    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur)))->toBe(MotifEchec::QuotaEpuise);

    Carbon::setTestNow(Carbon::parse('2026-12-01 00:00:01', 'UTC'));

    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur)))->toBeNull();
});

it('compte une vidéo de plus de 10 minutes pour une seule vidéo en Gratuit', function () {
    $utilisateur = quotasCompte(Plan::Free);

    app(QuotaService::class)->consommerTranscription(quotasTranscription($utilisateur, 1800));

    $ligne = UsageRecord::sole();
    expect($ligne->unite)->toBe(UniteConsommation::Video)
        ->and($ligne->quantite)->toBe(1)
        ->and($ligne->plan)->toBe(Plan::Free);
});

it('ne laisse pas deux lancements simultanés dépasser les 5 vidéos du mois', function () {
    $utilisateur = quotasCompte(Plan::Free);
    quotasVideosGratuites($utilisateur, 4);

    // Premier lancement accepté : sa transcription est en cours, donc réservée.
    app(QuotaService::class)->verifier($utilisateur);
    quotasTranscriptionEnAttente($utilisateur);

    // Le second lancement arrive avant la fin du premier : refusé.
    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur)))->toBe(MotifEchec::QuotaEpuise);
});

/* Pro */

it('arrondit la durée Pro à la minute supérieure', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);

    app(QuotaService::class)->consommerTranscription(quotasTranscription($utilisateur, 61));

    expect(UsageRecord::sole()->quantite)->toBe(120)
        ->and(UsageRecord::sole()->unite)->toBe(UniteConsommation::Secondes);
});

it('remet le volume Pro à zéro à la date anniversaire de l\'abonnement', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);
    // Cycle en cours du 15 octobre au 14 novembre : 30 h déjà consommées.
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Pro, 'periode' => '2026-10-15', 'quantite' => 30 * 3600,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-14 23:59:59', 'UTC'));
    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 60)))->toBe(MotifEchec::QuotaEpuise);

    Carbon::setTestNow(Carbon::parse('2026-11-15 08:00:01', 'UTC'));
    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 60)))->toBeNull();
});

it('consomme le volume Pro avant les Packs, et le reste seulement ensuite', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Pro,
        'periode' => '2026-10-15', 'quantite' => 29 * 3600,
    ]);
    $pack = app(AchatPack::class)->enregistrer($utilisateur);

    app(QuotaService::class)->consommerTranscription(quotasTranscription($utilisateur, 3 * 3600));

    $lignes = UsageRecord::where('transcription_id', '!=', null)->orderBy('id')->get();

    expect($lignes)->toHaveCount(2)
        ->and($lignes[0]->plan)->toBe(Plan::Pro)
        ->and($lignes[0]->quantite)->toBe(3600)
        ->and($lignes[1]->plan)->toBe(Plan::Pack)
        ->and($lignes[1]->quantite)->toBe(2 * 3600)
        ->and($lignes[1]->pack_achat_id)->toBe($pack->getKey());
});

it('garde le plan Pro quand un abonné achète un Pack', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);

    app(AchatPack::class)->enregistrer($utilisateur);

    expect($utilisateur->fresh()->plan)->toBe(Plan::Pro)
        ->and(PackAchat::where('user_id', $utilisateur->getKey())->count())->toBe(1);
});

it('deux lancements Pro de 3 h sur 5 h restantes : le second est refusé', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Pro,
        'periode' => '2026-10-15', 'quantite' => 25 * 3600,
    ]);

    // Premier lancement accepté (5 h restantes) ; sa vidéo de 3 h est réservée pendant le traitement.
    app(QuotaService::class)->verifier($utilisateur, 3 * 3600);
    Transcription::factory()->for($utilisateur)->create([
        'duree_secondes' => 3 * 3600,
        'statut' => StatutTranscription::EnAttente,
    ]);

    // Il reste 2 h libres : le second lancement de 3 h est refusé.
    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 3 * 3600)))
        ->toBe(MotifEchec::QuotaEpuise);
});

it('refuse au décompte une vidéo qui ne tient plus, sans rien écrire', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Pro,
        'periode' => '2026-10-15', 'quantite' => 15 * 3600,
    ]);

    // 20 h à compter, 15 h restantes et aucun Pack : le décompte refuse, rien n'est écrit.
    $transcription = quotasTranscription($utilisateur, 20 * 3600);

    expect(fn () => app(QuotaService::class)->consommerTranscription($transcription))
        ->toThrow(ErreurVideo::class)
        ->and(UsageRecord::count())->toBe(1);
});

it('refuse avant lancement une vidéo plus longue que la durée max du Pro, sans rien décompter', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);

    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 3 * 3600 + 1)))
        ->toBe(MotifEchec::TropLongue)
        ->and(UsageRecord::count())->toBe(0);
});

/* Pack */

it('crée un Pack de 10 h valable 6 mois à partir de l\'achat, et passe le Gratuit au plan Pack', function () {
    $utilisateur = quotasCompte(Plan::Free);

    $pack = app(AchatPack::class)->enregistrer($utilisateur);

    expect($pack->secondes)->toBe(10 * 3600)
        ->and($pack->expire_at->toDateTimeString())->toBe('2027-05-04 10:00:00')
        ->and($utilisateur->fresh()->plan)->toBe(Plan::Pack);
});

it('retire un Pack expiré du solde', function () {
    $utilisateur = quotasCompte(Plan::Pack);
    // Acheté le 1er mai : valable jusqu'au 1er novembre, donc expiré le 4.
    app(AchatPack::class)->enregistrer($utilisateur, Carbon::parse('2026-05-01 00:00:00', 'UTC'));

    expect(quotasMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 60)))->toBe(MotifEchec::QuotaEpuise)
        ->and(app(QuotaService::class)->resume($utilisateur)['secondes_packs'])->toBe(0);
});

it('consomme le plus ancien Pack en premier quand plusieurs Packs s\'additionnent', function () {
    $utilisateur = quotasCompte(Plan::Pack);
    $ancien = app(AchatPack::class)->enregistrer($utilisateur, Carbon::parse('2026-06-01 00:00:00', 'UTC'));
    $recent = app(AchatPack::class)->enregistrer($utilisateur, Carbon::parse('2026-10-01 00:00:00', 'UTC'));

    app(QuotaService::class)->consommerTranscription(quotasTranscription($utilisateur, 5400));

    expect(UsageRecord::where('pack_achat_id', $ancien->getKey())->sum('quantite'))->toBe(5400)
        ->and(UsageRecord::where('pack_achat_id', $recent->getKey())->count())->toBe(0)
        ->and(app(QuotaService::class)->resume($utilisateur)['secondes_packs'])->toBe(2 * 36000 - 5400);
});

it('n\'ouvre les crédits IA qu\'au Pro : aucun crédit pour le Gratuit ni pour le Pack', function () {
    $gratuit = quotasCompte(Plan::Free);
    $pack = quotasCompte(Plan::Pack);
    $pro = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);

    foreach ([$gratuit, $pack] as $sansCredit) {
        expect(fn () => app(QuotaService::class)->verifierCreditIa($sansCredit))
            ->toThrow(fn (AnalyseRefusee $erreur) => $erreur->motif === AnalyseRefusee::CREDIT_IA_INSUFFISANT);
    }

    expect(fn () => app(QuotaService::class)->verifierCreditIa($pro))->not->toThrow(AnalyseRefusee::class);
});

/* Traitement */

it('décompte une transcription réussie : les secondes Pro sont écrites à la fin seulement', function () {
    $utilisateur = quotasCompte(Plan::Pro, ['abonnement_debut_at' => '2026-01-15 08:00:00']);
    $transcription = quotasTranscriptionEnAttente($utilisateur);
    $moteur = quotasChaine(600);
    $moteur->repondre(transcriptionBruteTest());

    app(ChaineTranscription::class)->executer($transcription);

    expect($transcription->fresh()->statut)->toBe(StatutTranscription::Termine)
        ->and(UsageRecord::where('transcription_id', $transcription->getKey())->sum('quantite'))->toBe(600);
});

it('n\'écrit aucun décompte quand la transcription échoue techniquement', function () {
    $utilisateur = quotasCompte(Plan::Free);
    $transcription = quotasTranscriptionEnAttente($utilisateur);
    $moteur = quotasChaine(600);
    $moteur->echouer(new ErreurVideo(MotifEchec::ErreurTechnique, 'test'));

    app(ChaineTranscription::class)->executer($transcription);

    expect($transcription->fresh()->statut)->toBe(StatutTranscription::Echec)
        ->and(UsageRecord::count())->toBe(0);
});

it('refuse au traitement une vidéo quand le quota est déjà épuisé, avant toute transcription', function () {
    $utilisateur = quotasCompte(Plan::Free);
    quotasVideosGratuites($utilisateur, 5);
    $transcription = quotasTranscriptionEnAttente($utilisateur);
    $moteur = quotasChaine(600);
    $moteur->repondre(transcriptionBruteTest());

    app(ChaineTranscription::class)->executer($transcription);

    expect($transcription->fresh()->statut)->toBe(StatutTranscription::Echec)
        ->and($transcription->fresh()->motif_echec)->toBe(MotifEchec::QuotaEpuise)
        ->and(UsageRecord::count())->toBe(5);
});

/* Compteur et plans */

it('affiche le compteur de quota dans la mise en page', function () {
    $utilisateur = quotasCompte(Plan::Free, ['locale' => 'fr']);
    quotasVideosGratuites($utilisateur, 3);
    App::setLocale('fr');

    $this->actingAs($utilisateur)
        ->get(route('tableau-de-bord'))
        ->assertOk()
        ->assertSee(__('interface.quota.videos_mois', ['utilisees' => 3, 'limite' => 5]));
});

it('vend Gratuit, Pro, Pack et Business, et donne la file prioritaire au Pro et au Business', function () {
    $droits = app(PlanFeatures::class);

    expect($droits->plansVendables())->toEqual([Plan::Free, Plan::Pro, Plan::Pack, Plan::Business])
        ->and($droits->estVendable(Plan::Business))->toBeTrue()
        ->and($droits->fileDe(Plan::Business))->toBe('high')
        ->and($droits->fileDe(Plan::Pro))->toBe('high')
        ->and($droits->fileDe(Plan::Free))->toBe('default')
        ->and($droits->fileDe(Plan::Pack))->toBe('default');
});

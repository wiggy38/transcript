<?php

use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Enums\UniteConsommation;
use App\Exceptions\ErreurVideo;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use App\Services\Quota\AchatPack;
use App\Services\Quota\QuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Quotas du plan Business (étape 3 de la V2) : 150 h par mois, 5 h max par vidéo, lots et file prioritaire.
uses(RefreshDatabase::class);

beforeEach(fn () => Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC')));

afterEach(fn () => Carbon::setTestNow());

/** Compte Business vérifié, cycle anniversaire au 15 janvier. */
function businessCompte(array $attributs = []): User
{
    return User::factory()->create(['plan' => Plan::Business, 'email_verified_at' => now(), 'abonnement_debut_at' => '2026-01-15 08:00:00'] + $attributs);
}

/** Transcription terminée du compte, à la durée donnée (en secondes). */
function businessTranscription(User $utilisateur, int $duree): Transcription
{
    return Transcription::factory()->for($utilisateur)->create([
        'duree_secondes' => $duree,
        'statut' => StatutTranscription::Termine,
    ]);
}

/** Motif de refus d'une vérification de quota, ou null si elle passe. */
function businessMotifRefus(Closure $action): ?MotifEchec
{
    try {
        $action();

        return null;
    } catch (ErreurVideo $erreur) {
        return $erreur->motif;
    }
}

test('le volume Business est de 150 h par mois : 2 h demandées avec 149 h consommées sont refusées', function () {
    $utilisateur = businessCompte();
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Business,
        'periode' => '2026-10-15', 'quantite' => 149 * 3600,
    ]);

    expect(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 2 * 3600)))->toBe(MotifEchec::QuotaEpuise)
        ->and(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 3600)))->toBeNull();
});

test('le volume Business est consommé avant les Packs, et le reste seulement ensuite', function () {
    $utilisateur = businessCompte();
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Business,
        'periode' => '2026-10-15', 'quantite' => 149 * 3600,
    ]);
    $pack = app(AchatPack::class)->enregistrer($utilisateur);

    app(QuotaService::class)->consommerTranscription(businessTranscription($utilisateur, 3 * 3600));

    $lignes = UsageRecord::where('transcription_id', '!=', null)->orderBy('id')->get();

    expect($lignes)->toHaveCount(2)
        ->and($lignes[0]->plan)->toBe(Plan::Business)
        ->and($lignes[0]->quantite)->toBe(3600)
        ->and($lignes[1]->plan)->toBe(Plan::Pack)
        ->and($lignes[1]->quantite)->toBe(2 * 3600)
        ->and($lignes[1]->pack_achat_id)->toBe($pack->getKey());
});

test('la durée Business est arrondie à la minute supérieure', function () {
    $utilisateur = businessCompte();

    app(QuotaService::class)->consommerTranscription(businessTranscription($utilisateur, 61));

    expect(UsageRecord::sole()->quantite)->toBe(120)
        ->and(UsageRecord::sole()->plan)->toBe(Plan::Business);
});

test('le volume Business repart à zéro à la date anniversaire de l\'abonnement', function () {
    $utilisateur = businessCompte();
    UsageRecord::factory()->for($utilisateur)->create([
        'unite' => UniteConsommation::Secondes, 'plan' => Plan::Business,
        'periode' => '2026-10-15', 'quantite' => 150 * 3600,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-14 23:59:59', 'UTC'));
    expect(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 60)))->toBe(MotifEchec::QuotaEpuise);

    Carbon::setTestNow(Carbon::parse('2026-11-15 08:00:01', 'UTC'));
    expect(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 60)))->toBeNull();
});

test('une vidéo Business de plus de 5 h est refusée avant lancement, à 5 h elle passe', function () {
    $utilisateur = businessCompte();

    expect(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 5 * 3600)))->toBeNull()
        ->and(businessMotifRefus(fn () => app(QuotaService::class)->verifier($utilisateur, 5 * 3600 + 60)))->toBe(MotifEchec::TropLongue);
});

test('droits Business : abonnement, lots de 25 liens, file prioritaire, pas de plan gratuit', function () {
    $droits = app(PlanFeatures::class);

    expect($droits->estGratuit(Plan::Business))->toBeFalse()
        ->and($droits->estAbonnement(Plan::Business))->toBeTrue()
        ->and($droits->estAbonnement(Plan::Pro))->toBeTrue()
        ->and($droits->estAbonnement(Plan::Pack))->toBeFalse()
        ->and($droits->estGratuit(Plan::Free))->toBeTrue()
        ->and($droits->limiteLots(Plan::Business))->toBe(25)
        ->and($droits->limiteLots(Plan::Pro))->toBe(0)
        ->and($droits->fileDe(Plan::Business))->toBe('high');
});

test('les crédits IA Business sont de 300 par mois, réservés aux abonnés', function () {
    $droits = app(PlanFeatures::class);

    expect($droits->creditsIaMensuels(Plan::Business))->toBe(300)
        ->and($droits->creditsIaMensuels(Plan::Pro))->toBe(30)
        ->and($droits->creditsIaMensuels(Plan::Pack))->toBe(0)
        ->and($droits->creditsIaMensuels(Plan::Free))->toBe(0);
});

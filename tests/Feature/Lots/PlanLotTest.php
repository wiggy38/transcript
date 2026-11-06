<?php

use App\Enums\Plan;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Traitement par lots (V2, étape 6) : droit au lot réservé au Business, limites lues dans config/voxia.php.
uses(RefreshDatabase::class);

beforeEach(fn () => config(['voxia.droits.plan_reel' => true]));

function compteAvecPlan(Plan $plan): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()]);
}

test('seul le plan Business peut lancer un lot de liens', function () {
    $plans = app(PlanFeatures::class);

    expect($plans->peutLot(compteAvecPlan(Plan::Business)))->toBeTrue()
        ->and($plans->peutLot(compteAvecPlan(Plan::Pro)))->toBeFalse()
        ->and($plans->peutLot(compteAvecPlan(Plan::Pack)))->toBeFalse()
        ->and($plans->peutLot(compteAvecPlan(Plan::Free)))->toBeFalse();
});

test('la limite de liens par lot vient de config/voxia.php (25 pour le Business)', function () {
    $plans = app(PlanFeatures::class);

    expect($plans->limiteLots(Plan::Business))->toBe(25)
        ->and($plans->limiteLots(Plan::Pro))->toBe(0);

    config(['voxia.plans.business.batch_links' => 10]);

    expect($plans->limiteLots(Plan::Business))->toBe(10);
});

test('les limites de téléchargement simultané sont réglables dans la config (3 par utilisateur, 10 au total)', function () {
    expect(config('voxia.lots.telechargements_par_utilisateur'))->toBe(3)
        ->and(config('voxia.lots.telechargements_global'))->toBe(10);
});

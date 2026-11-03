<?php

use App\Enums\UniteConsommation;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('un remboursement est une ligne en sens inverse qui annule la consommation', function () {
    $utilisateur = User::factory()->create();
    $consommation = UsageRecord::factory()->for($utilisateur)->create();

    $remboursement = $consommation->reverse('echec_technique');

    expect($remboursement->quantite)->toBe(-1)
        ->and($remboursement->unite)->toBe(UniteConsommation::Video)
        ->and($remboursement->motif)->toBe('echec_technique')
        ->and($remboursement->annule->is($consommation))->toBeTrue()
        ->and($consommation->fresh()->remboursement->is($remboursement))->toBeTrue()
        ->and(UsageRecord::where('user_id', $utilisateur->id)->sum('quantite'))->toBe(0);
});

test('le quota en secondes reflète le remboursement d\'une partie seulement', function () {
    $utilisateur = User::factory()->create();

    UsageRecord::factory()->for($utilisateur)->secondes(600)->create();
    $raté = UsageRecord::factory()->for($utilisateur)->secondes(300)->create();

    $raté->reverse('erreur_technique');

    // SUM renvoie une chaîne sous PostgreSQL : on caste avant de comparer.
    $total = (int) UsageRecord::where('user_id', $utilisateur->id)
        ->where('unite', UniteConsommation::Secondes)
        ->sum('quantite');

    expect($total)->toBe(600);
});

test('une consommation ne peut être remboursée qu\'une seule fois', function () {
    $consommation = UsageRecord::factory()->create();

    $consommation->reverse();

    expect(fn () => $consommation->fresh()->reverse())
        ->toThrow(LogicException::class, 'déjà été remboursée');

    expect(UsageRecord::count())->toBe(2);
});

test('un remboursement ne peut pas lui-même être annulé', function () {
    $remboursement = UsageRecord::factory()->create()->reverse();

    expect(fn () => $remboursement->reverse())
        ->toThrow(LogicException::class, 'ne peut pas être annulée');
});

test('le journal de consommation n\'a pas de colonne de modification', function () {
    expect(DB::getSchemaBuilder()->hasColumn('usage_records', 'updated_at'))->toBeFalse();
});

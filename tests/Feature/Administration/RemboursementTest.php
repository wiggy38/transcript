<?php

use App\Enums\ActionAdministration;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\UniteConsommation;
use App\Exceptions\ActionAdministrationRefusee;
use App\Filament\Resources\Transcriptions\Pages\ViewTranscription;
use App\Models\JournalAdministration;
use App\Models\Transcription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Administration\RemboursementTranscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => app()->setLocale('fr'));

function rembourseAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
}

/** Traitement raté avec deux consommations actives, comme un débit partiel sur deux Packs. */
function rembourseTraitementRate(): Transcription
{
    $client = User::factory()->create(['plan' => Plan::Pack, 'email_verified_at' => now()]);
    $transcription = Transcription::factory()->for($client)->echec(MotifEchec::ErreurTechnique)->create();

    UsageRecord::factory()->for($client)->create([
        'transcription_id' => $transcription->id,
        'unite' => UniteConsommation::Secondes,
        'plan' => Plan::Pack,
        'quantite' => 600,
        'motif' => 'transcription',
    ]);
    UsageRecord::factory()->for($client)->create([
        'transcription_id' => $transcription->id,
        'unite' => UniteConsommation::Secondes,
        'plan' => Plan::Pack,
        'quantite' => 300,
        'motif' => 'transcription',
    ]);

    return $transcription;
}

it('rembourse un traitement raté par des lignes inverses et le journalise', function () {
    $administrateur = rembourseAdmin();
    $transcription = rembourseTraitementRate();

    $nombre = app(RemboursementTranscription::class)->rembourser($transcription, $administrateur, 'Échec confirmé côté fournisseur');

    expect($nombre)->toBe(2)
        ->and(UsageRecord::query()->whereNotNull('annule_usage_record_id')->sum('quantite'))->toBe(-900);

    $journal = JournalAdministration::query()->sole();
    expect($journal->action)->toBe(ActionAdministration::Remboursement)
        ->and($journal->transcription_id)->toBe($transcription->id)
        ->and($journal->administrateur_id)->toBe($administrateur->id)
        ->and($journal->motif)->toBe('Échec confirmé côté fournisseur');
});

it('refuse un second remboursement du même traitement', function () {
    $transcription = rembourseTraitementRate();
    $service = app(RemboursementTranscription::class);
    $service->rembourser($transcription, rembourseAdmin(), 'Premier');

    expect(fn () => $service->rembourser($transcription, rembourseAdmin(), 'Second'))
        ->toThrow(ActionAdministrationRefusee::class);

    expect(JournalAdministration::query()->count())->toBe(1);
});

it('refuse de rembourser un traitement qui n\'est pas en échec', function () {
    $transcription = Transcription::factory()->termine()->create();
    UsageRecord::factory()->for($transcription->user)->create(['transcription_id' => $transcription->id]);

    $refus = null;

    try {
        app(RemboursementTranscription::class)->rembourser($transcription, rembourseAdmin(), 'Test');
    } catch (ActionAdministrationRefusee $erreur) {
        $refus = $erreur->raison;
    }

    expect($refus)->toBe(ActionAdministrationRefusee::PAS_EN_ECHEC)
        ->and(UsageRecord::query()->whereNotNull('annule_usage_record_id')->count())->toBe(0);
});

it('signale qu\'il n\'y a rien à rembourser pour un échec sans débit', function () {
    $transcription = Transcription::factory()->echec()->create();

    $refus = null;

    try {
        app(RemboursementTranscription::class)->rembourser($transcription, rembourseAdmin(), 'Test');
    } catch (ActionAdministrationRefusee $erreur) {
        $refus = $erreur->raison;
    }

    expect($refus)->toBe(ActionAdministrationRefusee::RIEN_A_REMBOURSER);
});

it('rembourse depuis la fiche du traitement et journalise l\'action', function () {
    $administrateur = rembourseAdmin();
    $transcription = rembourseTraitementRate();

    Livewire::actingAs($administrateur)
        ->test(ViewTranscription::class, ['record' => $transcription->getRouteKey()])
        ->callAction('rembourser', data: ['motif' => 'Remboursement depuis la fiche'])
        ->assertHasNoActionErrors();

    expect(UsageRecord::query()->whereNotNull('annule_usage_record_id')->count())->toBe(2)
        ->and(JournalAdministration::query()->sole()->motif)->toBe('Remboursement depuis la fiche');
});

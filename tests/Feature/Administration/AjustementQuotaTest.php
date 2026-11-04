<?php

use App\Enums\ActionAdministration;
use App\Enums\CibleAjustement;
use App\Enums\Plan;
use App\Enums\UniteConsommation;
use App\Exceptions\ActionAdministrationRefusee;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\JournalAdministration;
use App\Models\PackAchat;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Administration\AjustementQuota;
use App\Services\Quota\QuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'UTC'));
    app()->setLocale('fr');
});

afterEach(fn () => Carbon::setTestNow());

function ajustementAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
}

function ajustementCompte(Plan $plan): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()]);
}

/** Code de refus de l'action, ou null si elle passe. */
function ajustementRefus(Closure $action): ?string
{
    try {
        $action();

        return null;
    } catch (ActionAdministrationRefusee $erreur) {
        return $erreur->raison;
    }
}

it('ajoute des vidéos au quota du Gratuit et le journalise', function () {
    $administrateur = ajustementAdmin();
    $client = ajustementCompte(Plan::Free);

    app(AjustementQuota::class)->ajuster($client, $administrateur, CibleAjustement::Videos, 2, 'Vidéo perdue par notre faute');

    $ligne = UsageRecord::query()->where('user_id', $client->id)->sole();
    expect($ligne->unite)->toBe(UniteConsommation::Video)
        ->and($ligne->quantite)->toBe(-2)
        ->and($ligne->motif)->toBe('ajustement_admin');

    $journal = JournalAdministration::query()->sole();
    expect($journal->action)->toBe(ActionAdministration::AjustementQuota)
        ->and($journal->administrateur_id)->toBe($administrateur->id)
        ->and($journal->user_id)->toBe($client->id)
        ->and($journal->motif)->toBe('Vidéo perdue par notre faute')
        ->and($journal->details['quantite'])->toBe(2);

    expect(app(QuotaService::class)->resume($client)['videos_utilisees'])->toBe(-2);
});

it('refuse un ajustement sans motif, sans rien écrire', function () {
    $client = ajustementCompte(Plan::Free);

    $refus = ajustementRefus(fn () => app(AjustementQuota::class)->ajuster($client, ajustementAdmin(), CibleAjustement::Videos, 1, '   '));

    expect($refus)->toBe(ActionAdministrationRefusee::MOTIF_VIDE)
        ->and(UsageRecord::query()->count())->toBe(0)
        ->and(JournalAdministration::query()->count())->toBe(0);
});

it('refuse une quantité nulle', function () {
    $client = ajustementCompte(Plan::Free);

    $refus = ajustementRefus(fn () => app(AjustementQuota::class)->ajuster($client, ajustementAdmin(), CibleAjustement::Videos, 0, 'Test'));

    expect($refus)->toBe(ActionAdministrationRefusee::QUANTITE_NULLE);
});

it('ajoute des heures au quota mensuel du Pro, en secondes', function () {
    $client = ajustementCompte(Plan::Pro);

    app(AjustementQuota::class)->ajuster($client, ajustementAdmin(), CibleAjustement::HeuresMois, 2, 'Geste commercial');

    $ligne = UsageRecord::query()->where('user_id', $client->id)->sole();
    expect($ligne->unite)->toBe(UniteConsommation::Secondes)
        ->and($ligne->quantite)->toBe(-7200)
        ->and($ligne->plan)->toBe(Plan::Pro);

    expect(app(QuotaService::class)->resume($client)['secondes_mois'])->toBe(32 * 3600);
});

it('ajoute des heures à un Pack valable', function () {
    $client = ajustementCompte(Plan::Pack);
    $pack = PackAchat::create([
        'user_id' => $client->id,
        'secondes' => 10 * 3600,
        'achete_at' => now()->subDay(),
        'expire_at' => now()->addMonths(6),
    ]);

    app(AjustementQuota::class)->ajuster($client, ajustementAdmin(), CibleAjustement::HeuresPack, 1, 'Erreur de facturation');

    $ligne = UsageRecord::query()->where('user_id', $client->id)->sole();
    expect($ligne->pack_achat_id)->toBe($pack->id)
        ->and($ligne->quantite)->toBe(-3600);

    expect(app(QuotaService::class)->resume($client)['secondes_packs'])->toBe(11 * 3600);
});

it('refuse un compteur que le plan du compte n\'a pas', function () {
    $client = ajustementCompte(Plan::Free);

    $refus = ajustementRefus(fn () => app(AjustementQuota::class)->ajuster($client, ajustementAdmin(), CibleAjustement::CreditsIa, 1, 'Test'));

    expect($refus)->toBe(ActionAdministrationRefusee::CIBLE_INDISPONIBLE)
        ->and(UsageRecord::query()->count())->toBe(0);
});

it('propose le bouton d\'ajustement dans la liste et journalise l\'action', function () {
    $administrateur = ajustementAdmin();
    $client = ajustementCompte(Plan::Free);

    Livewire::actingAs($administrateur)
        ->test(ListUsers::class)
        ->callTableAction('ajuster', $client, data: [
            'compteur' => 'videos',
            'quantite' => 3,
            'motif' => 'Test depuis le back-office',
        ])
        ->assertHasNoTableActionErrors();

    expect(UsageRecord::query()->where('user_id', $client->id)->sole()->quantite)->toBe(-3)
        ->and(JournalAdministration::query()->sole()->motif)->toBe('Test depuis le back-office');
});

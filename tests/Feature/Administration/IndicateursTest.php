<?php

use App\Enums\EtapeTraitement;
use App\Enums\Plan;
use App\Models\PackAchat;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Administration\IndicateursAdministration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'UTC'));
    app()->setLocale('fr');
});

afterEach(fn () => Carbon::setTestNow());

function indicateursTelechargement(Transcription $transcription, bool $succes, Carbon $quand): void
{
    ProcessingLog::factory()->for($transcription)->create([
        'etape' => EtapeTraitement::Telechargement,
        'succes' => $succes,
        'created_at' => $quand,
    ]);
}

it('compte les traitements lancés depuis minuit seulement', function () {
    Transcription::factory()->create(['created_at' => now()->startOfDay()->addHour()]);
    Transcription::factory()->create(['created_at' => now()->subDay()]);

    expect(app(IndicateursAdministration::class)->traitementsDuJour())->toBe(1);
});

it('calcule le taux d\'échec du téléchargement sur 24 heures', function () {
    $transcription = Transcription::factory()->create();

    // 2 échecs sur 10 tentatives dans les 24 heures ; un échec plus ancien ne compte pas.
    for ($i = 0; $i < 8; $i++) {
        indicateursTelechargement($transcription, true, now()->subHours(2));
    }
    indicateursTelechargement($transcription, false, now()->subHours(3));
    indicateursTelechargement($transcription, false, now()->subHours(4));
    indicateursTelechargement($transcription, false, now()->subHours(30));

    $mesure = app(IndicateursAdministration::class)->tauxEchecTelechargement(now()->subHours(24));

    expect($mesure)->toMatchArray(['tentatives' => 10, 'echecs' => 2])
        ->and($mesure['taux'])->toBe(0.2);
});

it('donne un taux nul sans tentative', function () {
    expect(app(IndicateursAdministration::class)->tauxEchecTelechargement(now()->subHours(24))['taux'])->toBeNull();
});

it('calcule le coût moyen par heure de vidéo sur les transcriptions terminées', function () {
    Transcription::factory()->termine()->create([
        'duree_secondes' => 3600,
        'cout_estime_usd' => 0.22,
    ]);
    Transcription::factory()->termine()->create([
        'duree_secondes' => 1800,
        'cout_estime_usd' => 0.11,
    ]);
    // Un échec n'entre pas dans le calcul.
    Transcription::factory()->echec()->create(['duree_secondes' => 3600, 'cout_estime_usd' => 5]);

    expect(app(IndicateursAdministration::class)->coutMoyenParHeureVideo(now()->subDays(30)))
        ->toBe(0.22);
});

it('compte les inscriptions hors administrateurs', function () {
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()->subDays(2), 'is_admin' => true]);
    User::factory()->create(['created_at' => now()->subDays(60)]);

    expect(app(IndicateursAdministration::class)->inscriptions(now()->subDays(30)))->toBe(1);
});

it('compte les conversions au premier achat payant de la période', function () {
    $nouveauAbonne = User::factory()->create(['plan' => Plan::Pro]);
    $ancienAbonne = User::factory()->create(['plan' => Plan::Pro]);
    $nouveauPack = User::factory()->create(['plan' => Plan::Pack]);
    $dejaPayant = User::factory()->create(['plan' => Plan::Pack]);

    DB::table('subscriptions')->insert([
        ['billable_id' => $nouveauAbonne->id, 'billable_type' => User::class, 'type' => 'pro', 'paddle_id' => 'sub_1', 'status' => 'active', 'created_at' => now()->subDays(3), 'updated_at' => now()],
        ['billable_id' => $ancienAbonne->id, 'billable_type' => User::class, 'type' => 'pro', 'paddle_id' => 'sub_2', 'status' => 'canceled', 'created_at' => now()->subDays(90), 'updated_at' => now()],
    ]);

    PackAchat::create(['user_id' => $nouveauPack->id, 'secondes' => 36000, 'achete_at' => now()->subDays(5), 'expire_at' => now()->addMonths(6)]);

    // Pack acheté avant la période, puis abonnement dans la période : pas une conversion.
    PackAchat::create(['user_id' => $dejaPayant->id, 'secondes' => 36000, 'achete_at' => now()->subDays(60), 'expire_at' => now()->addMonths(6)]);
    DB::table('subscriptions')->insert(['billable_id' => $dejaPayant->id, 'billable_type' => User::class, 'type' => 'pro', 'paddle_id' => 'sub_3', 'status' => 'active', 'created_at' => now()->subDays(2), 'updated_at' => now()]);

    expect(app(IndicateursAdministration::class)->conversions(now()->subDays(30)))->toBe(2);
});

it('affiche les indicateurs sur le tableau de bord', function () {
    $administrateur = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    $transcription = Transcription::factory()->create(['created_at' => now()]);
    indicateursTelechargement($transcription, false, now()->subMinutes(5));

    $this->actingAs($administrateur)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Traitements du jour')
        ->assertSee('Échecs de téléchargement (24 h)')
        ->assertSee('Coût moyen par heure de vidéo')
        ->assertSee('Inscriptions')
        ->assertSee('Conversions Gratuit')
        ->assertSee('100,0 %');
});

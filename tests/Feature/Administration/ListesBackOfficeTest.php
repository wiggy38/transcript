<?php

use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Filament\Resources\Transcriptions\Pages\ListTranscriptions;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => app()->setLocale('fr'));

function listesAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
}

it('filtre la liste des utilisateurs par plan', function () {
    $gratuit = User::factory()->create(['plan' => Plan::Free]);
    $pro = User::factory()->create(['plan' => Plan::Pro]);

    Livewire::actingAs(listesAdmin())
        ->test(ListUsers::class)
        ->filterTable('plan', 'pro')
        ->assertCanSeeTableRecords([$pro])
        ->assertCanNotSeeTableRecords([$gratuit]);
});

it('affiche la consommation du mois de chaque compte', function () {
    $gratuit = User::factory()->create(['plan' => Plan::Free]);

    Livewire::actingAs(listesAdmin())
        ->test(ListUsers::class)
        ->assertTableColumnStateSet('consommation', ['0 / 5 vidéos'], record: $gratuit);
});

it('filtre les traitements par statut', function () {
    $echec = Transcription::factory()->echec(MotifEchec::VideoPrivee)->create();
    $termine = Transcription::factory()->termine()->create();

    Livewire::actingAs(listesAdmin())
        ->test(ListTranscriptions::class)
        ->filterTable('statut', 'echec')
        ->assertCanSeeTableRecords([$echec])
        ->assertCanNotSeeTableRecords([$termine]);
});

it('filtre les traitements par période', function () {
    $ancien = Transcription::factory()->termine()->create(['created_at' => '2026-10-01 12:00:00']);
    $recent = Transcription::factory()->termine()->create(['created_at' => '2026-11-02 12:00:00']);

    Livewire::actingAs(listesAdmin())
        ->test(ListTranscriptions::class)
        ->filterTable('periode', ['du' => '2026-11-01', 'au' => '2026-11-30'])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$ancien]);
});

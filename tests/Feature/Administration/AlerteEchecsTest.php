<?php

use App\Enums\EtapeTraitement;
use App\Mail\AlerteEchecsTelechargement;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Administration\AlerteEchecsTelechargement as Alerte;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'UTC'));
    app()->setLocale('fr');
    Mail::fake();
    Cache::flush();
});

afterEach(fn () => Carbon::setTestNow());

/** Tentatives de téléchargement : $echecs en échec et $succes réussies, toutes il y a $minutes minutes. */
function alerteTentatives(int $echecs, int $succes, int $minutes = 10): void
{
    $transcription = Transcription::factory()->create();

    for ($i = 0; $i < $echecs + $succes; $i++) {
        ProcessingLog::factory()->for($transcription)->create([
            'etape' => EtapeTraitement::Telechargement,
            'succes' => $i >= $echecs,
            'created_at' => now()->subMinutes($minutes),
        ]);
    }
}

function alerteAdmin(): User
{
    return User::factory()->create(['is_admin' => true, 'email' => 'admin@voxia.test']);
}

it('alerte les administrateurs quand le taux dépasse 5 % sur la dernière heure', function () {
    $administrateur = alerteAdmin();
    alerteTentatives(echecs: 3, succes: 17);

    expect(app(Alerte::class)->verifier())->toBeTrue();

    Mail::assertQueued(AlerteEchecsTelechargement::class, fn (AlerteEchecsTelechargement $courriel) => $courriel->hasTo($administrateur->email));
});

it('n\'alerte pas quand le taux reste à 5 % ou moins', function () {
    alerteAdmin();
    alerteTentatives(echecs: 1, succes: 19);

    expect(app(Alerte::class)->verifier())->toBeFalse();

    Mail::assertNothingQueued();
});

it('ne compte que la dernière heure', function () {
    alerteAdmin();
    alerteTentatives(echecs: 5, succes: 0, minutes: 90);
    alerteTentatives(echecs: 0, succes: 20, minutes: 10);

    expect(app(Alerte::class)->verifier())->toBeFalse();

    Mail::assertNothingQueued();
});

it('utilise le seuil de la configuration', function () {
    alerteAdmin();
    config(['voxia.administration.alerte_echecs.seuil' => 0.5]);
    alerteTentatives(echecs: 3, succes: 17);

    expect(app(Alerte::class)->verifier())->toBeFalse();
});

it('n\'envoie pas une seconde alerte avant le délai', function () {
    alerteAdmin();
    alerteTentatives(echecs: 5, succes: 5);

    $alerte = app(Alerte::class);
    expect($alerte->verifier())->toBeTrue()
        ->and($alerte->verifier())->toBeFalse();

    Mail::assertQueuedCount(1);

    // Le délai écoulé, une nouvelle alerte peut partir (avec des tentatives récentes).
    Carbon::setTestNow(now()->addMinutes(61));
    alerteTentatives(echecs: 5, succes: 5, minutes: 1);
    expect($alerte->verifier())->toBeTrue();

    Mail::assertQueuedCount(2);
});

it('ne fait rien sans tentative de téléchargement', function () {
    alerteAdmin();

    expect(app(Alerte::class)->verifier())->toBeFalse();

    Mail::assertNothingQueued();
});

it('exécute la commande voxia:alerter-echecs', function () {
    alerteAdmin();
    alerteTentatives(echecs: 2, succes: 8);

    $this->artisan('voxia:alerter-echecs')->assertSuccessful();

    Mail::assertQueuedCount(1);
});

<?php

use App\Enums\Plan;
use App\Mail\RappelSuppression;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Conservation\Conservation;
use App\Services\Plans\PlanFeatures;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** Transcription de ce compte, créée à la date donnée (created_at n'est pas assignable). */
function transcriptionConservee(User $utilisateur, string $titre, DateTimeInterface $date, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(array_merge(['titre' => $titre], $attributs));
    $transcription->forceFill(['created_at' => $date])->save();

    return $transcription;
}

function compteGratuit(): User
{
    return User::factory()->create(['locale' => 'fr']);
}

function comptePro(): User
{
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();

    return $utilisateur;
}

// Critère F5 (Gratuit) : suppression 30 jours après création.
it('supprime les transcriptions du plan Gratuit plus vieilles que 30 jours', function () {
    Storage::fake(config('voxia.stockage.disque'));
    $gratuit = compteGratuit();

    $expiree = transcriptionConservee($gratuit, 'Expirée', now()->subDays(31));
    $encoreLa = transcriptionConservee($gratuit, 'Encore là', now()->subDays(29));

    app(Conservation::class)->purgerHistorique();

    expect(Transcription::whereKey($expiree->getKey())->exists())->toBeFalse()
        ->and(Transcription::whereKey($encoreLa->getKey())->exists())->toBeTrue();
});

it('ne supprime jamais les transcriptions d’un plan sans limite (Pro)', function () {
    $pro = comptePro();
    $ancienne = transcriptionConservee($pro, 'Ancienne Pro', now()->subYears(2));

    app(Conservation::class)->purgerHistorique();

    expect(Transcription::whereKey($ancienne->getKey())->exists())->toBeTrue();
});

// Critère F5 (Gratuit) : email de rappel 3 jours avant suppression.
it('envoie le rappel trois jours avant la suppression, une seule fois par transcription', function () {
    Mail::fake();
    $gratuit = compteGratuit();

    // Expire dans 3 jours : à signaler. Expire dans 10 jours : pas encore.
    $bientot = transcriptionConservee($gratuit, 'Bientôt supprimée', now()->subDays(27)->subHour());
    $plusTard = transcriptionConservee($gratuit, 'Plus tard', now()->subDays(20));

    app(Conservation::class)->rappelerSuppressions();

    Mail::assertQueued(RappelSuppression::class, fn (RappelSuppression $mail) => $mail->hasTo($gratuit->email)
        && $mail->transcriptions->pluck('id')->all() === [$bientot->getKey()]);

    expect($bientot->refresh()->date_rappel_suppression)->not->toBeNull()
        ->and($plusTard->refresh()->date_rappel_suppression)->toBeNull();

    // Deuxième passage le lendemain : rien de nouveau à signaler.
    Mail::fake();
    app(Conservation::class)->rappelerSuppressions();
    Mail::assertNothingQueued();
});

it('n’envoie pas de rappel aux comptes Pro', function () {
    Mail::fake();
    $pro = comptePro();
    transcriptionConservee($pro, 'Pro', now()->subDays(28));

    app(Conservation::class)->rappelerSuppressions();

    Mail::assertNothingQueued();
});

it('le rappel indique le nombre de transcriptions, leurs titres et leurs dates de suppression', function () {
    app()->setLocale('fr');
    $gratuit = compteGratuit();
    $transcription = transcriptionConservee($gratuit, 'Vidéo du rappel', now()->subDays(28));

    $mail = new RappelSuppression($gratuit, collect([$transcription]), 30, 3);

    $mail->assertSeeInHtml('Vidéo du rappel');
    $mail->assertSeeInHtml($transcription->created_at->copy()->addDays(30)->locale('fr')->isoFormat('LL'));
});

// Critère F5 (conservation) : MP3 et fichiers importés supprimés après la durée de config, pour tous.
it('retire les MP3 plus vieux que la durée de conservation des médias, pour tous les plans', function () {
    Storage::fake(config('voxia.stockage.disque'));
    $disque = Storage::disk(config('voxia.stockage.disque'));
    $pro = comptePro();

    $vieux = transcriptionConservee($pro, 'Vieux MP3', now()->subDays(45));
    $recent = transcriptionConservee($pro, 'Récent MP3', now()->subDays(5));

    $cheminVieux = "transcriptions/{$vieux->getKey()}/vieux.mp3";
    $cheminRecent = "transcriptions/{$recent->getKey()}/recent.mp3";
    $vieux->forceFill(['mp3_chemin' => $cheminVieux])->save();
    $recent->forceFill(['mp3_chemin' => $cheminRecent])->save();
    $disque->put($cheminVieux, 'x');
    $disque->put($cheminRecent, 'y');

    $resultat = app(Conservation::class)->purgerMedias();

    expect($resultat['mp3'])->toBe(1)
        ->and($vieux->refresh()->mp3_chemin)->toBeNull()
        ->and($recent->refresh()->mp3_chemin)->toBe($cheminRecent);

    $disque->assertMissing($cheminVieux);
    $disque->assertExists($cheminRecent);
});

it('retire les fichiers importés et morceaux plus vieux que la durée de conservation', function () {
    $dossier = storage_path('framework/testing/televersements');
    File::deleteDirectory($dossier);
    config(['voxia.uploads.dossier' => $dossier]);

    mkdir($dossier.'/1/envoi-ancien', 0775, true);
    mkdir($dossier.'/1/envoi-recent', 0775, true);
    file_put_contents($dossier.'/1/envoi-ancien/vieux.mp4', 'x');
    file_put_contents($dossier.'/1/envoi-recent/recent.mp4', 'y');
    touch($dossier.'/1/envoi-ancien/vieux.mp4', now()->subDays(40)->getTimestamp());
    touch($dossier.'/1/envoi-ancien', now()->subDays(40)->getTimestamp());

    $resultat = app(Conservation::class)->purgerMedias();

    expect($resultat['fichiers'])->toBe(1)
        ->and(file_exists($dossier.'/1/envoi-ancien/vieux.mp4'))->toBeFalse()
        ->and(is_dir($dossier.'/1/envoi-ancien'))->toBeFalse()
        ->and(file_exists($dossier.'/1/envoi-recent/recent.mp4'))->toBeTrue();

    File::deleteDirectory($dossier);
});

it('planifie les trois commandes de conservation', function () {
    $evenements = collect(app(Schedule::class)->events())->map(fn ($evenement) => $evenement->command);

    expect($evenements->contains(fn ($commande) => str_contains($commande, 'voxia:purger-historique')))->toBeTrue()
        ->and($evenements->contains(fn ($commande) => str_contains($commande, 'voxia:purger-medias')))->toBeTrue()
        ->and($evenements->contains(fn ($commande) => str_contains($commande, 'voxia:rappeler-suppression')))->toBeTrue();
});

// Droits par plan : tout le monde est Gratuit tant que le drapeau de plan est éteint.
it('considère tout le monde comme Gratuit tant que le drapeau de plan est éteint', function () {
    config(['voxia.droits.plan_reel' => false]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();

    $droits = app(PlanFeatures::class);

    expect($droits->planDe($utilisateur))->toBe(Plan::Free)
        ->and($droits->peutRechercher($utilisateur))->toBeFalse()
        ->and($droits->joursConservation($utilisateur))->toBe(30)
        ->and($droits->joursAvantRappel($utilisateur))->toBe(3);
});

it('applique les droits du plan enregistré quand le drapeau de plan est actif', function () {
    $pro = comptePro();

    $droits = app(PlanFeatures::class);

    expect($droits->planDe($pro))->toBe(Plan::Pro)
        ->and($droits->peutRechercher($pro))->toBeTrue()
        ->and($droits->joursConservation($pro))->toBeNull();
});

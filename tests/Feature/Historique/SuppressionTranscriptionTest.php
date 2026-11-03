<?php

use App\Enums\Plan;
use App\Jobs\SupprimerFichiersTranscriptions;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

$pasPostgres = fn () => DB::getDriverName() !== 'pgsql';

/** Transcription terminée avec un MP3 stocké sur le disque de test. */
function historiqueTranscriptionAvecMp3(User $utilisateur, string $titre = 'Vidéo à supprimer'): Transcription
{
    Storage::fake(config('voxia.stockage.disque'));

    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(['titre' => $titre]);
    $chemin = 'transcriptions/'.$transcription->getKey().'/audio.mp3';

    Storage::disk(config('voxia.stockage.disque'))->put($chemin, 'mp3');
    $transcription->update(['mp3_chemin' => $chemin]);

    return $transcription;
}

// Critère F5 : confirmation avant suppression.
it('demande une confirmation avant de supprimer, sans rien effacer', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $transcription = historiqueTranscriptionAvecMp3($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.supprimer', $transcription))
        ->assertOk()
        ->assertSee(__('historique.confirmation.titre'))
        ->assertSee('Vidéo à supprimer');

    expect(Transcription::whereKey($transcription->getKey())->exists())->toBeTrue();
});

// Critère F5 : une transcription supprimée disparaît de la liste.
it('supprime la transcription : elle disparaît de la liste', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);
    app()->setLocale('fr');
    $transcription = historiqueTranscriptionAvecMp3($utilisateur, 'Titre à effacer');

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertSee('Titre à effacer');

    $this->actingAs($utilisateur)
        ->delete(route('transcriptions.destroy', $transcription))
        ->assertRedirect(route('historique'))
        ->assertSessionHas('statut', 'transcription-supprimee');

    expect(Transcription::whereKey($transcription->getKey())->exists())->toBeFalse();

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertOk()
        ->assertDontSee('Titre à effacer');
});

// Le MP3 part avec la transcription (cahier F5 : suppression de la transcription et de son MP3).
it('supprime aussi le MP3 de la transcription', function () {
    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $transcription = historiqueTranscriptionAvecMp3($utilisateur);
    $chemin = $transcription->mp3_chemin;

    Storage::disk(config('voxia.stockage.disque'))->assertExists($chemin);

    $this->actingAs($utilisateur)
        ->delete(route('transcriptions.destroy', $transcription));

    Storage::disk(config('voxia.stockage.disque'))->assertMissing($chemin);
});

it('confie la suppression des fichiers à la file', function () {
    Queue::fake();

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $transcription = historiqueTranscriptionAvecMp3($utilisateur);

    $this->actingAs($utilisateur)
        ->delete(route('transcriptions.destroy', $transcription));

    Queue::assertPushed(SupprimerFichiersTranscriptions::class, fn ($job) => $job->transcriptionIds === [$transcription->getKey()]);
});

// Un compte ne supprime que ses transcriptions.
it('refuse la suppression d’une transcription d’un autre compte', function () {
    $proprietaire = User::factory()->create(['locale' => 'fr']);
    $intrus = User::factory()->create(['locale' => 'fr']);
    $transcription = historiqueTranscriptionAvecMp3($proprietaire);

    $this->actingAs($intrus)
        ->get(route('transcriptions.supprimer', $transcription))
        ->assertNotFound();

    $this->actingAs($intrus)
        ->delete(route('transcriptions.destroy', $transcription))
        ->assertNotFound();

    expect(Transcription::whereKey($transcription->getKey())->exists())->toBeTrue();
});

// Critère F5 : une transcription supprimée disparaît aussi de la recherche (PostgreSQL).
it('supprime la transcription : elle disparaît aussi de la recherche', function () {
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = User::factory()->create(['locale' => 'fr']);
    $utilisateur->forceFill(['plan' => Plan::Pro])->save();
    app()->setLocale('fr');

    $transcription = historiqueTranscriptionAvecMp3($utilisateur, 'Vidéo recherchée');
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 9000,
        'texte' => 'Le secret du lancement.',
        'config_recherche' => 'french',
    ]);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertSee('Vidéo recherchée');

    $this->actingAs($utilisateur)
        ->delete(route('transcriptions.destroy', $transcription));

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'lancement']))
        ->assertOk()
        ->assertDontSee('Vidéo recherchée');

    expect(TranscriptionParagraph::query()->recherche('lancement', 'french')->count())->toBe(0);
})->skip($pasPostgres, 'Recherche plein texte : PostgreSQL uniquement.');

it('la tâche de suppression retire le dossier du MP3', function () {
    Storage::fake(config('voxia.stockage.disque'));
    $disque = Storage::disk(config('voxia.stockage.disque'));

    $disque->put('transcriptions/42/audio.mp3', 'mp3');
    $disque->put('transcriptions/43/audio.mp3', 'autre');

    (new SupprimerFichiersTranscriptions([42]))->handle();

    $disque->assertMissing('transcriptions/42/audio.mp3');
    $disque->assertExists('transcriptions/43/audio.mp3');
});

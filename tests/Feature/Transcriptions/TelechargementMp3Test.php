<?php

use App\Models\Transcription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/** Transcription de fichier terminée, avec son MP3 posé sur le disque de stockage. */
function mp3Transcription(User $utilisateur): Transcription
{
    Storage::fake(config('voxia.stockage.disque'));

    $transcription = Transcription::factory()->termine()->fichier()->for($utilisateur)->create([
        'titre' => 'Atelier du jour',
        'mp3_chemin' => 'transcriptions/1/extrait.mp3',
    ]);
    Storage::disk(config('voxia.stockage.disque'))->put('transcriptions/1/extrait.mp3', str_repeat('0', 4096));

    return $transcription;
}

// Critère F3 : le bouton MP3 passe par un lien signé, et le fichier porte le nom calculé à partir du titre.
it('télécharge le MP3 par un lien signé, nommé d’après le titre', function () {
    $utilisateur = User::factory()->create();
    $transcription = mp3Transcription($utilisateur);

    $this->actingAs($utilisateur)
        ->get(lienSigne('transcriptions.mp3', [$transcription]))
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('Content-Disposition', 'attachment; filename="Atelier du jour.mp3"');
});

// Critère Sécurité : le bouton de la page porte un lien signé vers le MP3 du propriétaire.
it('branche le bouton MP3 de la page sur un lien signé', function () {
    $utilisateur = User::factory()->create();
    $transcription = mp3Transcription($utilisateur);
    $this->actingAs($utilisateur);
    // Les liens signés contiennent leur échéance : le temps est figé pour comparer les adresses.
    $this->travelTo(now());

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('href="'.e(lienSigne('transcriptions.mp3', [$transcription])).'"', false);
});

// Critère Sécurité : un autre compte n'obtient jamais le fichier, même avec un lien signé valide ;
// sans signature, le propriétaire lui-même est refusé.
it('refuse le MP3 à un autre utilisateur, et un lien sans signature', function () {
    $proprietaire = User::factory()->create();
    $autre = User::factory()->create();
    $transcription = mp3Transcription($proprietaire);

    $this->actingAs($autre)
        ->get(lienSigne('transcriptions.mp3', [$transcription]))
        ->assertNotFound();

    $this->actingAs($proprietaire)
        ->get(route('transcriptions.mp3', [$transcription]))
        ->assertForbidden();
});

// Critère Sécurité : un lien modifié pour viser une autre transcription est refusé.
it('refuse un lien modifié pour viser une autre transcription', function () {
    $utilisateur = User::factory()->create();
    $transcription = mp3Transcription($utilisateur);
    $autre = Transcription::factory()->termine()->for($utilisateur)->create(['mp3_chemin' => null]);

    $lien = lienSigne('transcriptions.mp3', [$transcription]);
    $modifie = str_replace('/transcriptions/'.$transcription->getKey().'/mp3', '/transcriptions/'.$autre->getKey().'/mp3', $lien);

    $this->actingAs($utilisateur)->get($modifie)->assertForbidden();
});

// Critère Sécurité : un lien expiré est refusé ; il vaut jusqu'à la durée de config (voxia.telechargement).
it('refuse un lien expiré', function () {
    $utilisateur = User::factory()->create();
    $transcription = mp3Transcription($utilisateur);
    $this->actingAs($utilisateur);
    $lien = lienSigne('transcriptions.mp3', [$transcription]);

    $this->get($lien)->assertOk();

    $this->travel(config('voxia.telechargement.duree_lien_minutes') + 1)->minutes();

    $this->get($lien)->assertForbidden();
});

// Critère F3 / conservation : un MP3 supprimé par la règle de conservation est signalé sur le bouton.
it('signale clairement un MP3 supprimé par la règle de conservation', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(['mp3_chemin' => null]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee(__('transcriptions.lecture.mp3_supprime_bouton'))
        ->assertSee(__('transcriptions.lecture.mp3_supprime', ['jours' => config('voxia.retention.media_days')]))
        ->assertDontSee(route('transcriptions.mp3', [$transcription]), false);

    $this->get(lienSigne('transcriptions.mp3', [$transcription]))->assertNotFound();
});

// Critère Sécurité : un fichier absent du disque ne renvoie rien d'autre qu'un 404.
it('répond 404 quand le fichier a disparu du disque', function () {
    $utilisateur = User::factory()->create();
    $transcription = mp3Transcription($utilisateur);
    Storage::disk(config('voxia.stockage.disque'))->delete('transcriptions/1/extrait.mp3');

    $this->actingAs($utilisateur)
        ->get(lienSigne('transcriptions.mp3', [$transcription]))
        ->assertNotFound();
});

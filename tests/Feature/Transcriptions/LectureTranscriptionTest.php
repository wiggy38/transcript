<?php

use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

uses(RefreshDatabase::class);

/** Crée une transcription terminée avec deux paragraphes aux débuts connus. */
function transcriptionAvecDeuxParagraphes(User $utilisateur, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create($attributs);

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 13_400,
        'texte' => 'Bienvenue dans l’atelier.',
    ]);
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 2,
        'debut_ms' => 13_400,
        'fin_ms' => 19_000,
        'texte' => 'Le secret, c’est de centrer la terre.',
    ]);

    return $transcription;
}

// Critère F2 : le clic sur un horodatage positionne la lecture à 1 seconde près.
// Le lecteur reçoit le début exact du paragraphe, en millisecondes. L'horodatage affiché
// (mm:ss) tronque la seconde : l'écart reste inférieur à 1 seconde.
it('positionne la lecture au début exact du paragraphe cliqué, à 1 seconde près', function () {
    $utilisateur = User::factory()->create();
    $transcription = transcriptionAvecDeuxParagraphes($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('data-debut-ms="13400"', false)
        ->assertSee('@click="aller(13400)"', false)
        ->assertSee('aria-label="'.__('transcriptions.lecture.lire_a', ['temps' => '00:13']).'"', false);

});

// Critère F2 : même précision sur le MP3 (TikTok et fichiers). Le navigateur ne déplace la lecture
// avec précision que si le serveur accepte les requêtes d'intervalle (Range) : 206 Partial Content.
it('sert le MP3 avec les requêtes d’intervalle, au propriétaire seulement', function () {
    Storage::fake(config('voxia.stockage.disque'));
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->termine()->fichier()->for($utilisateur)->create([
        'mp3_chemin' => 'transcriptions/1/extrait.mp3',
    ]);
    Storage::disk(config('voxia.stockage.disque'))->put('transcriptions/1/extrait.mp3', str_repeat('0', 4096));

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.audio', $transcription), ['Range' => 'bytes=1024-2047'])
        ->assertStatus(206)
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('Content-Range', 'bytes 1024-2047/4096');

    $this->actingAs(User::factory()->create())
        ->get(route('transcriptions.audio', $transcription))
        ->assertNotFound();
});

// Critère F2 : une transcription de 3 heures s'affiche et reste fluide au défilement.
// Le test vérifie que tout le texte part en une réponse rapide ; la fluidité du défilement
// se mesure dans le navigateur (voir docs/avancement.md).
it('affiche une transcription de 3 heures en entier, sans attendre', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->longue(3 * 3600)->for($utilisateur)->create();
    $this->actingAs($utilisateur);

    $paragraphes = $transcription->paragraphs()->count();

    $debut = microtime(true);
    $html = $this->get(route('transcriptions.show', $transcription))->assertOk()->getContent();
    $duree = microtime(true) - $debut;

    expect($paragraphes)->toBeGreaterThan(400)
        ->and(substr_count($html, 'data-paragraphe'))->toBe($paragraphes)
        ->and($duree)->toBeLessThan(2.0);
});

it('affiche le titre, la plateforme, la durée, la langue et le nombre de mots dans l’en-tête', function () {
    $utilisateur = User::factory()->create();
    $transcription = transcriptionAvecDeuxParagraphes($utilisateur, [
        'titre' => 'Vlog #34 — Mon atelier',
        'duree_secondes' => 723,
        'langue_detectee' => 'fr',
        'nombre_mots' => 1842,
    ]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('Vlog #34 — Mon atelier')
        ->assertSee('YouTube')
        ->assertSee('12:03')
        ->assertSee('FR')
        ->assertSee(__('transcriptions.lecture.mots', ['nombre' => Number::format(1842)]));
});

it('lit une vidéo YouTube avec l’API IFrame, et un MP3 pour TikTok ou un fichier', function () {
    $utilisateur = User::factory()->create();
    $youtube = transcriptionAvecDeuxParagraphes($utilisateur);
    $fichier = Transcription::factory()->termine()->fichier()->for($utilisateur)->create([
        'mp3_chemin' => 'transcriptions/2/extrait.mp3',
    ]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $youtube))
        ->assertSee($youtube->id_video)
        ->assertDontSee(route('transcriptions.audio', $youtube), false);

    $this->get(route('transcriptions.show', $fichier))
        ->assertSee('src="'.route('transcriptions.audio', $fichier).'"', false)
        ->assertDontSee('x-ref="youtube"', false);
});

it('affiche le bandeau Passe au Pro sur un aperçu, en fin de texte', function () {
    $utilisateur = User::factory()->create();
    $transcription = transcriptionAvecDeuxParagraphes($utilisateur, ['est_apercu' => true]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee(__('transcriptions.lecture.bandeau_pro'));
});

// Critère F4 : la barre d'export branche les formats du plan ; un format verrouillé dit quel plan le débloque.
it('affiche TXT et SRT au plan Gratuit, et PDF et VTT verrouillés avec le plan qui les débloque', function () {
    $utilisateur = User::factory()->create();
    $transcription = transcriptionAvecDeuxParagraphes($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('href="'.route('transcriptions.export', [$transcription, 'txt', 'horodatages' => 1]).'"', false)
        ->assertSee('href="'.route('transcriptions.export', [$transcription, 'srt', 'horodatages' => 1]).'"', false)
        ->assertDontSee('href="'.route('transcriptions.export', [$transcription, 'pdf', 'horodatages' => 1]).'"', false)
        ->assertSee('aria-describedby="verrou-pdf"', false)
        ->assertSee('aria-describedby="verrou-vtt"', false)
        ->assertSee(__('transcriptions.lecture.exporter_verrouille', ['plan' => 'Pro']))
        ->assertSee(__('transcriptions.lecture.exports_bientot'))
        ->assertSee('aria-describedby="exports-bientot"', false);
});

it('active tous les formats au plan Pro', function () {
    config(['voxia.droits.plan_reel' => true]);
    $utilisateur = User::factory()->create(['plan' => Plan::Pro]);
    $transcription = transcriptionAvecDeuxParagraphes($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertSee('href="'.route('transcriptions.export', [$transcription, 'pdf', 'horodatages' => 1]).'"', false)
        ->assertSee('href="'.route('transcriptions.export', [$transcription, 'vtt', 'horodatages' => 1]).'"', false)
        ->assertDontSee('aria-describedby="verrou-pdf"', false);
});

it('n’affiche pas la lecture tant que la transcription n’est pas terminée', function () {
    $utilisateur = User::factory()->create();
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'statut' => StatutTranscription::Transcription,
    ]);
    $this->actingAs($utilisateur);

    $this->get(route('transcriptions.show', $transcription))
        ->assertOk()
        ->assertDontSee('data-paragraphe', false)
        ->assertSee('wire:poll.3s', false);
});

<?php

use App\Contracts\VideoFetcher;
use App\Data\MetadonneesVideo;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\SourceTranscription;
use App\Exceptions\ErreurVideo;
use App\Jobs\ProcessTranscription;
use App\Jobs\RecupererApercu;
use App\Livewire\NouvelleTranscription;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Quota\QuotaService;
use App\Services\Video\FauxVideoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const LIEN_TRANSCRIPTION = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

/** Fausse lecture de métadonnées : une vidéo de 10 minutes, sauf indication contraire. */
function fauxVideos(?MetadonneesVideo $metadonnees = null, ?ErreurVideo $erreur = null): void
{
    app()->instance(VideoFetcher::class, new FauxVideoFetcher(
        metadonnees: $metadonnees ?? new MetadonneesVideo(
            id: 'dQw4w9WgXcQ',
            titre: 'Ma vidéo de test',
            dureeSecondes: 600,
            miniatureUrl: 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            chaine: 'Ma chaîne de test',
            estLive: false,
        ),
        erreurMetadonnees: $erreur,
    ));
}

function metadonneesDeDuree(int $secondes): MetadonneesVideo
{
    return new MetadonneesVideo(
        id: 'dQw4w9WgXcQ',
        titre: 'Longue vidéo',
        dureeSecondes: $secondes,
        miniatureUrl: null,
        chaine: null,
        estLive: false,
    );
}

function utilisateurVerifie(Plan $plan = Plan::Free): User
{
    return User::factory()->create(['plan' => $plan]);
}

// Critère F1 : un lien public valide ne demande rien de plus que le lancement.
it('lit l\'aperçu d\'un lien valide en file et n\'écrit encore aucune transcription', function () {
    Queue::fake();
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSet('apercu.etat', 'en_cours')
        ->assertSee(__('transcriptions.lien.lecture'));

    Queue::assertPushed(RecupererApercu::class);
    expect(Transcription::count())->toBe(0);
});

it('affiche titre, durée, chaîne et miniature avant le bouton de lancement', function () {
    fauxVideos();
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSee('Ma vidéo de test')
        ->assertSee('10:00')
        ->assertSee('Ma chaîne de test')
        ->assertSee('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg')
        ->assertSee(__('transcriptions.apercu.lancer'));
});

it('lance le traitement d\'un lien valide de 10 minutes sans autre action que le lancement', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = utilisateurVerifie();
    $this->actingAs($utilisateur);

    $composant = Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->call('lancerLien');

    $transcription = Transcription::sole();
    $composant->assertRedirect(route('transcriptions.show', $transcription));

    expect($transcription->user_id)->toBe($utilisateur->id)
        ->and($transcription->source)->toBe(SourceTranscription::Youtube)
        ->and($transcription->id_video)->toBe('dQw4w9WgXcQ');

    Queue::assertPushedOn('default', ProcessTranscription::class);
});

// Critère F1 : privée, supprimée, région, live et échec technique s'affichent avant tout décompte.
it('affiche le motif d\'un lien refusé avant lancement, sans rien créer ni décompter', function (MotifEchec $motif, bool $live) {
    if ($live) {
        app()->instance(VideoFetcher::class, new FauxVideoFetcher(metadonnees: new MetadonneesVideo(
            id: 'dQw4w9WgXcQ', titre: 'Live', dureeSecondes: 0, miniatureUrl: null, chaine: null, estLive: true,
        )));
    } else {
        fauxVideos(erreur: new ErreurVideo($motif, 'test'));
    }

    $this->actingAs(utilisateurVerifie());
    $this->mock(QuotaService::class)->shouldNotReceive('verifier');

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSee(__('videos.motifs.'.$motif->value))
        ->call('lancerLien')
        ->assertSee(__('videos.motifs.'.$motif->value));

    expect(Transcription::count())->toBe(0);
})->with([
    'privée' => [MotifEchec::VideoPrivee, false],
    'supprimée' => [MotifEchec::VideoSupprimee, false],
    'bloquée dans la région' => [MotifEchec::BloqueeRegion, false],
    'live en cours' => [MotifEchec::LiveEnCours, true],
    'échec technique' => [MotifEchec::ErreurTechnique, false],
]);

it('refuse un lien non reconnu sans lire de métadonnées', function () {
    Queue::fake();
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', 'https://example.com/video.mp4')
        ->assertSet('apercu.etat', 'erreur')
        ->assertSee(__('videos.motifs.lien_invalide'))
        ->call('lancerLien');

    Queue::assertNotPushed(RecupererApercu::class);
    expect(Transcription::count())->toBe(0);
});

it('refuse le lancement tant que l\'aperçu n\'est pas prêt', function () {
    Queue::fake();
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->call('lancerLien')
        ->assertSee(__('transcriptions.lien.apercu_pas_pret'));

    expect(Transcription::count())->toBe(0);
});

it('refuse au plan payant une vidéo plus longue que son plan, avant tout décompte', function () {
    fauxVideos(metadonneesDeDuree(3 * 3600 + 60));
    $this->actingAs(utilisateurVerifie(Plan::Pro));
    $this->mock(QuotaService::class)->shouldNotReceive('verifier');

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSee(__('videos.motifs.trop_longue'))
        ->call('lancerLien');

    expect(Transcription::count())->toBe(0);
});

it('laisse le Gratuit lancer une vidéo plus longue, en annonçant que seules les 10 premières minutes seront transcrites', function () {
    fauxVideos(metadonneesDeDuree(3600));
    Queue::fake([ProcessTranscription::class]);
    $this->actingAs(utilisateurVerifie(Plan::Free));

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSee(__('transcriptions.apercu.limite', ['minutes' => 10]))
        ->assertDontSee(__('videos.motifs.trop_longue'))
        ->call('lancerLien');

    expect(Transcription::count())->toBe(1);
    Queue::assertPushed(ProcessTranscription::class);
});

it('envoie la langue choisie, et la détection automatique sinon', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->set('langue', 'en')
        ->call('lancerLien');

    expect(Transcription::sole()->langue_forcee)->toBe('en');
});

it('laisse la langue vide, donc détectée, par défaut', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->call('lancerLien');

    expect(Transcription::sole()->langue_forcee)->toBeNull();
});

it('refuse une langue absente de la liste', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->set('langue', 'xx')
        ->call('lancerLien')
        ->assertHasErrors(['langue']);

    expect(Transcription::count())->toBe(0);
});

// Critère F1 : un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante.
it('renvoie vers la transcription existante du même utilisateur, sans nouveau décompte', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = utilisateurVerifie();
    $existante = Transcription::factory()->for($utilisateur)->create(['id_video' => 'dQw4w9WgXcQ']);
    $this->actingAs($utilisateur);
    $this->mock(QuotaService::class)->shouldNotReceive('verifier');

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->assertSee(__('transcriptions.apercu.deja'))
        ->call('lancerLien')
        ->assertRedirect(route('transcriptions.show', $existante));

    expect(Transcription::count())->toBe(1);
    Queue::assertNothingPushed();
});

it('ne renvoie pas vers la transcription d\'un autre utilisateur', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    Transcription::factory()->create(['id_video' => 'dQw4w9WgXcQ']);
    $this->actingAs(utilisateurVerifie());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->call('lancerLien');

    expect(Transcription::count())->toBe(2);
    Queue::assertPushed(ProcessTranscription::class);
});

it('vérifie le quota avant de créer une nouvelle transcription', function () {
    fauxVideos();
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = utilisateurVerifie();
    $this->actingAs($utilisateur);
    $this->mock(QuotaService::class)
        ->shouldReceive('verifier')
        ->once()
        ->with(Mockery::on(fn (User $u) => $u->is($utilisateur)), Mockery::any());

    Livewire::test(NouvelleTranscription::class)
        ->set('lien', LIEN_TRANSCRIPTION)
        ->call('lancerLien');

    expect(Transcription::count())->toBe(1);
});

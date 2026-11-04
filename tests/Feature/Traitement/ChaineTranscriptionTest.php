<?php

use App\Contracts\TranscriptionEngine;
use App\Data\MetadonneesVideo;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Events\TranscriptionCompleted;
use App\Events\TranscriptionFailed;
use App\Exceptions\ErreurVideo;
use App\Jobs\ProcessTranscription;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Audio\FauxAudioExtractor;
use App\Services\Traitement\ChaineTranscription;
use App\Services\Video\FauxVideoFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const LIEN_TRAITEMENT = 'https://www.youtube.com/watch?v=abcdefghijk';

/** Transcription de test, en attente, rattachée à un compte Pro. */
function transcriptionEnAttente(string $source = 'lien'): Transcription
{
    $utilisateur = User::factory()->create(['plan' => Plan::Pro]);

    return $source === 'fichier'
        ? Transcription::factory()->fichier()->for($utilisateur)->create(['statut' => StatutTranscription::EnAttente])
        : Transcription::factory()->for($utilisateur)->create([
            'url_origine' => LIEN_TRAITEMENT,
            'id_video' => null,
            'titre' => null,
            'duree_secondes' => null,
            'miniature_url' => null,
            'chaine' => null,
            'statut' => StatutTranscription::EnAttente,
        ]);
}

/** Lance la chaîne sur une transcription et renvoie ses journaux dans l'ordre. */
function executerChaine(Transcription $transcription, ?string $cheminFichier = null)
{
    app(ChaineTranscription::class)->executer($transcription, $cheminFichier);

    return ProcessingLog::where('transcription_id', $transcription->getKey())->orderBy('id')->get();
}

it('transcrit un lien de bout en bout : six étapes journalisées, MP3 stocké, texte enregistré', function () {
    $moteur = preparerTraitement();
    $moteur->repondre(transcriptionBruteTest());
    Event::fake([TranscriptionCompleted::class]);

    $transcription = transcriptionEnAttente();
    $journaux = executerChaine($transcription);

    $transcription->refresh();

    expect($transcription->statut)->toBe(StatutTranscription::Termine)
        ->and($transcription->titre)->toBe('Vidéo factice')
        ->and($transcription->id_video)->toBe('abcdefghijk')
        ->and($transcription->langue_detectee)->toBe('fr')
        ->and($transcription->nombre_mots)->toBe(3)
        ->and((float) $transcription->cout_estime_usd)->toBe(0.0367)
        ->and($transcription->date_termine)->not->toBeNull();

    expect($journaux->pluck('etape')->map(fn ($etape) => $etape->value)->all())->toBe(ETAPES_TRAITEMENT)
        ->and($journaux->every(fn ($ligne) => $ligne->succes === true))->toBeTrue()
        ->and($journaux->every(fn ($ligne) => $ligne->duree_ms !== null))->toBeTrue();

    expect((float) $journaux->firstWhere('etape.value', 'transcription')->cout_usd)->toBe(0.0367);

    expect($transcription->paragraphs()->count())->toBe(2)
        ->and($transcription->words()->count())->toBe(3);

    Storage::disk('local')->assertExists($transcription->mp3_chemin);
    Event::assertDispatched(TranscriptionCompleted::class);
    Event::assertNotDispatched(TranscriptionFailed::class);

    // Le décompte est écrit dans la transaction qui passe la transcription à Terminé : une seule ligne (Pro, en secondes).
    expect(DB::table('usage_records')->where('transcription_id', $transcription->getKey())->count())->toBe(1)
        ->and(dossierTemporaireVide())->toBeTrue();
});

it('passe en échec à chaque étape avec son motif, sans consommation, et nettoie les fichiers temporaires', function (string $etape, string $motif) {
    match ($etape) {
        'metadonnees' => preparerTraitement(new FauxVideoFetcher(
            erreurMetadonnees: new ErreurVideo(MotifEchec::VideoPrivee, 'vidéo privée'),
        )),
        'telechargement' => preparerTraitement(new FauxVideoFetcher(
            erreurTelechargement: new ErreurVideo(MotifEchec::ErreurTechnique, 'code 1'),
        )),
        'extraction_audio' => preparerTraitement(extracteur: new FauxAudioExtractor(
            erreur: new ErreurVideo(MotifEchec::SansAudio, 'aucun flux audio'),
        )),
        'transcription' => preparerTraitement()->echouer(new ErreurVideo(MotifEchec::ErreurTechnique, 'quota')),
        'enregistrement' => preparerEnregistrementEnPanne(),
    };

    Event::fake([TranscriptionFailed::class, TranscriptionCompleted::class]);

    $transcription = transcriptionEnAttente();
    $journaux = executerChaine($transcription);

    $transcription->refresh();

    expect($transcription->statut)->toBe(StatutTranscription::Echec)
        ->and($transcription->motif_echec)->toBe(MotifEchec::from($motif))
        ->and($transcription->date_echec)->not->toBeNull()
        ->and($transcription->words()->count())->toBe(0)
        ->and($transcription->paragraphs()->count())->toBe(0);

    // Les étapes après l'échec n'ont pas de ligne ; la dernière est celle qui a échoué.
    $attendues = array_slice(ETAPES_TRAITEMENT, 0, array_search($etape, ETAPES_TRAITEMENT) + 1);

    expect($journaux->pluck('etape')->map(fn ($e) => $e->value)->all())->toBe($attendues)
        ->and($journaux->last()->succes)->toBeFalse()
        ->and($journaux->slice(0, -1)->every(fn ($ligne) => $ligne->succes === true))->toBeTrue();

    Event::assertDispatched(
        TranscriptionFailed::class,
        fn (TranscriptionFailed $evenement) => $evenement->motif === MotifEchec::from($motif),
    );
    Event::assertNotDispatched(TranscriptionCompleted::class);

    expect(DB::table('usage_records')->count())->toBe(0)
        ->and(dossierTemporaireVide())->toBeTrue();
})->with([
    'métadonnées : vidéo privée' => ['metadonnees', 'video_privee'],
    'téléchargement : erreur technique' => ['telechargement', 'erreur_technique'],
    'MP3 : aucune piste audio' => ['extraction_audio', 'sans_audio'],
    'transcription : erreur technique' => ['transcription', 'erreur_technique'],
    'enregistrement : disque en erreur' => ['enregistrement', 'erreur_technique'],
]);

it('refuse une vidéo privée avant tout téléchargement et ne décompte rien', function () {
    preparerTraitement(new FauxVideoFetcher(
        erreurMetadonnees: new ErreurVideo(MotifEchec::VideoPrivee),
    ));

    $transcription = transcriptionEnAttente();
    $journaux = executerChaine($transcription);

    expect($transcription->refresh()->motif_echec)->toBe(MotifEchec::VideoPrivee)
        ->and($journaux)->toHaveCount(1)
        ->and($journaux->first()->message)->toContain('video_privee')
        ->and(DB::table('usage_records')->count())->toBe(0);
});

it('ne retente pas un motif métier : seule une erreur technique est relancée sur les métadonnées', function () {
    config(['voxia.traitement.tentatives.metadonnees' => 3]);

    $videos = new FauxVideoFetcher(erreurMetadonnees: new ErreurVideo(MotifEchec::VideoSupprimee));
    preparerTraitement($videos);

    executerChaine(transcriptionEnAttente());

    expect($videos->appelsMetadonnees)->toBe(1);
});

it('relance les métadonnées sur erreur technique puis réussit', function () {
    config(['voxia.traitement.tentatives.metadonnees' => 2]);

    $videos = new FauxVideoFetcher(erreursMetadonnees: [new ErreurVideo(MotifEchec::ErreurTechnique, 'délai')]);
    $moteur = preparerTraitement($videos);
    $moteur->repondre(transcriptionBruteTest());

    $transcription = transcriptionEnAttente();
    executerChaine($transcription);

    expect($videos->appelsMetadonnees)->toBe(2)
        ->and($transcription->refresh()->statut)->toBe(StatutTranscription::Termine);
});

it('refuse un live en cours sans le télécharger', function () {
    preparerTraitement(new FauxVideoFetcher(
        metadonnees: new MetadonneesVideo('abcdefghijk', 'Live', 900, null, null, estLive: true),
    ));

    $transcription = transcriptionEnAttente();
    $journaux = executerChaine($transcription);

    expect($transcription->refresh()->motif_echec)->toBe(MotifEchec::LiveEnCours)
        ->and($journaux)->toHaveCount(1);
});

it('refuse une vidéo plus longue que la durée d\'un plan payant, sans rien transcrire', function () {
    preparerTraitement(new FauxVideoFetcher(
        metadonnees: new MetadonneesVideo('abcdefghijk', 'Longue', 3 * 3600 + 1, null, null, estLive: false),
    ));

    $utilisateur = User::factory()->create(['plan' => Plan::Pro]);
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'url_origine' => LIEN_TRAITEMENT,
        'id_video' => null,
        'statut' => StatutTranscription::EnAttente,
    ]);

    executerChaine($transcription);

    expect($transcription->refresh()->motif_echec)->toBe(MotifEchec::TropLongue)
        ->and($transcription->statut)->toBe(StatutTranscription::Echec);
});

it('transcrit seulement les 10 premières minutes d\'un lien plus long au Gratuit, et le marque comme aperçu', function () {
    $moteur = preparerTraitement(
        new FauxVideoFetcher(metadonnees: new MetadonneesVideo('abcdefghijk', 'Longue', 3600, null, null, estLive: false)),
        new FauxAudioExtractor(dureeSecondes: 3600),
    );
    $moteur->repondre(transcriptionBruteTest());

    $utilisateur = User::factory()->create(['plan' => Plan::Free]);
    $transcription = Transcription::factory()->for($utilisateur)->create([
        'url_origine' => LIEN_TRAITEMENT,
        'id_video' => null,
        'statut' => StatutTranscription::EnAttente,
    ]);

    executerChaine($transcription);

    $transcription->refresh();

    expect($transcription->statut)->toBe(StatutTranscription::Termine)
        ->and($transcription->est_apercu)->toBeTrue()
        ->and($transcription->motif_echec)->toBeNull()
        ->and($transcription->duree_secondes)->toBe(3600)
        ->and($transcription->mp3_duree_secondes)->toBe(600);
});

it('transcrit seulement les 10 premières minutes d\'un fichier plus long au Gratuit', function () {
    $moteur = preparerTraitement(extracteur: new FauxAudioExtractor(dureeSecondes: 1800));
    $moteur->repondre(transcriptionBruteTest());

    $chemin = storage_path('framework/testing/traitement/entree-longue.mp3');
    File::ensureDirectoryExists(dirname($chemin));
    File::put($chemin, 'entree');

    $utilisateur = User::factory()->create(['plan' => Plan::Free]);
    $transcription = Transcription::factory()->fichier()->for($utilisateur)->create([
        'statut' => StatutTranscription::EnAttente,
    ]);

    executerChaine($transcription, $chemin);

    $transcription->refresh();

    expect($transcription->statut)->toBe(StatutTranscription::Termine)
        ->and($transcription->est_apercu)->toBeTrue()
        ->and($transcription->duree_secondes)->toBe(1800)
        ->and($transcription->mp3_duree_secondes)->toBe(600);
});

it('traite un fichier local sans le télécharger ni le supprimer', function () {
    $moteur = preparerTraitement();
    $moteur->repondre(transcriptionBruteTest());

    $fichier = storage_path('framework/testing/traitement/entree-locale.mp3');
    File::ensureDirectoryExists(dirname($fichier));
    File::put($fichier, 'contenu source');

    $transcription = transcriptionEnAttente('fichier');
    $journaux = executerChaine($transcription, $fichier);

    expect($transcription->refresh()->statut)->toBe(StatutTranscription::Termine)
        ->and($transcription->titre)->toBe('entree-locale')
        ->and($journaux->firstWhere('etape.value', 'telechargement')->message)->toContain('rien à télécharger')
        ->and(file_exists($fichier))->toBeTrue()
        ->and(file_get_contents($fichier))->toBe('contenu source')
        ->and(dossierTemporaireVide())->toBeTrue();
});

it('passe en échec une tâche abandonnée par Laravel (délai total dépassé)', function () {
    Event::fake([TranscriptionFailed::class]);

    $transcription = transcriptionEnAttente();
    $transcription->update(['statut' => StatutTranscription::Transcription]);

    (new ProcessTranscription($transcription->getKey()))->failed(new RuntimeException('délai total dépassé'));

    expect($transcription->refresh()->statut)->toBe(StatutTranscription::Echec)
        ->and($transcription->motif_echec)->toBe(MotifEchec::ErreurTechnique);

    Event::assertDispatched(TranscriptionFailed::class);
});

it('ne modifie pas une transcription déjà terminée quand la tâche est abandonnée', function () {
    $transcription = transcriptionEnAttente();
    $transcription->update(['statut' => StatutTranscription::Termine]);

    (new ProcessTranscription($transcription->getKey()))->failed(new RuntimeException('tard'));

    expect($transcription->refresh()->statut)->toBe(StatutTranscription::Termine)
        ->and($transcription->motif_echec)->toBeNull();
});

/** Disque de stockage dont la racine est un fichier : toute écriture échoue. */
function preparerEnregistrementEnPanne(): void
{
    $racine = storage_path('framework/testing/traitement/racine-cassee');
    File::ensureDirectoryExists(dirname($racine));
    File::put($racine, 'pas un dossier');

    preparerTraitement();
    config([
        'voxia.stockage.disque' => 'casse',
        'filesystems.disks.casse' => ['driver' => 'local', 'root' => $racine, 'throw' => false, 'report' => false],
    ]);
    app(TranscriptionEngine::class)->repondre(transcriptionBruteTest());
}

<?php

use App\Enums\MotifEchec;
use App\Exceptions\ErreurVideo;
use App\Services\Transcription\ElevenLabsScribeEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

// Moteur ElevenLabs Scribe v2 : requêtes et réponses simulées (Http::fake), aucun appel réseau.
// La clé utilisée ici est factice.

const ADRESSE_SCRIBE = 'api.elevenlabs.io/v1/speech-to-text';

beforeEach(function () {
    config(['services.elevenlabs.key' => 'cle-de-test']);
    config(['services.elevenlabs.model' => 'scribe_v2']);
    Sleep::fake();
});

/** Un MP3 factice sur disque (le moteur l'envoie tel quel, il ne le décode pas). */
function mp3Test(int $octets = 2048): string
{
    $chemin = dossierAudioTest().'/source.mp3';
    file_put_contents($chemin, str_repeat('x', $octets));

    return $chemin;
}

function reponseScribeTest(): array
{
    return json_decode(file_get_contents(base_path('tests/fixtures/elevenlabs/francais_redige.json')), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Champs texte d'une requête multipart. Doit être appelée pendant l'envoi (dans le
 * callback de Http::fake) : le flux du MP3 est fermé juste après.
 *
 * @return array<string, string>
 */
function champsRequeteTest(Request $requete): array
{
    preg_match_all('/name="([^"]+)"\r\n\r\n([^\r\n]*)/', $requete->body(), $correspondances, PREG_SET_ORDER);

    return collect($correspondances)->mapWithKeys(fn ($c) => [$c[1] => $c[2]])->all();
}

/** Exécute l'appel et renvoie l'erreur produite, ou null. */
function erreurTranscriptionTest(callable $appel): ?ErreurVideo
{
    try {
        $appel();
    } catch (ErreurVideo $erreur) {
        return $erreur;
    }

    return null;
}

// Critère : le MP3 part vers ElevenLabs, avec la langue forcée si l'utilisateur en a choisi une.

test('envoie le MP3 à Scribe v2 avec la clé, en horodatage au mot, sans langue si non forcée', function () {
    $envoi = [];
    Http::fake(function (Request $requete) use (&$envoi) {
        $envoi = ['url' => $requete->url(), 'cle' => $requete->header('xi-api-key')[0] ?? null, 'champs' => champsRequeteTest($requete)];

        return Http::response(reponseScribeTest(), 200);
    });

    (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60);

    expect($envoi['url'])->toContain(ADRESSE_SCRIBE)
        ->and($envoi['cle'])->toBe('cle-de-test')
        ->and($envoi['champs'])->toMatchArray(['model_id' => 'scribe_v2', 'timestamps_granularity' => 'word'])
        ->and($envoi['champs'])->not->toHaveKey('language_code');
});

test('envoie la langue forcée par l’utilisateur', function () {
    $champs = [];
    Http::fake(function (Request $requete) use (&$champs) {
        $champs = champsRequeteTest($requete);

        return Http::response(reponseScribeTest(), 200);
    });

    (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60, 'fr');

    expect($champs)->toHaveKey('language_code', 'fr');
});

// Critère : le résultat est neutre (langue, mots en millisecondes, texte, coût estimé).

test('renvoie un objet neutre : langue, mots en millisecondes, texte et coût de config', function () {
    Http::fake(['*' => Http::response(reponseScribeTest(), 200)]);

    $resultat = (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 3600);

    expect($resultat->langueDetectee)->toBe('fra')
        ->and($resultat->mots[0]->texte)->toBe('Bonjour')
        ->and($resultat->mots[0]->debutMs)->toBe(100)
        ->and($resultat->mots[0]->finMs)->toBe(400)
        ->and($resultat->texte)->toStartWith('Bonjour à tous')
        ->and($resultat->coutEstimeUsd)->toBe(0.22);
});

test('ignore espaces et événements sonores : seuls les mots sont renvoyés', function () {
    $reponse = json_decode(file_get_contents(base_path('tests/fixtures/elevenlabs/muette_enregistree.json')), true, 512, JSON_THROW_ON_ERROR);
    Http::fake(['*' => Http::response($reponse, 200)]);

    $resultat = (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 2);

    expect($resultat->mots)->toBe([])
        ->and($resultat->texte)->toBe('[beep]')
        ->and($resultat->langueDetectee)->toBe('eng');
});

// Critère : échecs gérés avec tentatives, puis motif erreur_technique.

test('une panne passagère est retentée et réussit au second essai', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['detail' => 'erreur'], 500)
        ->push(reponseScribeTest(), 200),
    ]);

    $resultat = (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60);

    expect($resultat->mots)->not->toBeEmpty();
    Http::assertSentCount(2);
});

test('clé invalide : trois tentatives puis erreur_technique', function () {
    Http::fake(['*' => Http::response(['detail' => ['status' => 'invalid_api_key', 'message' => 'Invalid API key']], 401)]);

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur)->not->toBeNull()
        ->and($erreur->motif)->toBe(MotifEchec::ErreurTechnique);
    Http::assertSentCount(3);
});

test('quota épuisé : erreur_technique après les tentatives', function () {
    Http::fake(['*' => Http::response(['detail' => ['status' => 'quota_exceeded']], 401)]);

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique)
        ->and($erreur->getMessage())->toContain('quota');
    Http::assertSentCount(3);
});

test('fichier refusé par ElevenLabs (413) : erreur_technique après les tentatives', function () {
    Http::fake(['*' => Http::response(['detail' => 'too large'], 413)]);

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique);
    Http::assertSentCount(3);
});

test('délai dépassé : erreur_technique après les tentatives', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 900001 milliseconds'));

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique)
        ->and($erreur->getMessage())->toContain('délai dépassé');
});

test('réponse illisible : erreur_technique après les tentatives', function () {
    Http::fake(['*' => Http::response(['text' => 'sans mots'], 200)]);

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique);
    Http::assertSentCount(3);
});

// Critère : une limite locale échoue sans appel.

test('clé absente : erreur_technique sans aucun appel', function () {
    config(['services.elevenlabs.key' => '']);
    Http::fake();

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique);
    Http::assertNothingSent();
});

test('fichier trop volumineux : erreur_technique sans aucun appel', function () {
    config(['voxia.transcription.max_size_mb' => 0]);
    Http::fake();

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), 60));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique)
        ->and($erreur->getMessage())->toContain('trop volumineux');
    Http::assertNothingSent();
});

test('durée au-delà de la limite de l’API : erreur_technique sans aucun appel', function () {
    Http::fake();
    $limite = (int) config('voxia.transcription.max_duration_seconds');

    $erreur = erreurTranscriptionTest(fn () => (new ElevenLabsScribeEngine)->transcrire(mp3Test(), $limite + 1));

    expect($erreur->motif)->toBe(MotifEchec::ErreurTechnique);
    Http::assertNothingSent();
});

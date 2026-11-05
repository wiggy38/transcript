<?php

use App\Enums\Plan;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

// Exports DOCX et JSON, réservés au Business (étape 5 de la V2).
uses(RefreshDatabase::class);

beforeEach(fn () => config(['voxia.droits.plan_reel' => true]));

/** Compte vérifié au plan demandé. */
function exportBusinessCompte(Plan $plan): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()]);
}

/** Transcription terminée avec accents, guillemets français, et écritures non latines. */
function exportBusinessTranscription(User $utilisateur): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create([
        'titre' => 'Café « déjà vu » — 日本語',
        'url_origine' => 'https://www.youtube.com/watch?v=abc123',
    ]);

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1, 'debut_ms' => 0, 'fin_ms' => 13_400, 'texte' => 'Bienvenue à l’atelier, 你好.',
    ]);
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 2, 'debut_ms' => 65_000, 'fin_ms' => 70_000, 'texte' => 'Ça marche — très bien.',
    ]);

    return $transcription;
}

/** Texte du document Word (word/document.xml) d'une réponse DOCX. */
function exportBusinessTexteDocx(string $contenu): string
{
    $chemin = tempnam(sys_get_temp_dir(), 'test-docx-');
    file_put_contents($chemin, $contenu);

    $archive = new ZipArchive;
    expect($archive->open($chemin))->toBeTrue();
    $xml = (string) $archive->getFromName('word/document.xml');
    $archive->close();
    unlink($chemin);

    return $xml;
}

test('Business : export DOCX avec titre, lien source, date et horodatages, accents et écritures non latines conservés', function () {
    $utilisateur = exportBusinessCompte(Plan::Business);
    $transcription = exportBusinessTranscription($utilisateur);
    $this->actingAs($utilisateur);

    $reponse = $this->get(URL::temporarySignedRoute('transcriptions.export', now()->addMinutes(5), [$transcription, 'docx']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $xml = exportBusinessTexteDocx($reponse->getContent());

    expect($xml)->toContain('Café « déjà vu » — 日本語')
        ->and($xml)->toContain('https://www.youtube.com/watch?v=abc123')
        ->and($xml)->toContain('Date : ')
        ->and($xml)->toContain('[00:00] Bienvenue à l’atelier, 你好.')
        ->and($xml)->toContain('[01:05] Ça marche — très bien.');
});

test('Business : export JSON avec segments horodatés en millisecondes, accents et écritures non latines', function () {
    $utilisateur = exportBusinessCompte(Plan::Business);
    $transcription = exportBusinessTranscription($utilisateur);
    $this->actingAs($utilisateur);

    $reponse = $this->get(URL::temporarySignedRoute('transcriptions.export', now()->addMinutes(5), [$transcription, 'json']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    $donnees = json_decode($reponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

    expect($donnees['titre'])->toBe('Café « déjà vu » — 日本語')
        ->and($donnees['source'])->toBe('https://www.youtube.com/watch?v=abc123')
        ->and($donnees['date'])->not->toBeNull()
        ->and($donnees['segments'])->toHaveCount(2)
        ->and($donnees['segments'][0])->toMatchArray(['debut_ms' => 0, 'fin_ms' => 13400, 'texte' => 'Bienvenue à l’atelier, 你好.'])
        ->and($donnees['segments'][1])->toMatchArray(['debut_ms' => 65000, 'fin_ms' => 70000, 'texte' => 'Ça marche — très bien.'])
        ->and($reponse->getContent())->toContain('日本語');
});

test('Pro, Pack et Gratuit : DOCX et JSON refusés (403), même pour une transcription terminée', function (Plan $plan, string $format) {
    $utilisateur = exportBusinessCompte($plan);
    $transcription = exportBusinessTranscription($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(URL::temporarySignedRoute('transcriptions.export', now()->addMinutes(5), [$transcription, $format]))
        ->assertForbidden();
})->with([
    'Pro DOCX' => [Plan::Pro, 'docx'],
    'Pro JSON' => [Plan::Pro, 'json'],
    'Pack DOCX' => [Plan::Pack, 'docx'],
    'Pack JSON' => [Plan::Pack, 'json'],
    'Gratuit DOCX' => [Plan::Free, 'docx'],
    'Gratuit JSON' => [Plan::Free, 'json'],
]);

test('l\'analyse de script n\'existe qu\'en TXT et PDF : DOCX et JSON répondent 404, même au Business', function () {
    $utilisateur = exportBusinessCompte(Plan::Business);
    $transcription = exportBusinessTranscription($utilisateur);
    $this->actingAs($utilisateur);

    $this->get(URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$transcription, 'docx']))
        ->assertNotFound();
});

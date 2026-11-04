<?php

use App\Enums\StatutTranscription;
use App\Jobs\ExporterDonneesCompte;
use App\Mail\ExportDonneesPret;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Support\Horodatage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Export de toutes les données du compte (réglages, « Vos données »). Un test par critère de la
// section « Export des données » de docs/avancement.md.

beforeEach(function () {
    Storage::fake((string) config('voxia.stockage.disque'));
    Mail::fake();
});

/** Compte avec une transcription terminée, son paragraphe, son MP3 sur le disque. */
function compteAvecTranscription(): array
{
    $utilisateur = User::factory()->create(['email_verified_at' => now(), 'locale' => 'fr']);

    $transcription = Transcription::factory()->termine()->for($utilisateur)->create([
        'titre' => 'Mon atelier de céramique',
        'mp3_chemin' => 'mp3/atelier.mp3',
    ]);

    TranscriptionParagraph::insert([[
        'transcription_id' => $transcription->getKey(),
        'position' => 1,
        'debut_ms' => 0,
        'fin_ms' => 6000,
        'texte' => 'Bienvenue dans l\'atelier.',
    ]]);

    Storage::put('mp3/atelier.mp3', 'octets-du-mp3');

    return [$utilisateur, $transcription];
}

/** Prépare l'export comme la file le fait, et renvoie le lien signé reçu par email. */
function preparerExport(User $utilisateur): string
{
    (new ExporterDonneesCompte($utilisateur->getKey()))->handle();

    $lien = null;

    Mail::assertQueued(ExportDonneesPret::class, function (ExportDonneesPret $mail) use (&$lien) {
        $lien = $mail->lien;

        return true;
    });

    return $lien;
}

/** Noms des fichiers d'un ZIP stocké sur le disque de l'export. */
function fichiersDuZip(User $utilisateur): array
{
    $archive = new ZipArchive;
    $archive->open(Storage::path(ExporterDonneesCompte::chemin($utilisateur->getKey())));

    $noms = [];

    for ($i = 0; $i < $archive->numFiles; $i++) {
        $noms[] = $archive->getNameIndex($i);
    }

    $archive->close();

    return $noms;
}

it('met la demande d\'export en file, sans la traiter dans la requête', function () {
    Queue::fake();
    [$utilisateur] = compteAvecTranscription();

    test()->actingAs($utilisateur)
        ->post(route('reglages.donnees.demander'))
        ->assertRedirect(route('reglages'))
        ->assertSessionHas('statut', 'export-demande');

    Queue::assertPushed(ExporterDonneesCompte::class, fn (ExporterDonneesCompte $job) => $job->utilisateurId === $utilisateur->getKey());
    Storage::assertMissing(ExporterDonneesCompte::chemin($utilisateur->getKey()));
});

it('contient dans le ZIP le compte, chaque transcription en JSON et en texte horodaté, le MP3, et la consommation', function () {
    [$utilisateur, $transcription] = compteAvecTranscription();

    preparerExport($utilisateur);

    $noms = fichiersDuZip($utilisateur);
    $dossier = 'transcriptions/'.$transcription->getKey().'-mon-atelier-de-ceramique';

    expect($noms)
        ->toContain('compte.json')
        ->toContain('consommation.json')
        ->toContain($dossier.'/transcription.json')
        ->toContain($dossier.'/transcription.txt')
        ->toContain($dossier.'/audio.mp3');
});

it('écrit le texte horodaté de chaque transcription dans le ZIP', function () {
    [$utilisateur, $transcription] = compteAvecTranscription();

    preparerExport($utilisateur);

    $archive = new ZipArchive;
    $archive->open(Storage::path(ExporterDonneesCompte::chemin($utilisateur->getKey())));
    $texte = $archive->getFromName('transcriptions/'.$transcription->getKey().'-mon-atelier-de-ceramique/transcription.txt');
    $archive->close();

    expect($texte)->toContain('['.Horodatage::format(0).'] Bienvenue dans l\'atelier.');
});

it('envoie par email un lien signé qui télécharge le ZIP du propriétaire', function () {
    [$utilisateur] = compteAvecTranscription();

    $lien = preparerExport($utilisateur);

    expect($lien)->toContain('signature=');

    test()->actingAs($utilisateur)
        ->get($lien)
        ->assertOk()
        ->assertDownload('voxia-donnees.zip');
});

it('refuse le téléchargement à un autre compte que le propriétaire', function () {
    [$utilisateur] = compteAvecTranscription();
    $autre = User::factory()->create(['email_verified_at' => now(), 'locale' => 'fr']);

    $lien = preparerExport($utilisateur);

    test()->actingAs($autre)->get($lien)->assertNotFound();
});

it('refuse un téléchargement sans lien signé', function () {
    [$utilisateur] = compteAvecTranscription();
    preparerExport($utilisateur);

    test()->actingAs($utilisateur)
        ->get(route('reglages.donnees.telecharger', ['utilisateur' => $utilisateur->getKey()]))
        ->assertForbidden();
});

it('affiche sur les réglages le téléchargement une fois l\'export prêt', function () {
    [$utilisateur] = compteAvecTranscription();

    test()->actingAs($utilisateur)->get('/reglages')
        ->assertOk()
        ->assertDontSee('Télécharger le ZIP');

    preparerExport($utilisateur);

    test()->actingAs($utilisateur)->get('/reglages')
        ->assertOk()
        ->assertSee('Télécharger le ZIP');
});

it('supprime l\'export avec le compte', function () {
    [$utilisateur] = compteAvecTranscription();
    preparerExport($utilisateur);

    Storage::assertExists(ExporterDonneesCompte::chemin($utilisateur->getKey()));

    test()->actingAs($utilisateur)
        ->delete(route('reglages.supprimer'), ['confirmation_email' => $utilisateur->email])
        ->assertRedirect();

    Storage::assertMissing(ExporterDonneesCompte::dossier($utilisateur->getKey()));
});

it('retire les exports plus anciens que la durée de conservation, et garde les récents', function () {
    config(['voxia.donnees.conservation_heures' => 24]);

    [$ancien] = compteAvecTranscription();
    $recent = User::factory()->create();

    preparerExport($ancien);
    Storage::put(ExporterDonneesCompte::chemin($recent->getKey()), 'zip');

    touch(Storage::path(ExporterDonneesCompte::chemin($ancien->getKey())), now()->subHours(48)->getTimestamp());

    test()->artisan('voxia:purger-exports')->assertSuccessful();

    Storage::assertMissing(ExporterDonneesCompte::chemin($ancien->getKey()));
    Storage::assertExists(ExporterDonneesCompte::chemin($recent->getKey()));
});

it('ne met dans le ZIP que les transcriptions du compte, jamais celles d\'un autre compte', function () {
    [$utilisateur] = compteAvecTranscription();
    [$autre, $transcriptionAutre] = compteAvecTranscription();

    preparerExport($utilisateur);

    foreach (fichiersDuZip($utilisateur) as $nom) {
        expect($nom)->not->toContain('transcriptions/'.$transcriptionAutre->getKey().'-');
    }
});

it('décrit la transcription dans le ZIP sans identifiants internes du compte', function () {
    [$utilisateur, $transcription] = compteAvecTranscription();

    preparerExport($utilisateur);

    $archive = new ZipArchive;
    $archive->open(Storage::path(ExporterDonneesCompte::chemin($utilisateur->getKey())));
    $json = json_decode($archive->getFromName('transcriptions/'.$transcription->getKey().'-mon-atelier-de-ceramique/transcription.json'), true);
    $archive->close();

    expect($json['statut'])->toBe(StatutTranscription::Termine->value)
        ->and($json)->not->toHaveKey('user_id')
        ->and($json['paragraphes'][0])->toHaveKeys(['debut', 'fin', 'texte']);
});

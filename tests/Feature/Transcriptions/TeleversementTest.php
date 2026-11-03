<?php

use App\Jobs\ProcessTranscription;
use App\Livewire\NouvelleTranscription;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Televersement\Televersement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

uses(RefreshDatabase::class);

const ENVOI_TEST = '4f1c2a9e-1b2c-4d3e-8f40-5a6b7c8d9e0f';

beforeEach(function () {
    config(['voxia.uploads.dossier' => storage_path('framework/testing/televersements-'.uniqid())]);
    config(['voxia.uploads.morceau_octets' => 10]);
});

afterEach(function () {
    File::deleteDirectory(config('voxia.uploads.dossier'));
});

/**
 * Envoie un morceau comme le fait le navigateur : formulaire multipart, donc tous les champs
 * arrivent en chaînes (c'est ce qui a révélé la comparaison de types du contrôleur).
 */
function envoyerMorceau(User $utilisateur, array $surcharge = [], string $contenu = 'abcdefghij', ?string $envoi = null): TestResponse
{
    $donnees = array_merge([
        'envoi' => $envoi ?? ENVOI_TEST,
        'index' => 0,
        'total' => 1,
        'taille' => strlen($contenu),
        'nom' => 'cours.mp3',
    ], $surcharge);

    $champs = array_map(fn ($valeur) => is_int($valeur) ? (string) $valeur : $valeur, $donnees);
    $champs['morceau'] = UploadedFile::fake()->createWithContent('morceau.part', $contenu);

    return test()->actingAs($utilisateur)->post(route('televersements.morceau'), $champs, ['Accept' => 'application/json']);
}

// Critère F1 : fichier de 2 Go, envoi découpé. La limite est lue dans la configuration.
it('accepte jusqu\'à 2 Go par défaut, en morceaux de la taille configurée', function () {
    config(['voxia.uploads.max_size_mb' => 2048]);

    expect(Televersement::tailleMaxOctets())->toBe(2 * 1024 * 1024 * 1024)
        ->and(Televersement::nombreMorceaux(25))->toBe(3)
        ->and(Televersement::nombreMorceaux(30))->toBe(3);
});

it('réassemble un fichier envoyé en morceaux, à l\'identique, et supprime les morceaux', function () {
    $utilisateur = User::factory()->create();
    $contenu = 'abcdefghijklmnopqrstuvwxy';
    $morceaux = str_split($contenu, 10);

    foreach ($morceaux as $index => $morceau) {
        $reponse = envoyerMorceau($utilisateur, [
            'index' => $index,
            'total' => 3,
            'taille' => strlen($contenu),
        ], $morceau);

        $reponse->assertOk();
        expect($reponse->json('termine'))->toBe($index === 2);
    }

    $dossier = config('voxia.uploads.dossier').DIRECTORY_SEPARATOR.$utilisateur->id.DIRECTORY_SEPARATOR.ENVOI_TEST;
    expect(file_get_contents($dossier.DIRECTORY_SEPARATOR.'cours.mp3'))->toBe($contenu)
        ->and(glob($dossier.DIRECTORY_SEPARATOR.'*.part'))->toBe([]);
});

it('refuse un type de fichier non accepté, sans rien écrire', function () {
    $utilisateur = User::factory()->create();

    envoyerMorceau($utilisateur, ['nom' => 'virus.exe'])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('transcriptions.televersement.format'));

    expect(is_dir(config('voxia.uploads.dossier')))->toBeFalse();
});

it('refuse un fichier au-delà de la taille maximale, avant réception', function () {
    config(['voxia.uploads.max_size_mb' => 1]);
    $utilisateur = User::factory()->create();

    envoyerMorceau($utilisateur, ['taille' => 2 * 1024 * 1024, 'total' => 2])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['taille']);

    expect(is_dir(config('voxia.uploads.dossier')))->toBeFalse();
});

it('refuse un découpage qui ne correspond pas à la taille annoncée', function () {
    $utilisateur = User::factory()->create();

    envoyerMorceau($utilisateur, ['taille' => 25, 'total' => 2, 'index' => 0])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('transcriptions.televersement.decoupage'));
});

it('refuse un identifiant d\'envoi qui n\'est pas un UUID', function () {
    $utilisateur = User::factory()->create();

    envoyerMorceau($utilisateur, [], 'abcdefghij', '../../../../etc/passwd-xxxxxxxxxxxxxxxxxxxx')
        ->assertUnprocessable();

    expect(is_dir(config('voxia.uploads.dossier')))->toBeFalse();
});

it('exige un email vérifié pour envoyer un fichier', function () {
    $utilisateur = User::factory()->unverified()->create();

    envoyerMorceau($utilisateur)
        ->assertForbidden();

    expect(is_dir(config('voxia.uploads.dossier')))->toBeFalse();
});

it('lance la transcription d\'un fichier reçu, avec la langue choisie', function () {
    Queue::fake([ProcessTranscription::class]);
    $utilisateur = User::factory()->create();

    // Le serveur renvoie le nom final (nettoyé) : c'est lui que le navigateur transmet à Livewire.
    $nom = envoyerMorceau($utilisateur, ['total' => 1, 'taille' => 10, 'nom' => 'Cours de maths.mp3'])
        ->assertOk()
        ->json('nom');

    $this->actingAs($utilisateur);

    Livewire::test(NouvelleTranscription::class)
        ->call('fichierTermine', ENVOI_TEST, $nom, 'Cours de maths.mp3')
        ->set('langue', 'fr')
        ->call('lancerFichier');

    $transcription = Transcription::sole();

    expect($transcription->source->value)->toBe('fichier')
        ->and($transcription->langue_forcee)->toBe('fr')
        ->and($transcription->user_id)->toBe($utilisateur->id);

    Queue::assertPushed(ProcessTranscription::class);
});

it('refuse un fichier reçu qui n\'existe pas pour ce compte', function () {
    Queue::fake([ProcessTranscription::class]);
    $this->actingAs(User::factory()->create());

    Livewire::test(NouvelleTranscription::class)
        ->call('fichierTermine', ENVOI_TEST, 'absent.mp3', 'absent.mp3')
        ->assertSet('erreurFichier', __('transcriptions.televersement.echec'))
        ->assertSet('envoi', null);

    expect(Transcription::count())->toBe(0);
    Queue::assertNothingPushed();
});

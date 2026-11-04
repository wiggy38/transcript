<?php

use App\Data\ContenuTranscription;
use App\Data\MotTranscrit;
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\TranscriptionWord;
use App\Models\User;
use App\Services\Exports\ExporteurPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ValidateurSousTitres;

uses(RefreshDatabase::class);

/** Transcription terminée : deux paragraphes, des mots horodatés, un titre et un lien source. */
function exportsTranscription(User $utilisateur, array $attributs = []): Transcription
{
    $transcription = Transcription::factory()->termine()->for($utilisateur)->create([
        'titre' => 'Atelier du jour',
        'url_origine' => 'https://www.youtube.com/watch?v=abc123',
        'date_transcription' => '2026-11-03 10:00:00',
        ...$attributs,
    ]);

    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 1, 'debut_ms' => 0, 'fin_ms' => 2400,
        'texte' => 'Bienvenue dans l’atelier, où l’on écrit « Привет » !',
    ]);
    TranscriptionParagraph::factory()->for($transcription)->create([
        'position' => 2, 'debut_ms' => 3000, 'fin_ms' => 6000,
        'texte' => 'Ça marche, même pour les langues non latines.',
    ]);

    $mots = [
        ['Bienvenue', 0, 700], ['dans', 760, 1000], ['l’atelier', 1050, 1900], [',', 1900, 1905],
        ['où', 2000, 2200], ['l’on', 2250, 2500], ['écrit', 2550, 3000], ['Привет', 3050, 3600],
        ['Ça', 3700, 3900], ['marche', 3950, 4400], [',', 4400, 4405], ['même', 4500, 4900],
        ['pour', 4950, 5200], ['les', 5250, 5400], ['langues', 5450, 5900], ['non', 5950, 6100],
        ['latines', 6150, 6600], ['.', 6600, 6605],
    ];
    TranscriptionWord::insert(array_map(
        fn (array $mot, int $i) => [
            'transcription_id' => $transcription->id,
            'position' => $i + 1,
            'texte' => $mot[0],
            'debut_ms' => $mot[1],
            'fin_ms' => $mot[2],
        ],
        $mots,
        array_keys($mots),
    ));

    return $transcription;
}

/** Un compte passé au plan Pro (le drapeau voxia.droits.plan_reel rend le plan enregistré). */
function exportsComptePro(): User
{
    config(['voxia.droits.plan_reel' => true]);

    return User::factory()->create(['plan' => Plan::Pro]);
}

// Critère F4 : les formats du plan sont téléchargeables, les autres répondent 403.
it('télécharge TXT et SRT au plan Gratuit', function () {
    $utilisateur = User::factory()->create();
    $transcription = exportsTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.export', [$transcription, 'txt']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="Atelier du jour.txt"');

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.export', [$transcription, 'srt']))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="Atelier du jour.srt"');
});

it('refuse PDF et VTT au plan Gratuit', function () {
    $utilisateur = User::factory()->create();
    $transcription = exportsTranscription($utilisateur);

    $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'pdf']))->assertForbidden();
    $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'vtt']))->assertForbidden();
});

it('donne PDF et VTT au plan Pro', function () {
    $utilisateur = exportsComptePro();
    $transcription = exportsTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.export', [$transcription, 'vtt']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/vtt; charset=UTF-8');

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.export', [$transcription, 'pdf']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

// Critère F4 : tout en UTF-8, accents et caractères non latins compris.
it('écrit les accents et le cyrillique en UTF-8 dans TXT, SRT et VTT', function () {
    $utilisateur = exportsComptePro();
    $transcription = exportsTranscription($utilisateur);

    foreach (['txt', 'srt', 'vtt'] as $format) {
        $corps = $this->actingAs($utilisateur)
            ->get(route('transcriptions.export', [$transcription, $format]))
            ->assertOk()
            ->getContent();

        expect(mb_check_encoding($corps, 'UTF-8'))->toBeTrue("UTF-8 invalide en {$format}")
            ->and($corps)->toContain('où')
            ->and($corps)->toContain('Привет')
            ->and($corps)->toContain('l’atelier');
    }
});

it('écrit SRT et VTT valides, au même contenu', function () {
    $utilisateur = exportsComptePro();
    $transcription = exportsTranscription($utilisateur);
    $mots = $transcription->words()->get()->map(fn ($m) => new MotTranscrit($m->texte, $m->debut_ms, $m->fin_ms))->all();

    $srt = $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'srt']))->getContent();
    $vtt = $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'vtt']))->getContent();

    expect(ValidateurSousTitres::srt($srt, $mots))->toBe([])
        ->and(ValidateurSousTitres::vtt($vtt, $mots))->toBe([]);
});

it('écrit les horodatages dans le TXT, et les retire quand on le demande', function () {
    $utilisateur = User::factory()->create();
    $transcription = exportsTranscription($utilisateur);

    $avec = $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'txt']))->getContent();
    $sans = $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'txt']).'?horodatages=0')->getContent();

    expect($avec)->toStartWith('[00:00] Bienvenue dans l’atelier')
        ->and($avec)->toContain("\n\n[00:03] Ça marche")
        ->and($sans)->toStartWith('Bienvenue dans l’atelier')
        ->and($sans)->not->toContain('[00:');
});

it('produit un PDF qui commence par son en-tête PDF', function () {
    $utilisateur = exportsComptePro();
    $transcription = exportsTranscription($utilisateur);

    $pdf = $this->actingAs($utilisateur)->get(route('transcriptions.export', [$transcription, 'pdf']))->getContent();

    expect(str_starts_with($pdf, '%PDF-'))->toBeTrue()
        ->and($pdf)->toContain('/BaseFont /MPDFAA+Geist-Regular');
});

it('ne sert pas la transcription d’un autre compte', function () {
    $proprietaire = User::factory()->create();
    $autre = User::factory()->create();
    $transcription = exportsTranscription($proprietaire);

    $this->actingAs($autre)
        ->get(route('transcriptions.export', [$transcription, 'txt']))
        ->assertNotFound();
});

it('ne sert pas une transcription qui n’est pas encore terminée', function () {
    $utilisateur = User::factory()->create();
    $transcription = exportsTranscription($utilisateur, ['statut' => StatutTranscription::Transcription]);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.export', [$transcription, 'txt']))
        ->assertNotFound();
});

it('refuse un format inconnu', function () {
    $utilisateur = User::factory()->create();
    $transcription = exportsTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->get('/transcriptions/'.$transcription->id.'/exports/mp3')
        ->assertNotFound();
});

it('demande la connexion pour un export', function () {
    $transcription = exportsTranscription(User::factory()->create());

    $this->get(route('transcriptions.export', [$transcription, 'txt']))
        ->assertRedirect(route('login'));
});

// Critère F4 : le PDF reprend le titre, le lien source et la date de transcription. Le texte du PDF
// est compressé et codé par glyphe : on contrôle la page HTML qu'il met en forme.
it('reprend le titre, le lien source et la date dans le PDF', function () {
    app()->setLocale('fr');
    $utilisateur = exportsComptePro();
    $transcription = exportsTranscription($utilisateur);

    $html = app(ExporteurPdf::class)->html(ContenuTranscription::depuis($transcription), true);

    expect($html)->toContain('Atelier du jour')
        ->toContain('https://www.youtube.com/watch?v=abc123')
        ->toContain('3 novembre 2026')
        ->toContain('Привет')
        ->toContain('class="heure"');
});

it('retire les horodatages du PDF quand on le demande', function () {
    $transcription = exportsTranscription(exportsComptePro());

    $html = app(ExporteurPdf::class)->html(ContenuTranscription::depuis($transcription), false);

    expect($html)->not->toContain('class="heure"')
        ->toContain('Bienvenue dans l’atelier');
});

it('échappe le titre dans le PDF', function () {
    $transcription = exportsTranscription(exportsComptePro(), ['titre' => 'Tom & Jerry <b>']);

    $html = app(ExporteurPdf::class)->html(ContenuTranscription::depuis($transcription), true);

    expect($html)->toContain('Tom &amp; Jerry &lt;b&gt;')->not->toContain('<b>');
});

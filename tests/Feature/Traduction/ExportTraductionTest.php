<?php

require_once __DIR__.'/outils.php';

use App\Enums\FormatExport;
use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Services\Traduction\ExporteurTraduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

/**
 * Traduction terminée avec des textes donnés, un par segment (les segments viennent du lancement).
 *
 * @param  list<string>  $textes
 */
function traductionTermineeAvec(array $textes, string $langue = 'es', Plan $plan = Plan::Pro): array
{
    $utilisateur = resumeUtilisateur($plan);
    $transcription = traductionTranscription($utilisateur, 500, ['titre' => 'Mon balcon']);
    $sortie = traductionLancer($utilisateur, $transcription, [$langue])[$langue];
    $segments = traductionSegments($sortie);

    $traduits = [];

    foreach ($segments as $index => $segment) {
        $traduits[] = $segment + ['texte' => $textes[$index % count($textes)], 'trop_long' => false];
    }

    $sortie->update(['statut' => StatutSortieIa::Termine, 'contenu' => ['langue' => $langue, 'segments' => $traduits, 'avertissements' => 0]]);

    return [$utilisateur, $transcription, $sortie->refresh()];
}

/** Textes avec les caractères propres aux langues cibles : accents, tilde, ß, cédille. */
function traductionTextesAccentues(): array
{
    return ['Jardín con más sol, señora.', 'Der Schöne Straßenbalkon ist groß.', 'Ação, coração e ç.', 'Été à Montréal — très bien.'];
}

it('exporte les segments traduits en TXT, avec ou sans horodatages', function () {
    [, , $sortie] = traductionTermineeAvec(['Hola a todos.']);
    $exporteur = app(ExporteurTraduction::class);

    $avec = $exporteur->exporter($sortie, FormatExport::Txt, true);
    $sans = $exporteur->exporter($sortie, FormatExport::Txt, false);

    expect($avec)->toContain('[00:00] Hola a todos.')
        ->and($sans)->toContain('Hola a todos.')
        ->and($sans)->not->toContain('[00:');
});

it('garde les caractères de chaque langue cible dans chaque format', function (FormatExport $format) {
    [, , $sortie] = traductionTermineeAvec(traductionTextesAccentues());
    $contenu = app(ExporteurTraduction::class)->exporter($sortie, $format);

    expect($contenu)->not->toBeEmpty();

    if (in_array($format, [FormatExport::Txt, FormatExport::Srt, FormatExport::Vtt, FormatExport::Json], true)) {
        expect($contenu)->toContain('Jardín')->toContain('Straßenbalkon')->toContain('coração')->toContain('Montréal');
    }

    if ($format === FormatExport::Json) {
        expect(json_decode($contenu, true))->toBeArray();
    }

    if ($format === FormatExport::Docx) {
        expect(substr($contenu, 0, 2))->toBe('PK'); // archive Office
    }

    if ($format === FormatExport::Pdf) {
        expect(substr($contenu, 0, 4))->toBe('%PDF');
    }
})->with([
    'TXT' => [FormatExport::Txt],
    'PDF' => [FormatExport::Pdf],
    'SRT' => [FormatExport::Srt],
    'VTT' => [FormatExport::Vtt],
    'DOCX' => [FormatExport::Docx],
    'JSON' => [FormatExport::Json],
]);

it('redécoupe une traduction trop longue en SRT, sans dépasser les bornes du segment d’origine', function () {
    $long = str_repeat('Der Balkon ist sonnig und groß. ', 5);
    [, , $sortie] = traductionTermineeAvec([$long]);
    $segments = traductionSegments($sortie);

    $srt = app(ExporteurTraduction::class)->exporter($sortie, FormatExport::Srt);
    $blocs = array_values(array_filter(explode("\n\n", trim($srt))));

    expect(count($blocs))->toBeGreaterThan(count($segments));

    foreach ($blocs as $bloc) {
        preg_match('/^\d+\n(\d\d):(\d\d):(\d\d),(\d{3}) --> (\d\d):(\d\d):(\d\d),(\d{3})\n(.*)$/s', $bloc, $m);
        $debut = ((int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3]) * 1000 + (int) $m[4];
        $fin = ((int) $m[5] * 3600 + (int) $m[6] * 60 + (int) $m[7]) * 1000 + (int) $m[8];

        // Chaque sous-titre tient dans le segment d'origine dont il provient.
        $dansUnSegment = collect($segments)->contains(
            fn (array $s) => $debut >= $s['debut_ms'] && $fin <= $s['fin_ms'],
        );
        expect($dansUnSegment)->toBeTrue();

        foreach (explode("\n", trim($m[9])) as $ligne) {
            expect(mb_strlen($ligne))->toBeLessThanOrEqual(42);
        }
    }
});

it('nomme le fichier avec le code de langue, par exemple titre.es.srt', function () {
    [$utilisateur, $transcription] = traductionTermineeAvec(['Hola a todos.']);

    $url = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'es', 'srt']);
    $reponse = $this->actingAs($utilisateur)->get($url);

    $reponse->assertOk();
    expect($reponse->headers->get('Content-Disposition'))->toContain('Mon balcon.es.srt');
});

it('refuse le DOCX en Pro (403), et répond 404 pour un autre compte ou une langue non traduite', function () {
    [$utilisateur, $transcription] = traductionTermineeAvec(['Hola a todos.']);
    $autre = resumeUtilisateur(Plan::Pro);

    $docx = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'es', 'docx']);
    $this->actingAs($utilisateur)->get($docx)->assertForbidden();

    $srt = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'es', 'srt']);
    $this->actingAs($autre)->get($srt)->assertNotFound();

    $absente = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'de', 'srt']);
    $this->actingAs($utilisateur)->get($absente)->assertNotFound();
});

it('accepte le DOCX et le JSON en Business', function () {
    [$utilisateur, $transcription] = traductionTermineeAvec(['Hola a todos.'], 'es', Plan::Business);

    $json = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'es', 'json']);
    $this->actingAs($utilisateur)->get($json)->assertOk();

    $docx = URL::temporarySignedRoute('transcriptions.traduction.export', now()->addMinutes(5), [$transcription, 'es', 'docx']);
    $this->actingAs($utilisateur)->get($docx)->assertOk();
});

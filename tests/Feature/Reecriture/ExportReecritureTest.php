<?php

require_once __DIR__.'/outils.php';

use App\Enums\FormatExportReecriture;
use App\Enums\FormatReecriture;
use App\Enums\Plan;
use App\Exceptions\ReecritureRefusee;
use App\Models\AiOutput;
use App\Models\User;
use App\Services\Exports\ExporteurReecriture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

/** Réécriture terminée dans un format, pour un compte donné. */
function reecritureTerminee(User $utilisateur, FormatReecriture $format, array $structure): AiOutput
{
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse($structure));
    $sortie = reecritureLancer($utilisateur, $transcription, [$format])[$format->value];
    reecritureGenerer($sortie);

    return $sortie->refresh();
}

function reecritureUrlExport(AiOutput $sortie, string $export): string
{
    return URL::temporarySignedRoute(
        'transcriptions.reecriture.export',
        now()->addMinutes(5),
        [$sortie->transcription, FormatReecriture::from($sortie->format), $export],
    );
}

it('le plan Pro exporte en TXT ; le Markdown et le DOCX sont réservés au Business', function () {
    $pro = resumeUtilisateur(Plan::Pro);
    $sortie = reecritureTerminee($pro, FormatReecriture::LinkedIn, reecritureLinkedinValide());

    $this->actingAs($pro)->get(reecritureUrlExport($sortie, 'txt'))->assertOk();
    $this->actingAs($pro)->get(reecritureUrlExport($sortie, 'md'))->assertForbidden();
    $this->actingAs($pro)->get(reecritureUrlExport($sortie, 'docx'))->assertForbidden();

    $business = resumeUtilisateur(Plan::Business);
    $sortieBusiness = reecritureTerminee($business, FormatReecriture::LinkedIn, reecritureLinkedinValide());

    $this->actingAs($business)->get(reecritureUrlExport($sortieBusiness, 'md'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $this->actingAs($business)->get(reecritureUrlExport($sortieBusiness, 'docx'))->assertOk();
});

it('l’export Markdown d’un thread numérote chaque message k/N', function () {
    $business = resumeUtilisateur(Plan::Business);
    $sortie = reecritureTerminee($business, FormatReecriture::Thread, reecritureThreadValide());

    $markdown = app(ExporteurReecriture::class)->exporter($sortie, FormatExportReecriture::Markdown);

    expect($markdown)->toContain('- 1/6 Mon balcon sud en trois leçons.')
        ->and($markdown)->toContain('- 6/6 ');
});

it('l’export TXT d’un article garde le titre, les intertitres et la conclusion', function () {
    $pro = resumeUtilisateur(Plan::Pro);
    $sortie = reecritureTerminee($pro, FormatReecriture::Article, reecritureArticleValide());

    $txt = app(ExporteurReecriture::class)->exporter($sortie, FormatExportReecriture::Txt);

    expect($txt)->toStartWith('Un balcon qui produit')
        ->and($txt)->toContain('Choisir le bon emplacement')
        ->and($txt)->toContain('Conclusion');
});

it('un compte sans accès à la réécriture ne peut pas lancer : aucune sortie, aucune tâche', function () {
    $gratuit = resumeUtilisateur(Plan::Free);
    $transcription = resumeTranscriptionCourte($gratuit);

    expect(fn () => reecritureLancer($gratuit, $transcription, [FormatReecriture::Article]))
        ->toThrow(ReecritureRefusee::class);
    Queue::assertNothingPushed();
});

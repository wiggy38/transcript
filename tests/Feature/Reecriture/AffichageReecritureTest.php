<?php

require_once __DIR__.'/outils.php';

use App\Enums\FormatReecriture;
use App\Enums\Plan;
use App\Livewire\ReecrireTranscription;
use App\Services\Quota\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

it('affiche un onglet par format généré, avec le contenu et un bouton Copier par bloc', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(reecritureThreadValide()));
    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Thread])['thread'];
    reecritureGenerer($sortie);

    $this->actingAs($utilisateur);

    Livewire::test(ReecrireTranscription::class, ['transcription' => $transcription])
        ->assertSee('role="tab"', false)
        ->assertSee('X thread')
        ->assertSee('1/6 Mon balcon sud en trois leçons.')
        ->assertSee('Copy');
});

it('un solde insuffisant pour tous les formats affiche le motif sans rien lancer', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    app(QuotaService::class)->consommerCredits($utilisateur, 29, 'resume', $transcription);

    $this->actingAs($utilisateur);

    Livewire::test(ReecrireTranscription::class, ['transcription' => $transcription])
        ->set('formats', ['article', 'newsletter'])
        ->call('lancer')
        ->assertSee('These formats need 2 AI credit(s)');

    Queue::assertNothingPushed();
});

<?php

require_once __DIR__.'/outils.php';

use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Jobs\GenererResume;
use App\Livewire\ResumeTranscription;
use App\Services\Resume\LancerResume;
use App\Services\Resume\ServiceResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

// Critère affichage : le coût est affiché avant le lancement, et le lancement passe par la file.
it('affiche le coût avant le lancement et lance la tâche en file', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);

    Livewire::actingAs($utilisateur)
        ->test(ResumeTranscription::class, ['transcription' => $transcription])
        ->assertSee(__('resume.cout', ['credits' => 1]))
        ->call('lancer')
        ->assertSet('refus', null);

    Queue::assertPushed(GenererResume::class);
});

// Critère affichage : un plan sans crédits IA ne voit pas le bouton, seulement la mention du plan.
it('n’affiche pas le lancement aux plans sans crédits IA', function () {
    $utilisateur = resumeUtilisateur(Plan::Free);
    $transcription = resumeTranscriptionCourte($utilisateur);

    Livewire::actingAs($utilisateur)
        ->test(ResumeTranscription::class, ['transcription' => $transcription])
        ->assertSee(__('resume.plan_sans_acces'))
        ->assertDontSee(__('resume.bouton'));
});

// Critère affichage : un clic sur un point envoie « voxia-aller » avec le début du paragraphe source.
it('affiche les points cliquables avec le début de leur passage', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));
    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription);
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));

    Livewire::actingAs($utilisateur)
        ->test(ResumeTranscription::class, ['transcription' => $transcription])
        ->assertSee("voxia-aller', { ms: 20000 }", escape: false)
        ->assertSee(__('resume.points'))
        ->assertSee('Choisir un balcon bien orienté.');
});

// Critère affichage : régénérer ouvre une confirmation, sans lancer de tâche.
it('demande confirmation avant de régénérer', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));
    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription);
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));
    Queue::fake();

    Livewire::actingAs($utilisateur)
        ->test(ResumeTranscription::class, ['transcription' => $transcription])
        ->call('demanderRegeneration')
        ->assertSet('confirmerRegeneration', true)
        ->assertSee(__('resume.confirmer'));

    Queue::assertNothingPushed();
});

// Critère affichage : un échec affiche le message, sans crédit consommé.
it('affiche l’échec sans crédit consommé', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription);
    $sortie->update(['statut' => StatutSortieIa::Echec, 'date_echec' => now()]);

    Livewire::actingAs($utilisateur)
        ->test(ResumeTranscription::class, ['transcription' => $transcription])
        ->assertSee(__('resume.statut.echec'));
});

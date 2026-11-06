<?php

require_once __DIR__.'/outils.php';

use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Jobs\GenererTraduction;
use App\Livewire\TraduireTranscription;
use App\Models\AiOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    app()->setLocale('fr');
});

it('affiche le coût avant lancement et lance les langues cochées', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);

    $this->actingAs($utilisateur);

    Livewire::test(TraduireTranscription::class, ['transcription' => $transcription])
        ->set('langues', ['en', 'de'])
        ->assertSee(__('traduction.cout', ['credits' => 2]))
        ->call('lancer')
        ->assertHasNoErrors()
        ->assertSet('langues', []);

    expect(AiOutput::where('fonctionnalite', 'traduction')->count())->toBe(2);
    Queue::assertPushed(GenererTraduction::class, 2);
});

it('affiche le motif quand le solde ne couvre pas les langues, et ne lance rien', function () {
    config(['voxia.plans.pro.ai_credits_per_month' => 1]);
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);

    $this->actingAs($utilisateur);

    Livewire::test(TraduireTranscription::class, ['transcription' => $transcription])
        ->set('langues', ['en', 'de'])
        ->call('lancer')
        ->assertSee(__('traduction.refus.credit_ia_insuffisant', ['credits' => 2]));

    Queue::assertNothingPushed();
});

it('n’affiche pas le lancement à un plan sans crédits IA', function () {
    $utilisateur = resumeUtilisateur(Plan::Free);
    $transcription = traductionTranscription($utilisateur);

    $this->actingAs($utilisateur);

    Livewire::test(TraduireTranscription::class, ['transcription' => $transcription])
        ->assertSee(__('traduction.plan_sans_acces'))
        ->assertDontSee('wire:click="lancer"', false);
});

it('affiche la vue côte à côte d’une traduction terminée, avec les liens d’export signés', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['es'])['es'];
    $segments = traductionSegments($sortie);

    $traduits = array_map(fn (array $s) => $s + ['texte' => 'Traducción de prueba.', 'trop_long' => false], $segments);
    $sortie->update(['statut' => StatutSortieIa::Termine, 'contenu' => ['langue' => 'es', 'segments' => $traduits, 'avertissements' => 0]]);

    $this->actingAs($utilisateur);

    $composant = Livewire::test(TraduireTranscription::class, ['transcription' => $transcription])
        ->set('onglet', 'es')
        ->assertSee('Traducción de prueba.')
        ->assertSee('x-on:voxia-temps.window', false)
        ->assertSee('signature=', false);

    expect($composant->html())->toContain('lang="es"');
});

it('garde l’original quand la langue demandée n’est pas traduite', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    traductionLancer($utilisateur, $transcription, ['es']);

    $this->actingAs($utilisateur);

    Livewire::test(TraduireTranscription::class, ['transcription' => $transcription])
        ->set('onglet', 'es')
        ->assertSet('onglet', 'es')
        ->assertDontSee('voxia-temps');
});

it('ne montre pas une transcription d’un autre compte', function () {
    $proprietaire = resumeUtilisateur(Plan::Pro);
    $autre = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($proprietaire);

    $this->actingAs($autre);

    // La transcription est relue sur le compte connecté : un autre compte n'obtient rien (404).
    expect(fn () => Livewire::test(TraduireTranscription::class, ['transcription' => $transcription]))
        ->toThrow(ModelNotFoundException::class);
});

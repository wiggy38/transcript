<?php

require_once __DIR__.'/outils.php';

use App\Enums\StatutAnalyseScript;
use App\Livewire\AnalyseScript;
use App\Models\ScriptAnalysis;
use App\Models\User;
use App\Services\Analyse\LancerAnalyseScript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** Analyse terminée de la pub de test pour un compte, avec l'offre absente si $offreAbsente. */
function analyseTerminee(User $utilisateur, bool $offreAbsente = false)
{
    $transcription = transcriptionAnalysable($utilisateur);
    $plages = analysePlagesVraies();

    if ($offreAbsente) {
        $plages['offre'] = null;
    }

    analyseFaux()->repondre(analyseReponse($plages));
    app(LancerAnalyseScript::class)->lancer($utilisateur, $transcription);

    return $transcription->refresh();
}

// Critère F7 : un clic sur une partie lance la lecture à son début. Le bouton envoie le début exact en
// millisecondes ; le lecteur l'écoute (événement voxia-aller).
it('envoie le début de chaque partie au lecteur au clic', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->assertSee("\$dispatch('voxia-aller', { ms: 0 })", false)
        ->assertSee("\$dispatch('voxia-aller', { ms: 4000 })", false)
        ->assertSee("\$dispatch('voxia-aller', { ms: 9000 })", false);
});

// Critère F7 : la barre reprend la maquette ; une partie absente est hachurée et porte son libellé.
it('hachure une partie absente avec la mention « non détectée »', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur, offreAbsente: true);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->assertSee('repeating-linear-gradient', false)
        ->assertSee(__('analyse.types.offre').' · '.__('analyse.non_detectee'))
        ->assertDontSee('ms: 8000 }', false);
});

// Critère F7 : le texte de chaque partie est affiché avec ses horodatages.
it('affiche le texte de chaque partie avec ses horodatages', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->assertSee('« Tu te réveilles? »', false)
        ->assertSee('00:00 → 00:03', false)
        ->assertSee('« Ta peau »', false);
});

// Un script de plus de 3 minutes : bouton désactivé, raison affichée, et le serveur refuse aussi.
it('désactive le bouton au-delà de 3 minutes, avec la raison', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = transcriptionAnalysable($utilisateur, ['duree_secondes' => 240]);

    Livewire::test(AnalyseScript::class, ['transcription' => $transcription])
        ->assertSeeHtml('disabled')
        ->assertSee(__('analyse.refus.duree_trop_longue', ['minutes' => 3]))
        ->call('analyser')
        ->assertSet('refus', 'duree_trop_longue');
});

// Critère F7 : l'analyse est rattachée à sa transcription, visible dans l'historique.
it('signale l’analyse dans l’historique', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    analyseTerminee($utilisateur);

    $this->get(route('historique'))
        ->assertOk()
        ->assertSee(__('analyse.pastille'));
});

// Critère F7 : suppression de la transcription, suppression de son analyse.
it('supprime l’analyse avec sa transcription', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur);

    $transcription->delete();

    expect(ScriptAnalysis::count())->toBe(0);
});

// Critère F7 : export TXT. Une partie absente y figure comme « non détectée ».
it('exporte l’analyse en TXT, parties absentes comprises', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur, offreAbsente: true);

    $url = URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$transcription, 'txt']);

    $reponse = $this->get($url)->assertOk();

    expect((string) $reponse->getContent())
        ->toContain('Tu te réveilles?')
        ->toContain('['.'00:00 → 00:03'.']')
        ->toContain(__('analyse.types.offre').' : '.__('analyse.non_detectee'));
});

// Critère F7 : export PDF réservé aux plans qui le incluent (cahier §5 : Gratuit = TXT et SRT seulement).
it('refuse le PDF au plan Gratuit', function () {
    $utilisateur = User::factory()->create();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur);

    $url = URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$transcription, 'pdf']);

    $this->get($url)->assertForbidden();
});

it('exporte l’analyse en PDF au plan Pro', function () {
    config(['voxia.droits.plan_reel' => true]);
    $utilisateur = analyseUtilisateurPro();
    $this->actingAs($utilisateur);
    $transcription = analyseTerminee($utilisateur);

    $url = URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$transcription, 'pdf']);

    $reponse = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect(substr((string) $reponse->getContent(), 0, 4))->toBe('%PDF');
});

// Un export ne se donne qu'au propriétaire, et seulement pour une analyse terminée.
it('répond 404 à un autre compte ou à une analyse non terminée', function () {
    $proprietaire = User::factory()->create();
    $autre = User::factory()->create();
    $transcription = analyseTerminee($proprietaire);

    $this->actingAs($autre);
    $url = URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$transcription, 'txt']);

    $this->get($url)->assertNotFound();

    $this->actingAs($proprietaire);
    $encours = transcriptionAnalysable($proprietaire);
    ScriptAnalysis::factory()->for($encours)->create(['statut' => StatutAnalyseScript::EnAttente]);

    $urlEncours = URL::temporarySignedRoute('transcriptions.analyse.export', now()->addMinutes(5), [$encours, 'txt']);

    $this->get($urlEncours)->assertNotFound();
});

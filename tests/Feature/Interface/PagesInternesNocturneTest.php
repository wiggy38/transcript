<?php

use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Compte vérifié, au plan demandé (maquettes « Nocturne » des pages internes). */
function compteNocturne(Plan $plan = Plan::Free): User
{
    return User::factory()->create(['plan' => $plan, 'email_verified_at' => now()]);
}

it('affiche la navigation de l\'application à trois entrées, avec l\'écran courant marqué et la jauge de quota', function () {
    $html = $this->actingAs(compteNocturne())->get(route('tableau-de-bord'))->assertOk()->getContent();

    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toContain(route('historique'))
        ->toContain(route('reglages'))
        ->toContain(route('abonnement'));
});

it('affiche au plus cinq transcriptions récentes sur le tableau de bord, avec un lien vers l\'historique complet', function () {
    $utilisateur = compteNocturne();
    foreach (range(1, 7) as $indice) {
        Transcription::factory()->for($utilisateur)->create([
            'titre' => 'Titre-'.$indice,
            'created_at' => now()->subDays(8 - $indice),
        ]);
    }

    $this->actingAs($utilisateur)->get(route('tableau-de-bord'))
        ->assertOk()
        ->assertSee('Titre-7')
        ->assertSee('Titre-3')
        ->assertDontSee('Titre-2')
        ->assertDontSee('Titre-1')
        ->assertSee(__('comptes.tableau.tout'));
});

it('affiche « Ta formule » dans les réglages : nom du plan, quota du Gratuit et ancres de navigation', function () {
    $html = $this->actingAs(compteNocturne(Plan::Free))->get(route('reglages'))->assertOk()->getContent();

    $quota = '0 / '.config('voxia.plans.free.videos_per_month');

    expect($html)
        ->toContain(app(PlanFeatures::class)->libellePlan(Plan::Free))
        ->toContain($quota)
        ->toContain(__('comptes.reglages.formule.videos'))
        ->toContain('href="#abonnement"')
        ->toContain('href="#profil"')
        ->toContain('href="#mot-de-passe"')
        ->toContain('href="#langue"')
        ->toContain('href="#factures"')
        ->toContain('href="#donnees"')
        ->toContain('href="#suppression"');
});

it('affiche les heures restantes dans « Ta formule » pour une formule Pro, et la ligne des Packs seulement s\'il y en a', function () {
    $this->actingAs(compteNocturne(Plan::Pro))->get(route('reglages'))
        ->assertOk()
        ->assertSee(__('comptes.reglages.formule.heures_mois'))
        ->assertDontSee(__('comptes.reglages.formule.heures_packs'))
        ->assertDontSee(__('comptes.reglages.formule.videos'));
});

it('marque l\'étape en cours du traitement, et aucune en cas d\'échec', function () {
    $utilisateur = compteNocturne();
    $enCours = Transcription::factory()->for($utilisateur)->create(['statut' => StatutTranscription::Transcription]);

    $html = $this->actingAs($utilisateur)->get(route('transcriptions.show', $enCours))->assertOk()->getContent();

    expect($html)
        ->toMatch('/<li\s+aria-current="step"[^>]*>.*?<span>'.preg_quote(__('interface.statuts.transcription'), '/').'<\/span>/s');

    $echec = Transcription::factory()->for($utilisateur)->echec()->create();

    $this->actingAs($utilisateur)->get(route('transcriptions.show', $echec))
        ->assertOk()
        ->assertDontSee('aria-current="step"', false);
});

it('affiche la connexion avec le lien « Mot de passe oublié ? » sur la ligne du libellé, une case « Rester connecté » et une colonne décorative', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    expect($html)
        ->toContain(route('password.request'))
        ->toContain('name="remember"')
        ->toContain('<aside aria-hidden="true"');
});

it('affiche l\'inscription avec les liens vers les CGU et la politique de confidentialité', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(route('conditions'), false)
        ->assertSee(route('confidentialite'), false);
});

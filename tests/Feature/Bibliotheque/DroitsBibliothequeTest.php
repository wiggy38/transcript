<?php

// Droits de la bibliothèque (V2) : Policies, plan Gratuit et Pack, retour au Gratuit en lecture seule.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\Plan;
use App\Enums\TypeEtiquette;
use App\Models\Transcription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('ne donne accès au dossier, à l’étiquette ni au classement d’un autre compte qu’en 404', function () {
    Queue::fake();

    $proprietaire = bibPro();
    $intrus = bibPro();
    $dossier = bibDossier($proprietaire, 'Privé');
    $etiquette = bibEtiquette($proprietaire, TypeEtiquette::Niche, 'Privée');
    $transcription = bibTranscription($proprietaire);

    $this->actingAs($intrus)->patch(route('bibliotheque.dossiers.update', $dossier), ['nom' => 'Volé'])->assertNotFound();
    $this->actingAs($intrus)->delete(route('bibliotheque.dossiers.destroy', $dossier))->assertNotFound();
    $this->actingAs($intrus)->patch(route('bibliotheque.etiquettes.update', $etiquette), ['nom' => 'Volée', 'type' => 'libre'])->assertNotFound();
    $this->actingAs($intrus)->delete(route('bibliotheque.etiquettes.destroy', $etiquette))->assertNotFound();
    $this->actingAs($intrus)->get(route('transcriptions.classement', $transcription))->assertNotFound();
    $this->actingAs($intrus)->put(route('transcriptions.classement.update', $transcription), ['etiquettes' => []])->assertNotFound();

    expect($dossier->fresh()->nom)->toBe('Privé');
    expect($etiquette->fresh()->nom)->toBe('Privée');
});

it('ignore dans une action groupée les transcriptions d’un autre compte', function () {
    Queue::fake();

    $proprietaire = bibPro();
    $intrus = bibPro();
    $transcription = bibTranscription($proprietaire);

    $this->actingAs($intrus)
        ->post(route('bibliotheque.selection'), ['action' => 'supprimer', 'selection' => [$transcription->getKey()]])
        ->assertSessionHas('statut', 'selection-supprimee');

    expect(Transcription::find($transcription->getKey()))->not->toBeNull();
});

it('refuse de ranger une transcription dans le dossier d’un autre compte', function () {
    $proprietaire = bibPro();
    $intrus = bibPro();
    $dossierDuProprietaire = bibDossier($proprietaire, 'Privé');
    $transcription = bibTranscription($intrus);

    $this->actingAs($intrus)
        ->put(route('transcriptions.classement.update', $transcription), ['dossier_id' => $dossierDuProprietaire->getKey()])
        ->assertSessionHasErrors('dossier_id');

    expect($transcription->fresh()->dossier_id)->toBeNull();
});

it('refuse au plan Gratuit la création d’un dossier', function () {
    $gratuit = bibGratuit();

    $this->actingAs($gratuit)
        ->post(route('bibliotheque.dossiers.store'), ['nom' => 'Niche café'])
        ->assertForbidden();

    expect($gratuit->dossiers()->count())->toBe(0);
});

it('refuse au Pack la création d’une étiquette et le classement d’une transcription', function () {
    $pack = bibCompte(Plan::Pack);
    $transcription = bibTranscription($pack);

    $this->actingAs($pack)
        ->post(route('bibliotheque.etiquettes.store'), ['nom' => 'Café', 'type' => 'niche'])
        ->assertForbidden();

    $this->actingAs($pack)
        ->get(route('transcriptions.classement', $transcription))
        ->assertForbidden();
});

it('affiche « Classer » au plan Gratuit, qui ouvre la proposition d’upgrade au lieu de la page de classement', function () {
    $gratuit = bibGratuit();
    $transcription = bibTranscription($gratuit);

    $this->actingAs($gratuit)
        ->get(route('historique'))
        ->assertOk()
        ->assertSee(__('bibliotheque.classer.bouton'))
        ->assertSee('upgrade-classement')
        ->assertDontSee(route('transcriptions.classement', $transcription));
});

it('garde en lecture seule les dossiers et étiquettes d’un compte repassé au Gratuit', function () {
    Queue::fake();

    $gratuit = bibGratuit();
    $dossier = bibDossier($gratuit, 'Ancien dossier');
    $etiquette = bibEtiquette($gratuit, TypeEtiquette::Concurrent, 'Ancien concurrent');
    $transcription = bibTranscription($gratuit, ['titre' => 'Titre rangé', 'dossier' => $dossier]);
    $transcription->etiquettes()->attach($etiquette->getKey());

    // Les données restent visibles et filtrables.
    $this->actingAs($gratuit)
        ->get(route('historique'))
        ->assertOk()
        ->assertSee('id="titre-bibliotheque"', false)
        ->assertSee('Ancien dossier')
        ->assertSee('Ancien concurrent');

    $this->actingAs($gratuit)
        ->get(route('historique', ['dossier' => $dossier->getKey()]))
        ->assertOk()
        ->assertSee('Titre rangé');

    $this->actingAs($gratuit)
        ->get(route('bibliotheque.gerer'))
        ->assertOk()
        ->assertSee(__('bibliotheque.gerer.lecture_seule'))
        ->assertSee('Ancien dossier')
        ->assertDontSee(route('bibliotheque.dossiers.update', $dossier))
        ->assertDontSee(route('bibliotheque.etiquettes.update', $etiquette));

    // Aucune modification ne passe.
    $this->actingAs($gratuit)->post(route('bibliotheque.dossiers.store'), ['nom' => 'Nouveau'])->assertForbidden();
    $this->actingAs($gratuit)->patch(route('bibliotheque.dossiers.update', $dossier), ['nom' => 'Renommé'])->assertForbidden();
    $this->actingAs($gratuit)->delete(route('bibliotheque.dossiers.destroy', $dossier))->assertForbidden();
    $this->actingAs($gratuit)->patch(route('bibliotheque.etiquettes.update', $etiquette), ['nom' => 'Renommée', 'type' => 'niche'])->assertForbidden();
    $this->actingAs($gratuit)->delete(route('bibliotheque.etiquettes.destroy', $etiquette))->assertForbidden();
    $this->actingAs($gratuit)->get(route('transcriptions.classement', $transcription))->assertForbidden();

    expect($dossier->fresh()->nom)->toBe('Ancien dossier');
    expect($transcription->fresh()->dossier_id)->toBe($dossier->getKey());

    // Sortir une transcription de son dossier est un classement : refusé.
    $this->actingAs($gratuit)
        ->post(route('bibliotheque.selection'), ['action' => 'deplacer', 'selection' => [$transcription->getKey()], 'dossier_id' => 'aucun'])
        ->assertForbidden();

    expect($transcription->fresh()->dossier_id)->toBe($dossier->getKey());

    // Supprimer une transcription reste permis.
    $this->actingAs($gratuit)
        ->post(route('bibliotheque.selection'), ['action' => 'supprimer', 'selection' => [$transcription->getKey()]])
        ->assertSessionHas('statut', 'selection-supprimee');

    expect(Transcription::find($transcription->getKey()))->toBeNull();
});

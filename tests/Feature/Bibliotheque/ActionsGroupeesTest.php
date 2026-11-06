<?php

// Actions groupées sur la liste (V2) : déplacer, étiqueter ou supprimer les transcriptions cochées.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\TypeEtiquette;
use App\Models\Transcription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('déplace seulement les transcriptions cochées, sans toucher celles d’un autre compte', function () {
    Queue::fake();

    $utilisateur = bibPro();
    $autre = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');
    $coche = bibTranscription($utilisateur);
    $deuxieme = bibTranscription($utilisateur);
    $nonCoche = bibTranscription($utilisateur);
    $dAutrui = bibTranscription($autre);

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.selection'), [
            'action' => 'deplacer',
            'selection' => [$coche->getKey(), $deuxieme->getKey(), $dAutrui->getKey()],
            'dossier_id' => (string) $dossier->getKey(),
        ])
        ->assertSessionHas('statut', 'selection-deplacee');

    expect($coche->fresh()->dossier_id)->toBe($dossier->getKey());
    expect($deuxieme->fresh()->dossier_id)->toBe($dossier->getKey());
    expect($nonCoche->fresh()->dossier_id)->toBeNull();
    expect($dAutrui->fresh()->dossier_id)->toBeNull();
});

it('remet les transcriptions cochées dans « Non classé » avec la valeur aucun', function () {
    Queue::fake();

    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');
    $transcription = bibTranscription($utilisateur, ['dossier' => $dossier]);

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.selection'), [
            'action' => 'deplacer',
            'selection' => [$transcription->getKey()],
            'dossier_id' => 'aucun',
        ])
        ->assertSessionHas('statut', 'selection-deplacee');

    expect($transcription->fresh()->dossier_id)->toBeNull();
});

it('ajoute une étiquette aux transcriptions cochées sans retirer celles qu’elles ont déjà', function () {
    Queue::fake();

    $utilisateur = bibPro();
    $ancienne = bibEtiquette($utilisateur, TypeEtiquette::Libre, 'À revoir');
    $nouvelle = bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Lampe LED');
    $coche = bibTranscription($utilisateur);
    $coche->etiquettes()->attach($ancienne->getKey());
    $nonCoche = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.selection'), [
            'action' => 'etiqueter',
            'selection' => [$coche->getKey()],
            'etiquettes' => [(string) $nouvelle->getKey()],
        ])
        ->assertSessionHas('statut', 'selection-etiquetee');

    expect($coche->fresh()->etiquettes->pluck('id')->sort()->values()->all())
        ->toBe(collect([$ancienne->getKey(), $nouvelle->getKey()])->sort()->values()->all());
    expect($nonCoche->fresh()->etiquettes)->toHaveCount(0);
});

it('refuse d’étiqueter sans étiquette choisie, sans afficher un succès', function () {
    Queue::fake();

    $utilisateur = bibPro();
    $transcription = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.selection'), [
            'action' => 'etiqueter',
            'selection' => [$transcription->getKey()],
            'etiquettes' => [''],
        ])
        ->assertSessionHasErrors('etiquettes')
        ->assertSessionMissing('statut');
});

it('supprime les transcriptions cochées et laisse les autres', function () {
    Queue::fake();

    $utilisateur = bibPro();
    $supprimee = bibTranscription($utilisateur);
    $conservee = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.selection'), [
            'action' => 'supprimer',
            'selection' => [$supprimee->getKey()],
        ])
        ->assertSessionHas('statut', 'selection-supprimee');

    expect(Transcription::find($supprimee->getKey()))->toBeNull();
    expect(Transcription::find($conservee->getKey()))->not->toBeNull();
});

it('refuse à un plan sans classement de déplacer ou d’étiqueter, mais laisse supprimer', function () {
    Queue::fake();

    $gratuit = bibGratuit();
    $etiquette = bibEtiquette($gratuit, TypeEtiquette::Niche, 'Café');
    $deplacee = bibTranscription($gratuit);
    $etiquetee = bibTranscription($gratuit);
    $supprimee = bibTranscription($gratuit);

    $this->actingAs($gratuit)
        ->post(route('bibliotheque.selection'), [
            'action' => 'deplacer',
            'selection' => [$deplacee->getKey()],
            'dossier_id' => 'aucun',
        ])
        ->assertForbidden();

    $this->actingAs($gratuit)
        ->post(route('bibliotheque.selection'), [
            'action' => 'etiqueter',
            'selection' => [$etiquetee->getKey()],
            'etiquettes' => [(string) $etiquette->getKey()],
        ])
        ->assertForbidden();

    $this->actingAs($gratuit)
        ->post(route('bibliotheque.selection'), [
            'action' => 'supprimer',
            'selection' => [$supprimee->getKey()],
        ])
        ->assertSessionHas('statut', 'selection-supprimee');

    expect($etiquetee->fresh()->etiquettes)->toHaveCount(0);
    expect(Transcription::find($supprimee->getKey()))->toBeNull();
});

<?php

// Classement (V2) : dossiers à un niveau, une transcription par dossier au plus, étiquettes des quatre types,
// autocomplétion, renommage et suppression.

require_once __DIR__.'/AideBibliotheque.php';

use App\Enums\TypeEtiquette;
use App\Models\Dossier;
use App\Models\Etiquette;
use App\Models\Transcription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ne propose que quatre types d’étiquettes : niche, produit, concurrent et libre', function () {
    expect(array_map(fn (TypeEtiquette $type) => $type->value, TypeEtiquette::cases()))
        ->toBe(['niche', 'produit', 'concurrent', 'libre']);
});

it('range une transcription dans un seul dossier : la nouvelle affectation remplace l’ancienne', function () {
    $utilisateur = bibPro();
    $premier = bibDossier($utilisateur, 'Niche café');
    $second = bibDossier($utilisateur, 'Produit lampe');
    $transcription = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->put(route('transcriptions.classement.update', $transcription), ['dossier_id' => $premier->getKey()])
        ->assertSessionHasNoErrors();

    expect($transcription->fresh()->dossier_id)->toBe($premier->getKey());

    $this->actingAs($utilisateur)
        ->put(route('transcriptions.classement.update', $transcription), ['dossier_id' => $second->getKey()])
        ->assertSessionHasNoErrors();

    expect($transcription->fresh()->dossier_id)->toBe($second->getKey());
});

it('classe une transcription depuis sa page, avec plusieurs étiquettes de types différents', function () {
    $utilisateur = bibPro();
    $niche = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café');
    $concurrent = bibEtiquette($utilisateur, TypeEtiquette::Concurrent, 'Nespresso');
    $transcription = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->put(route('transcriptions.classement.update', $transcription), ['etiquettes' => [$niche->getKey(), $concurrent->getKey()]])
        ->assertRedirect(route('transcriptions.classement', $transcription))
        ->assertSessionHas('statut', 'classement-enregistre');

    expect($transcription->fresh()->etiquettes->pluck('id')->sort()->values()->all())
        ->toBe(collect([$niche->getKey(), $concurrent->getKey()])->sort()->values()->all());
});

it('réutilise une étiquette existante du même type, sans tenir compte de la casse ni des espaces', function () {
    $utilisateur = bibPro();
    $premiere = bibTranscription($utilisateur);
    $seconde = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)->put(route('transcriptions.classement.update', $premiere), [
        'nouvelle_etiquette' => 'Nike',
        'nouvelle_etiquette_type' => 'produit',
    ]);

    $this->actingAs($utilisateur)->put(route('transcriptions.classement.update', $seconde), [
        'nouvelle_etiquette' => '  nike ',
        'nouvelle_etiquette_type' => 'produit',
    ]);

    expect(Etiquette::query()->where('user_id', $utilisateur->getKey())->count())->toBe(1);
    expect($seconde->fresh()->etiquettes->first()->nom)->toBe('Nike');
});

it('garde un même nom dans deux types différents, mais pas deux fois dans le même type', function () {
    $utilisateur = bibPro();
    $transcription = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)->put(route('transcriptions.classement.update', $transcription), [
        'nouvelle_etiquette' => 'Nike',
        'nouvelle_etiquette_type' => 'produit',
    ]);

    $this->actingAs($utilisateur)->put(route('transcriptions.classement.update', $transcription), [
        'nouvelle_etiquette' => 'Nike',
        'nouvelle_etiquette_type' => 'concurrent',
    ]);

    expect(Etiquette::query()->where('user_id', $utilisateur->getKey())->count())->toBe(2);
});

it('propose les étiquettes déjà créées dans la liste d’autocomplétion de la page de classement', function () {
    $utilisateur = bibPro();
    bibEtiquette($utilisateur, TypeEtiquette::Produit, 'Lampe LED');
    $transcription = bibTranscription($utilisateur);

    $this->actingAs($utilisateur)
        ->get(route('transcriptions.classement', $transcription))
        ->assertOk()
        ->assertSee('<option value="Lampe LED">', false);
});

it('crée un dossier et refuse un second dossier de même nom, sans tenir compte de la casse', function () {
    $utilisateur = bibPro();

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.dossiers.store'), ['nom' => 'Niche café'])
        ->assertSessionHas('statut', 'dossier-cree');

    $this->actingAs($utilisateur)
        ->post(route('bibliotheque.dossiers.store'), ['nom' => 'NICHE CAFÉ'])
        ->assertSessionHasErrors('nom');

    expect(Dossier::query()->where('user_id', $utilisateur->getKey())->count())->toBe(1);
});

it('renomme un dossier et refuse un nom déjà pris par un autre dossier', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Niche café');
    bibDossier($utilisateur, 'Produit lampe');

    $this->actingAs($utilisateur)
        ->patch(route('bibliotheque.dossiers.update', $dossier), ['nom' => 'PRODUIT lampe'])
        ->assertSessionHasErrors('nom');

    $this->actingAs($utilisateur)
        ->patch(route('bibliotheque.dossiers.update', $dossier), ['nom' => 'Niche thé'])
        ->assertSessionHasNoErrors();

    expect($dossier->fresh()->nom)->toBe('Niche thé');
    expect($dossier->fresh()->nom_normalise)->toBe('niche thé');
});

it('supprime un dossier sans supprimer ses transcriptions, qui reviennent dans « Non classé »', function () {
    $utilisateur = bibPro();
    $dossier = bibDossier($utilisateur, 'Concurrents');
    $transcription = bibTranscription($utilisateur, ['dossier' => $dossier]);

    $this->actingAs($utilisateur)
        ->delete(route('bibliotheque.dossiers.destroy', $dossier))
        ->assertSessionHas('statut', 'dossier-supprime');

    expect(Dossier::find($dossier->getKey()))->toBeNull();
    expect(Transcription::find($transcription->getKey()))->not->toBeNull();
    expect($transcription->fresh()->dossier_id)->toBeNull();
});

it('renomme une étiquette, puis la supprime sans supprimer les transcriptions qu’elle portait', function () {
    $utilisateur = bibPro();
    $etiquette = bibEtiquette($utilisateur, TypeEtiquette::Niche, 'Café');
    $transcription = bibTranscription($utilisateur);
    $etiquette->transcriptions()->attach($transcription->getKey());

    $this->actingAs($utilisateur)
        ->patch(route('bibliotheque.etiquettes.update', $etiquette), ['nom' => 'Café de spécialité', 'type' => 'niche'])
        ->assertSessionHas('statut', 'etiquette-renommee');

    expect($etiquette->fresh()->nom)->toBe('Café de spécialité');

    $this->actingAs($utilisateur)
        ->delete(route('bibliotheque.etiquettes.destroy', $etiquette))
        ->assertSessionHas('statut', 'etiquette-supprimee');

    expect(Etiquette::find($etiquette->getKey()))->toBeNull();
    expect($transcription->fresh()->etiquettes)->toHaveCount(0);
    expect(Transcription::find($transcription->getKey()))->not->toBeNull();
});

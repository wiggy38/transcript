<?php

use App\Enums\Plan;
use App\Models\Transcription;
use App\Models\User;
use App\Services\Plans\PlanFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Utilisateur au plan Gratuit (défaut), en français. */
function utilisateurHistorique(): User
{
    $utilisateur = User::factory()->create(['locale' => 'fr']);
    app()->setLocale('fr');

    return $utilisateur;
}

/** Transcription terminée de ce compte, créée à la date donnée (created_at n'est pas assignable). */
function transcriptionDatee(User $utilisateur, string $titre, ?DateTimeInterface $date = null, array $attributs = []): Transcription
{
    $date ??= now();

    $transcription = Transcription::factory()->termine()->for($utilisateur)->create(array_merge(['titre' => $titre], $attributs));
    $transcription->forceFill(['created_at' => $date])->save();

    return $transcription;
}

// Critère F5 (liste) : miniature, titre, plateforme, durée, date et badge de statut.
it('affiche la miniature, le titre, la plateforme, la durée, la date et le statut de chaque transcription', function () {
    $utilisateur = utilisateurHistorique();

    transcriptionDatee($utilisateur, 'Pub café de printemps', now()->setDate(2026, 10, 14)->setTime(9, 30), [
        'source' => 'youtube',
        'duree_secondes' => 754,
        'miniature_url' => 'https://i.ytimg.com/vi/abc/hqdefault.jpg',
    ]);

    transcriptionDatee($utilisateur, 'Reel TikTok sans miniature', now()->setDate(2026, 10, 15)->setTime(18, 5), [
        'source' => 'tiktok',
        'duree_secondes' => 42,
        'miniature_url' => null,
        'url_origine' => 'https://www.tiktok.com/@marque/video/1',
    ]);

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertOk()
        ->assertSee('src="https://i.ytimg.com/vi/abc/hqdefault.jpg"', false)
        ->assertSee('Pub café de printemps')
        ->assertSee(__('historique.plateformes.youtube'))
        ->assertSee(__('historique.plateformes.tiktok'))
        ->assertSee('12:34')
        ->assertSee('00:42')
        ->assertSee('14 octobre 2026 à 09:30')
        ->assertSee('15 octobre 2026 à 18:05')
        ->assertSee(__('interface.statuts.termine'));
});

// Critère F5 (tri) : tri par date, la plus récente d'abord, et l'inverse sur demande.
it('trie les transcriptions par date, la plus récente d’abord', function () {
    $utilisateur = utilisateurHistorique();

    transcriptionDatee($utilisateur, 'Ancienne', now()->subDays(5));
    transcriptionDatee($utilisateur, 'Récente', now()->subDay());
    transcriptionDatee($utilisateur, 'Intermédiaire', now()->subDays(3));

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertSeeInOrder(['Récente', 'Intermédiaire', 'Ancienne']);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['tri' => 'ancien']))
        ->assertSeeInOrder(['Ancienne', 'Intermédiaire', 'Récente']);
});

// Critère F5 (pagination) : la liste se découpe en pages.
it('pagine la liste selon voxia.historique.par_page', function () {
    config(['voxia.historique.par_page' => 2]);
    $utilisateur = utilisateurHistorique();

    foreach (range(1, 5) as $jour) {
        transcriptionDatee($utilisateur, "Vidéo jour {$jour}", now()->subDays($jour));
    }

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertSee('Vidéo jour 1')
        ->assertSee('Vidéo jour 2')
        ->assertDontSee('Vidéo jour 3')
        ->assertSee('?page=2', false);

    $this->actingAs($utilisateur)
        ->get(route('historique', ['page' => 3]))
        ->assertSee('Vidéo jour 5')
        ->assertDontSee('Vidéo jour 4');
});

it('n’affiche que les transcriptions du compte connecté', function () {
    $moi = utilisateurHistorique();
    $autre = User::factory()->create(['locale' => 'fr']);

    transcriptionDatee($moi, 'La mienne');
    transcriptionDatee($autre, 'Celle d’un autre compte');

    $this->actingAs($moi)
        ->get(route('historique'))
        ->assertSee('La mienne')
        ->assertDontSee('Celle d’un autre compte');
});

it('affiche la date de suppression prévue au plan Gratuit', function () {
    $utilisateur = utilisateurHistorique();
    $creation = now()->subDays(2);

    transcriptionDatee($utilisateur, 'Vidéo gratuite', $creation);

    $this->actingAs($utilisateur)
        ->get(route('historique'))
        ->assertSee(__('historique.suppression_prevue', [
            'date' => $creation->copy()->addDays(30)->locale('fr')->isoFormat('LL'),
        ]));
});

// Recherche en Gratuit : le champ est visible et propose le Pro ; aucune recherche ne part.
it('affiche le champ de recherche en Gratuit, avec la proposition de passer au Pro', function () {
    $utilisateur = utilisateurHistorique();

    transcriptionDatee($utilisateur, 'Vidéo sur le café');

    $this->actingAs($utilisateur)
        ->get(route('historique', ['q' => 'café']))
        ->assertOk()
        ->assertSee('name="q"', false)
        ->assertSee(__('historique.recherche.gratuit'))
        ->assertDontSee('<mark', false)
        ->assertSee('Vidéo sur le café');
});

it('demande la connexion pour voir l’historique', function () {
    $this->get(route('historique'))->assertRedirect(route('login'));
});

it('est réservé aux Pro et Pack pour la recherche, pas au Gratuit même si le drapeau de plan est actif', function () {
    config(['voxia.droits.plan_reel' => true]);

    $utilisateur = utilisateurHistorique();
    $utilisateur->forceFill(['plan' => Plan::Free])->save();

    expect(app(PlanFeatures::class)->peutRechercher($utilisateur))->toBeFalse();
});

<?php

require_once __DIR__.'/outils.php';

use App\Data\Resume\ChapitreResume;
use App\Data\Resume\ContenuResume;
use App\Data\Resume\PointResume;
use App\Enums\MotifErreurModele;
use App\Enums\StatutSortieIa;
use App\Exceptions\ErreurModele;
use App\Jobs\GenererResume;
use App\Models\AiOutput;
use App\Services\Resume\LancerResume;
use App\Services\Resume\ServiceResume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

// Critère recherche : le texte indexé porte le résumé, les points et les chapitres, jamais la structure JSON.
it('indexe le résumé, les points et les chapitres sans la structure', function () {
    $contenu = new ContenuResume(
        'Le balcon sud reçoit du soleil.',
        [new PointResume('Choisir un balcon bien orienté.', 2, 20000)],
        [new ChapitreResume('Les bacs', 3, 40000, 'Les bacs doivent être légers. Le drainage compte.')],
    );

    expect($contenu->texteRecherche())
        ->toContain('Le balcon sud reçoit du soleil.')
        ->toContain('Choisir un balcon bien orienté.')
        ->toContain('Les bacs doivent être légers.')
        ->not->toContain('debut_ms')
        ->not->toContain('paragraphe')
        ->not->toContain('points_cles');
});

// Critère recherche : à la réussite, le texte et la configuration de la langue du résumé sont enregistrés.
it('enregistre le texte de recherche et la configuration de la langue du résumé à la réussite', function () {
    $utilisateur = resumeUtilisateur();
    $transcription = resumeTranscriptionCourte($utilisateur, ['langue_detectee' => 'fr']);
    resumeFaux()->repondre(resumeReponse(resumeSortieValide()));

    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription, 'en');
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));

    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($sortie->config_recherche)->toBe('english')
        ->and($sortie->texte_recherche)->toContain('Choisir un balcon bien orienté.');
});

// Critère recherche : un échec n'enregistre pas de texte de recherche.
it('n’enregistre aucun texte de recherche pour un résumé en échec', function () {
    $utilisateur = resumeUtilisateur();
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(new ErreurModele(MotifErreurModele::Delai));

    $sortie = app(LancerResume::class)->lancer($utilisateur, $transcription);
    (new GenererResume($sortie->getKey()))->handle(app(ServiceResume::class));

    expect(AiOutput::find($sortie->getKey())->texte_recherche)->toBeNull();
});

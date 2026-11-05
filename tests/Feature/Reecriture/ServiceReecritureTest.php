<?php

require_once __DIR__.'/outils.php';

use App\Enums\FormatReecriture;
use App\Enums\MotifErreurModele;
use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Exceptions\ErreurModele;
use App\Exceptions\ReecritureRefusee;
use App\Jobs\GenererReecriture;
use App\Models\AiOutput;
use App\Services\Quota\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

it('génère chaque format avec le faux fournisseur, et décompte un crédit', function (FormatReecriture $format, array $structure) {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse($structure));

    $sortie = reecritureLancer($utilisateur, $transcription, [$format])[$format->value];
    reecritureGenerer($sortie);
    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($sortie->contenu)->not->toBeNull()
        ->and($sortie->modele)->toBe('claude-haiku-5-5')
        ->and(reecritureCreditsConsommes($utilisateur))->toBe(1);
})->with([
    'article' => [FormatReecriture::Article, reecritureArticleValide()],
    'newsletter' => [FormatReecriture::Newsletter, reecritureNewsletterValide()],
    'post LinkedIn' => [FormatReecriture::LinkedIn, reecritureLinkedinValide()],
    'thread X' => [FormatReecriture::Thread, reecritureThreadValide()],
]);

it('relance une newsletter dont l’objet dépasse la limite, puis accepte la version corrigée', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $trop = reecritureNewsletterValide();
    $trop['objet'] = str_repeat('Balcon ', 12); // 84 caractères, au-delà de 60
    resumeFaux()->repondre(resumeReponse($trop))->repondre(resumeReponse(reecritureNewsletterValide()));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Newsletter])['newsletter'];
    reecritureGenerer($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeFaux()->requetes)->toHaveCount(2)
        ->and(resumeFaux()->requetes[1]->instructions)->toContain('ne respectait pas')
        ->and(reecritureCreditsConsommes($utilisateur))->toBe(1);
});

it('raccourcit un post LinkedIn trop long en retirant des paragraphes entiers', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $long = [
        'accroche' => 'Un balcon réussi commence par la lumière.',
        'paragraphes' => [
            'Le balcon sud reçoit beaucoup de soleil. '.reecritureMots(120),
            reecritureMots(20, 'bac'),
            reecritureMots(400, 'terre'),
        ],
    ];
    resumeFaux()->repondre(resumeReponse($long))->repondre(resumeReponse($long));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::LinkedIn])['linkedin'];
    reecritureGenerer($sortie);
    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($sortie->contenu['paragraphes'])->toHaveCount(2)
        ->and(mb_strlen(implode("\n\n", [$sortie->contenu['accroche'], ...$sortie->contenu['paragraphes']])))->toBeLessThanOrEqual(1300)
        ->and(resumeFaux()->requetes)->toHaveCount(2);
});

it('garde les repères [à compléter] tels quels, et demande de ne jamais inventer un lien', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(reecritureNewsletterValide('Inscris-toi sur [à compléter].')));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Newsletter])['newsletter'];
    reecritureGenerer($sortie);
    $sortie->refresh();

    expect($sortie->contenu['appel_action'])->toBe('Inscris-toi sur [à compléter].')
        ->and(resumeFaux()->requetes[0]->instructions)->toContain('[à compléter]')
        ->and(resumeFaux()->requetes[0]->instructions)->toContain('N’invente jamais un lien');
});

it('refuse une sortie avec un chiffre absent de la transcription, puis échoue sans décompte', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    $avecChiffre = reecritureNewsletterValide();
    $avecChiffre['accroche'] = 'Le balcon reçoit 2026 heures de soleil.';
    resumeFaux()->repondre(resumeReponse($avecChiffre))->repondre(resumeReponse($avecChiffre));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Newsletter])['newsletter'];
    reecritureGenerer($sortie);
    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Echec)
        ->and($sortie->contenu)->toBeNull()
        ->and(reecritureCreditsConsommes($utilisateur))->toBe(0);
});

it('un échec technique du fournisseur ne décompte aucun crédit', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(new ErreurModele(MotifErreurModele::Delai));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Article])['article'];
    reecritureGenerer($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(reecritureCreditsConsommes($utilisateur))->toBe(0);
});

it('lance plusieurs formats d’un coup : une sortie et une tâche par format, un crédit par format', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);

    $sorties = reecritureLancer($utilisateur, $transcription, [FormatReecriture::Article, FormatReecriture::Newsletter, FormatReecriture::Thread]);

    expect($sorties)->toHaveCount(3)
        ->and(AiOutput::where('transcription_id', $transcription->getKey())->where('fonctionnalite', 'reecriture')->count())->toBe(3);
    Queue::assertPushed(GenererReecriture::class, 3);

    resumeFaux()
        ->repondre(resumeReponse(reecritureArticleValide()))
        ->repondre(resumeReponse(reecritureNewsletterValide()))
        ->repondre(resumeReponse(reecritureThreadValide()));

    foreach ($sorties as $sortie) {
        reecritureGenerer($sortie);
    }

    expect(reecritureCreditsConsommes($utilisateur))->toBe(3);
});

it('avec un solde insuffisant pour tous les formats, rien n’est lancé et le message le dit', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro); // 30 crédits par mois
    $transcription = resumeTranscriptionCourte($utilisateur);
    app(QuotaService::class)->consommerCredits($utilisateur, 28, 'resume', $transcription);

    $erreur = null;

    try {
        reecritureLancer($utilisateur, $transcription, [
            FormatReecriture::Article,
            FormatReecriture::Newsletter,
            FormatReecriture::LinkedIn,
            FormatReecriture::Thread,
        ]);
    } catch (ReecritureRefusee $e) {
        $erreur = $e;
    }

    expect($erreur)->not->toBeNull()
        ->and($erreur->motif)->toBe(ReecritureRefusee::CREDIT_INSUFFISANT)
        ->and($erreur->credits)->toBe(4)
        ->and(AiOutput::where('fonctionnalite', 'reecriture')->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('rouvrir un format déjà généré est gratuit ; le régénérer exige une confirmation et coûte 1 crédit', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = resumeTranscriptionCourte($utilisateur);
    resumeFaux()->repondre(resumeReponse(reecritureLinkedinValide()))->repondre(resumeReponse(reecritureLinkedinValide()));

    $sortie = reecritureLancer($utilisateur, $transcription, [FormatReecriture::LinkedIn])['linkedin'];
    reecritureGenerer($sortie);

    // Rouvrir : aucun crédit, aucune tâche.
    reecritureLancer($utilisateur, $transcription, [FormatReecriture::LinkedIn]);
    expect(reecritureCreditsConsommes($utilisateur))->toBe(1);
    Queue::assertPushed(GenererReecriture::class, 1);

    // Régénérer sans confirmation : refusé.
    expect(fn () => reecritureLancer($utilisateur, $transcription, [FormatReecriture::LinkedIn], regenerer: true))
        ->toThrow(ReecritureRefusee::class);

    // Régénérer avec confirmation : une tâche de plus, un crédit de plus.
    reecritureLancer($utilisateur, $transcription, [FormatReecriture::LinkedIn], regenerer: true, confirme: true);
    Queue::assertPushed(GenererReecriture::class, 2);
});

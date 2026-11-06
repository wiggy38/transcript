<?php

require_once __DIR__.'/outils.php';

use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Exceptions\TraductionRefusee;
use App\Jobs\GenererTraduction;
use App\Models\AiOutput;
use App\Services\Traduction\LancerTraduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

/** Refus attendu : renvoie le motif de la TraductionRefusee levée par le lancement. */
function traductionMotifRefus(callable $lancement): string
{
    try {
        $lancement();
    } catch (TraductionRefusee $erreur) {
        return $erreur->motif;
    }

    return 'aucun refus';
}

it('affiche le coût avant lancement : une heure entamée et une langue font un crédit', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    // 3 700 secondes : deux heures entamées, donc deux crédits par langue.
    $transcription = traductionTranscription($utilisateur, 500, ['duree_secondes' => 3700]);

    expect(app(LancerTraduction::class)->coutPour($transcription, ['en', 'de']))->toBe(4)
        ->and(app(LancerTraduction::class)->coutPour($transcription, ['en']))->toBe(2);
});

it('lance plusieurs langues d’un coup et les met toutes en file', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);

    $sorties = traductionLancer($utilisateur, $transcription, ['en', 'es', 'pt']);

    expect(array_keys($sorties))->toBe(['en', 'es', 'pt'])
        ->and(AiOutput::where('fonctionnalite', 'traduction')->count())->toBe(3)
        ->and(AiOutput::where('statut', StatutSortieIa::EnAttente)->count())->toBe(3);

    Queue::assertPushed(GenererTraduction::class, 3);
});

it('refuse tout un lot quand le solde ne couvre pas toutes les langues, et ne lance rien', function () {
    config(['voxia.plans.pro.ai_credits_per_month' => 3]);
    $utilisateur = resumeUtilisateur(Plan::Pro);
    // Deux heures entamées : deux crédits par langue. Trois langues font six crédits, le solde en a trois.
    $transcription = traductionTranscription($utilisateur, 500, ['duree_secondes' => 3700]);

    $motif = traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['en', 'es', 'pt']));

    expect($motif)->toBe(TraductionRefusee::CREDIT_INSUFFISANT)
        ->and(AiOutput::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('refuse un plan sans crédits IA (Gratuit) avant toute écriture', function () {
    $utilisateur = resumeUtilisateur(Plan::Free);
    $transcription = traductionTranscription($utilisateur);

    expect(traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['en'])))
        ->toBe(TraductionRefusee::CREDIT_INSUFFISANT)
        ->and(AiOutput::count())->toBe(0);
});

it('rouvre une langue déjà terminée sans coût ni nouvel appel', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);

    resumeFaux()
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 0))))
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 30))));
    traductionExecuter($sortie);

    Queue::fake();
    $aFacturer = app(LancerTraduction::class)->coutPour($transcription, ['en']);
    $rouverte = traductionLancer($utilisateur, $transcription, ['en'])['en'];

    expect($aFacturer)->toBe(0)
        ->and($rouverte->statut)->toBe(StatutSortieIa::Termine)
        ->and(traductionCreditsConsommes($utilisateur))->toBe(1);
    Queue::assertNothingPushed();
});

it('relance une langue en échec, puis ne facture qu’à la réussite', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);
    $echoue = traductionMorceau(traductionMorceauN($segments, 0));
    $echoue['segments'][0]['id'] = 'inconnu';

    resumeFaux()->repondre(traductionReponseModele($echoue))->repondre(traductionReponseModele($echoue));
    traductionExecuter($sortie);
    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(traductionCreditsConsommes($utilisateur))->toBe(0);

    $relancee = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    expect($relancee->statut)->toBe(StatutSortieIa::EnAttente);
    Queue::assertPushed(GenererTraduction::class, 2);
});

it('refuse la langue source comme cible, une langue hors liste, ou une langue source non prise en charge', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);

    expect(traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['fr'])))
        ->toBe(TraductionRefusee::MEME_LANGUE)
        ->and(traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['ja'])))
        ->toBe(TraductionRefusee::LANGUE_INVALIDE)
        ->and(traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['ar'])))
        ->toBe(TraductionRefusee::LANGUE_INVALIDE);

    $transcription->forceFill(['langue_detectee' => 'ja'])->save();

    expect(traductionMotifRefus(fn () => traductionLancer($utilisateur, $transcription, ['en'])))
        ->toBe(TraductionRefusee::SOURCE_NON_PRISE_EN_CHARGE)
        ->and(AiOutput::count())->toBe(0);
});

it('propose les langues cibles sans la langue source', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);

    expect(app(LancerTraduction::class)->languesCibles($transcription))
        ->toBe(['en', 'es', 'de', 'it', 'pt']);
});

it('refuse la traduction d’une transcription d’un autre compte', function () {
    $proprietaire = resumeUtilisateur(Plan::Pro);
    $autre = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($proprietaire);

    expect(traductionMotifRefus(fn () => traductionLancer($autre, $transcription, ['en'])))
        ->toBe(TraductionRefusee::NON_TERMINEE);
});

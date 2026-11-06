<?php

require_once __DIR__.'/outils.php';

use App\Enums\MotifErreurModele;
use App\Enums\Plan;
use App\Enums\StatutSortieIa;
use App\Exceptions\ErreurModele;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake());

it('conserve le nombre de segments et leurs horodatages, et décompte un crédit par langue', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);

    resumeFaux()
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 0))))
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 30))));
    traductionExecuter($sortie);
    $sortie->refresh();

    $resultat = $sortie->contenu['segments'];

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and(count($segments))->toBeGreaterThan(30)
        ->and(count($resultat))->toBe(count($segments))
        ->and(array_column($resultat, 'debut_ms'))->toBe(array_column($segments, 'debut_ms'))
        ->and(array_column($resultat, 'fin_ms'))->toBe(array_column($segments, 'fin_ms'))
        ->and($resultat[0]['texte'])->toStartWith('EN : ')
        ->and(traductionCreditsConsommes($utilisateur))->toBe(1);
});

it('envoie des segments de contexte, sans les renvoyer dans la sortie', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);

    resumeFaux()
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 0))))
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 30))));
    traductionExecuter($sortie);

    $deuxieme = json_decode(resumeFaux()->requetes[1]->donnees[0], true);

    expect(array_column($deuxieme['contexte_avant'], 'id'))->toBe(array_column(array_slice($segments, 27, 3), 'id'))
        ->and($deuxieme['contexte_avant'][0])->toHaveKey('traduction')
        ->and(array_column($deuxieme['a_traduire'], 'id'))->toBe(array_column(array_slice($segments, 30, 30), 'id'))
        ->and(array_column($deuxieme['contexte_apres'], 'id'))->toBe(array_column(array_slice($segments, 60), 'id'))
        ->and(resumeFaux()->requetes[1]->instructions)->not->toContain('s0031');
});

it('relance un morceau dont la sortie perd un segment, puis accepte la version corrigée', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);
    $perdu = traductionMorceau(traductionMorceauN($segments, 0));
    array_pop($perdu['segments']);

    resumeFaux()
        ->repondre(traductionReponseModele($perdu))
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 0))))
        ->repondre(traductionReponseModele(traductionMorceau(traductionMorceauN($segments, 30))));
    traductionExecuter($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Termine)
        ->and(resumeFaux()->requetes)->toHaveCount(3)
        ->and(resumeFaux()->requetes[1]->instructions)->toContain('ne contenait pas exactement')
        ->and(traductionCreditsConsommes($utilisateur))->toBe(1);
});

it('échoue proprement après une relance sans identifiant attendu, sans décompte', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);

    $invente = traductionMorceau(traductionMorceauN($segments, 0));
    $invente['segments'][3]['id'] = 's9999';

    resumeFaux()->repondre(traductionReponseModele($invente))->repondre(traductionReponseModele($invente));
    traductionExecuter($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(traductionCreditsConsommes($utilisateur))->toBe(0);
});

it('refuse une fusion, un identifiant répété ou un ordre inversé, puis échoue sans décompte', function (string $defaut) {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];
    $segments = traductionSegments($sortie);
    $mauvais = traductionMorceau(traductionMorceauN($segments, 0));

    if ($defaut === 'fusion') {
        array_splice($mauvais['segments'], 4, 2); // deux segments remplacés par un seul : un identifiant manque
    } elseif ($defaut === 'doublon') {
        $mauvais['segments'][5]['id'] = $mauvais['segments'][4]['id'];
    } else {
        [$mauvais['segments'][1], $mauvais['segments'][2]] = [$mauvais['segments'][2], $mauvais['segments'][1]];
    }

    resumeFaux()->repondre(traductionReponseModele($mauvais))->repondre(traductionReponseModele($mauvais));
    traductionExecuter($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(traductionCreditsConsommes($utilisateur))->toBe(0);
})->with(['segment absent' => ['fusion'], 'identifiant répété' => ['doublon'], 'ordre inversé' => ['ordre']]);

it('échec technique du modèle : rien n’est décompté', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur);
    $sortie = traductionLancer($utilisateur, $transcription, ['en'])['en'];

    resumeFaux()->repondre(new ErreurModele(MotifErreurModele::CleAbsente));
    traductionExecuter($sortie);

    expect($sortie->refresh()->statut)->toBe(StatutSortieIa::Echec)
        ->and(traductionCreditsConsommes($utilisateur))->toBe(0);
});

it('signale une traduction encore trop longue après une demande de raccourcir, sans rien couper', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur, 60);
    $sortie = traductionLancer($utilisateur, $transcription, ['de'])['de'];
    $segments = traductionSegments($sortie);
    // 1 400 caractères : plus que 7 sous-titres de 2 lignes ne peuvent en contenir, même pour un segment de 7 s.
    $long = array_map(fn (array $s) => ['id' => $s['id'], 'texte' => str_repeat('Garten ', 200)], $segments);

    resumeFaux()
        ->repondre(traductionReponseModele(['segments' => $long]))
        ->repondre(traductionReponseModele(['segments' => $long]));
    traductionExecuter($sortie);
    $sortie->refresh();

    expect($sortie->statut)->toBe(StatutSortieIa::Termine)
        ->and($sortie->contenu['avertissements'])->toBe(count($segments))
        ->and($sortie->contenu['segments'][0]['trop_long'])->toBeTrue()
        ->and($sortie->contenu['segments'][0]['texte'])->toContain('Garten Garten')
        ->and(resumeFaux()->requetes[1]->instructions)->toContain('dépassaient la longueur maximale');
});

it('accepte la version raccourcie quand elle tient dans sa durée', function () {
    $utilisateur = resumeUtilisateur(Plan::Pro);
    $transcription = traductionTranscription($utilisateur, 60);
    $sortie = traductionLancer($utilisateur, $transcription, ['de'])['de'];
    $segments = traductionSegments($sortie);
    $long = array_map(fn (array $s) => ['id' => $s['id'], 'texte' => str_repeat('Garten ', 200)], $segments);
    $court = array_map(fn (array $s) => ['id' => $s['id'], 'texte' => 'Kurzer Garten.'], $segments);

    resumeFaux()
        ->repondre(traductionReponseModele(['segments' => $long]))
        ->repondre(traductionReponseModele(['segments' => $court]));
    traductionExecuter($sortie);
    $sortie->refresh();

    expect($sortie->contenu['avertissements'])->toBe(0)
        ->and($sortie->contenu['segments'][0]['texte'])->toBe('Kurzer Garten.');
});

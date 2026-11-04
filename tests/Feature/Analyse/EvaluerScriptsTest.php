<?php

require_once __DIR__.'/outils.php';

use App\Exceptions\ErreurAnalyse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fichierEvaluation(): string
{
    return __DIR__.'/../../fixtures/analyse/pubs-test.json';
}

// Critère F7 (évaluation) : la part de limites justes à 2 s près est calculée sur les pubs de référence,
// et la commande réussit à partir de la cible (80 %).
it('calcule la part de limites justes et réussit au-dessus de la cible', function () {
    analyseFaux()->repondre(analyseReponse())->repondre(analyseReponse());

    $this->artisan('voxia:eval-scripts', ['--fichier' => fichierEvaluation()])
        ->expectsOutputToContain('Limites justes : 20 / 20 (100.0 %)')
        ->assertExitCode(0);
});

// Une pub dont l'analyse échoue compte toutes ses limites comme fausses : 10 / 20 = 50 %, sous la cible.
it('échoue sous la cible, une analyse en échec comptant comme fausse', function () {
    analyseFaux()->repondre(analyseReponse())->echouer(new ErreurAnalyse('réponse illisible'));

    $this->artisan('voxia:eval-scripts', ['--fichier' => fichierEvaluation()])
        ->expectsOutputToContain('Limites justes : 10 / 20 (50.0 %)')
        ->assertExitCode(1);
});

it('signale un fichier de référence absent ou mal formé', function () {
    $this->artisan('voxia:eval-scripts', ['--fichier' => __DIR__.'/absent.json'])
        ->expectsOutputToContain('Fichier de référence introuvable')
        ->assertExitCode(1);
});

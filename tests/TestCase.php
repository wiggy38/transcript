<?php

namespace Tests;

use App\Contracts\BillingProvider;
use App\Contracts\LanguageModel;
use App\Contracts\ScriptAnalyzer;
use App\Contracts\TranscriptionEngine;
use App\Services\Analyse\FakeScriptAnalyzer;
use App\Services\Ia\FauxLanguageModel;
use App\Services\Paiement\FauxBillingProvider;
use App\Services\Transcription\FakeTranscriptionEngine;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aucun test ne doit appeler une API réelle : toute requête non simulée échoue.
        Http::preventStrayRequests();

        // Tous les tests utilisent le faux moteur de transcription.
        $this->app->instance(TranscriptionEngine::class, new FakeTranscriptionEngine);

        // Idem pour l'analyse de script : le faux analyseur, jamais l'API Claude.
        $this->app->instance(ScriptAnalyzer::class, new FakeScriptAnalyzer);

        // Le résumé (V2) et tout autre appel au modèle de langage : le faux modèle, jamais l'API.
        $this->app->instance(LanguageModel::class, new FauxLanguageModel);

        // Le paiement : le faux fournisseur, jamais Paddle. Les webhooks restent réels (signés, en base).
        $this->app->instance(BillingProvider::class, new FauxBillingProvider);

        // Les tests lisent le plan enregistré sur le compte (users.plan), comme le fera la facturation.
        // Sans cela, PlanFeatures::planDe() ramènerait tout le monde au Gratuit (voxia.droits.plan_reel).
        config(['voxia.droits.plan_reel' => true]);
    }
}

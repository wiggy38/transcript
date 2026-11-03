<?php

namespace Tests;

use App\Contracts\TranscriptionEngine;
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
    }
}

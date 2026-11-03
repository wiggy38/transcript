<?php

namespace Database\Factories;

use App\Enums\StatutAnalyseScript;
use App\Models\ScriptAnalysis;
use App\Models\Transcription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptAnalysis>
 */
class ScriptAnalysisFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transcription_id' => Transcription::factory(),
            'statut' => StatutAnalyseScript::EnAttente,
            'fournisseur' => null,
            'cout_estime_usd' => 0,
        ];
    }

    public function termine(): static
    {
        return $this->state(fn () => [
            'statut' => StatutAnalyseScript::Termine,
            'date_termine' => now(),
        ]);
    }
}

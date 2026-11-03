<?php

namespace Database\Factories;

use App\Enums\TypePartieScript;
use App\Models\ScriptAnalysis;
use App\Models\ScriptPart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptPart>
 */
class ScriptPartFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debut = fake()->numberBetween(0, 50_000);

        return [
            'script_analysis_id' => ScriptAnalysis::factory(),
            'position' => 1,
            'type' => TypePartieScript::Accroche,
            'debut_ms' => $debut,
            'fin_ms' => $debut + fake()->numberBetween(2_000, 15_000),
            'texte' => fake()->sentence(8),
            'detectee' => true,
        ];
    }

    /** Partie du script à une position donnée (1 à 5), dans l'ordre de la vidéo. */
    public function pour(TypePartieScript $type, int $position): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'position' => $position,
        ]);
    }

    /** Partie absente de la pub : marquée « non détectée », sans bornes ni texte. */
    public function nonDetectee(): static
    {
        return $this->state(fn () => [
            'debut_ms' => null,
            'fin_ms' => null,
            'texte' => null,
            'detectee' => false,
        ]);
    }
}

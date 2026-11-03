<?php

namespace Database\Factories;

use App\Enums\EtapeTraitement;
use App\Models\ProcessingLog;
use App\Models\Transcription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingLog>
 */
class ProcessingLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transcription_id' => Transcription::factory(),
            'etape' => EtapeTraitement::Telechargement,
            'duree_ms' => fake()->numberBetween(500, 120_000),
            'succes' => true,
            'message' => null,
            'cout_usd' => 0,
        ];
    }

    public function echec(string $message = 'Échec de l\'étape.'): static
    {
        return $this->state(fn () => [
            'succes' => false,
            'message' => $message,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Transcription;
use App\Models\TranscriptionWord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranscriptionWord>
 */
class TranscriptionWordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debut = fake()->numberBetween(0, 3_600_000);

        return [
            'transcription_id' => Transcription::factory(),
            'position' => fake()->unique()->numberBetween(1, 100_000),
            'texte' => fake()->word(),
            'debut_ms' => $debut,
            'fin_ms' => $debut + fake()->numberBetween(120, 800),
        ];
    }
}

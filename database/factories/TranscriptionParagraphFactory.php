<?php

namespace Database\Factories;

use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranscriptionParagraph>
 */
class TranscriptionParagraphFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debut = fake()->numberBetween(0, 3_600_000);

        return [
            'transcription_id' => Transcription::factory(),
            'position' => fake()->unique()->numberBetween(1, 10_000),
            'debut_ms' => $debut,
            'fin_ms' => $debut + fake()->numberBetween(5_000, 60_000),
            'texte' => fake()->sentence(12),
        ];
    }
}

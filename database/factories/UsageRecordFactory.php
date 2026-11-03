<?php

namespace Database\Factories;

use App\Enums\UniteConsommation;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transcription_id' => null,
            'script_analysis_id' => null,
            'unite' => UniteConsommation::Video,
            'quantite' => 1,
            'motif' => null,
        ];
    }

    /** Consommation en secondes de vidéo. */
    public function secondes(int $secondes): static
    {
        return $this->state(fn () => [
            'unite' => UniteConsommation::Secondes,
            'quantite' => $secondes,
        ]);
    }

    public function creditIa(): static
    {
        return $this->state(fn () => [
            'unite' => UniteConsommation::CreditIa,
            'quantite' => 1,
        ]);
    }
}

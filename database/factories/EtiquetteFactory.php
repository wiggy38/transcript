<?php

namespace Database\Factories;

use App\Enums\TypeEtiquette;
use App\Models\Etiquette;
use App\Models\User;
use App\Support\Libelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etiquette>
 */
class EtiquetteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nom = fake()->unique()->word();

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(TypeEtiquette::cases()),
            'nom' => $nom,
            'nom_normalise' => Libelle::normaliser($nom),
        ];
    }
}

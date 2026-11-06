<?php

namespace Database\Factories;

use App\Models\Dossier;
use App\Models\User;
use App\Support\Libelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dossier>
 */
class DossierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'nom' => $nom,
            'nom_normalise' => Libelle::normaliser($nom),
        ];
    }
}

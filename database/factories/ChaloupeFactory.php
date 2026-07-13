<?php

namespace Database\Factories;

use App\Enums\StatutChaloupeEnum;
use App\Models\Chaloupe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chaloupe>
 */
class ChaloupeFactory extends Factory
{
    protected $model = Chaloupe::class;

    public function definition(): array
    {
        return [
            'imatriculation' => 'IM-'.strtoupper(fake()->unique()->bothify('??###')),
            'nom' => fake()->randomElement(['Beer', 'Coumba Castel', 'Le Joola', 'Gorée Express', 'Ndar']),
            'capacite' => fake()->numberBetween(50, 200),
            'statut' => StatutChaloupeEnum::ACTIVE->value,
        ];
    }

    public function enMaintenance(): static
    {
        return $this->state(['statut' => StatutChaloupeEnum::EN_MAINTENANCE->value]);
    }
}

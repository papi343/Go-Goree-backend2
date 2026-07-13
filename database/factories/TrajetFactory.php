<?php

namespace Database\Factories;

use App\Enums\JourEnum;
use App\Models\Trajet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trajet>
 */
class TrajetFactory extends Factory
{
    protected $model = Trajet::class;

    public function definition(): array
    {
        return [
            'jour' => fake()->randomElement(JourEnum::cases())->value,
            'heure_depart' => fake()->randomElement(['07:30', '10:00', '12:30', '16:00', '18:30']),
            'duree' => fake()->randomFloat(2, 15, 45),
        ];
    }
}

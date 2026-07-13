<?php

namespace Database\Factories;

use App\Models\Chaloupe;
use App\Models\Trajet;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voyage>
 */
class VoyageFactory extends Factory
{
    protected $model = Voyage::class;

    public function definition(): array
    {
        $places = fake()->numberBetween(50, 150);

        return [
            'date_voyage' => now()->addDays(fake()->numberBetween(0, 7))->toDateString(),
            'places' => $places,
            'places_restantes' => $places,
            'trajet_id' => Trajet::factory(),
            'chaloupe_id' => Chaloupe::factory(),
        ];
    }

    public function complet(): static
    {
        return $this->state(['places_restantes' => 0]);
    }

    public function placesRestantes(int $n): static
    {
        return $this->state(['places_restantes' => $n]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\StatutEmbarquementEnum;
use App\Models\Embarquement;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Embarquement>
 */
class EmbarquementFactory extends Factory
{
    protected $model = Embarquement::class;

    public function definition(): array
    {
        return [
            'statut' => StatutEmbarquementEnum::OUVERT->value,
            'ouvert_a' => now(),
            'ferme_a' => null,
            'voyage_id' => Voyage::factory(),
            'ouvert_par' => null,
        ];
    }

    public function ferme(): static
    {
        return $this->state([
            'statut' => StatutEmbarquementEnum::FERME->value,
            'ferme_a' => now(),
        ]);
    }
}

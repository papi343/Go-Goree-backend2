<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $duree = fake()->randomElement([1, 6, 12]);

        return [
            'nom' => "Abonnement {$duree} mois",
            'duree_mois' => $duree,
            'prix' => $duree * 5000,
            'actif' => true,
        ];
    }

    public function duree(int $mois, float $prix): static
    {
        return $this->state(['nom' => "Abonnement {$mois} mois", 'duree_mois' => $mois, 'prix' => $prix]);
    }
}

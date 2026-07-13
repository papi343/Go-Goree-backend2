<?php

namespace Database\Factories;

use App\Enums\MouvementPortefeuilleEnum;
use App\Enums\StatutMouvementEnum;
use App\Models\MouvementPortefeuille;
use App\Models\Portefeuille;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MouvementPortefeuille>
 */
class MouvementPortefeuilleFactory extends Factory
{
    protected $model = MouvementPortefeuille::class;

    public function definition(): array
    {
        return [
            'montant' => fake()->randomElement([500, 1500, 5000]),
            'type' => MouvementPortefeuilleEnum::RECHARGE->value,
            'payement_id' => null,
            'statut' => StatutMouvementEnum::VALIDE->value,
            'portefeuille_id' => Portefeuille::factory(),
        ];
    }

    public function debit(): static
    {
        return $this->state(['type' => MouvementPortefeuilleEnum::DEBIT->value]);
    }
}

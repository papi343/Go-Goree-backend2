<?php

namespace Database\Factories;

use App\Models\Portefeuille;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portefeuille>
 */
class PortefeuilleFactory extends Factory
{
    protected $model = Portefeuille::class;

    public function definition(): array
    {
        return [
            'solde' => 0,
            'user_id' => User::factory(),
        ];
    }

    public function solde(float $solde): static
    {
        return $this->state(['solde' => $solde]);
    }
}

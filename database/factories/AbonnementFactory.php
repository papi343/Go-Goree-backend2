<?php

namespace Database\Factories;

use App\Models\Abonnement;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Abonnement>
 */
class AbonnementFactory extends Factory
{
    protected $model = Abonnement::class;

    public function definition(): array
    {
        return [
            'date_debut' => now(),
            'date_fin' => now()->addMonths(12),
            'montant' => 5000,
            'resident_id' => Resident::factory(),
        ];
    }

    public function actif(): static
    {
        return $this->state([
            'date_debut' => now()->subMonth(),
            'date_fin' => now()->addMonths(11),
        ]);
    }

    public function expire(): static
    {
        return $this->state([
            'date_debut' => now()->subMonths(13),
            'date_fin' => now()->subMonth(),
        ]);
    }
}

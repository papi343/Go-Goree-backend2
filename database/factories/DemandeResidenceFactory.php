<?php

namespace Database\Factories;

use App\Enums\DemandeResidenceEnum;
use App\Models\DemandeResidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeResidence>
 */
class DemandeResidenceFactory extends Factory
{
    protected $model = DemandeResidence::class;

    public function definition(): array
    {
        return [
            'carte_identite' => 'CNI'.fake()->numerify('##########'),
            'residence' => fake()->randomElement(['Gorée Centre', 'Gorée Nord', 'Gorée Sud']),
            'statut' => DemandeResidenceEnum::EN_COURS->value,
            'photo' => 'photo_'.fake()->uuid().'.png',
            'user_id' => User::factory()->client(),
        ];
    }

    public function acceptee(): static
    {
        return $this->state(['statut' => DemandeResidenceEnum::ACCEPTEE->value]);
    }

    public function refusee(): static
    {
        return $this->state([
            'statut' => DemandeResidenceEnum::REFUSEE->value,
            'motif_refus' => 'Justificatifs insuffisants.',
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\StatutBilletEnum;
use App\Models\Billet;
use App\Models\Tarif;
use App\Models\User;
use App\Models\Voyage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Billet>
 */
class BilletFactory extends Factory
{
    protected $model = Billet::class;

    public function definition(): array
    {
        return [
            'qr_token' => 'QR_'.Str::random(32),
            'montant' => fake()->randomElement([500, 1500, 2500]),
            'statut' => StatutBilletEnum::PAYE->value,
            'voyage_id' => Voyage::factory(),
            'tarif_id' => Tarif::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function statut(StatutBilletEnum $statut): static
    {
        return $this->state(['statut' => $statut->value]);
    }

    public function paye(): static
    {
        return $this->statut(StatutBilletEnum::PAYE);
    }

    public function utilise(): static
    {
        return $this->statut(StatutBilletEnum::UTILISE);
    }

    public function expire(): static
    {
        return $this->statut(StatutBilletEnum::EXPIRE);
    }

    public function enAttente(): static
    {
        return $this->statut(StatutBilletEnum::EN_ATTENTE_PAIEMENT);
    }
}

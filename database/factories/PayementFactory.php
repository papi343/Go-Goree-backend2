<?php

namespace Database\Factories;

use App\Enums\ModePayementEnum;
use App\Enums\StatutPayementEnum;
use App\Enums\TypeTransactionPayDunyaEnum;
use App\Models\Payement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payement>
 */
class PayementFactory extends Factory
{
    protected $model = Payement::class;

    public function definition(): array
    {
        return [
            'reference' => 'PAY_'.Str::random(12),
            'montant' => fake()->randomElement([500, 1500, 5000]),
            'statut' => StatutPayementEnum::EN_COURS->value,
            'mode' => ModePayementEnum::PAYDUNYA->value,
            'type_transaction' => TypeTransactionPayDunyaEnum::RECHARGE_PORTEFEUILLE->value,
            'paydunya_token' => 'tok_'.Str::random(20),
            'billet_id' => null,
            'user_id' => User::factory(),
        ];
    }

    public function accepte(): static
    {
        return $this->state(['statut' => StatutPayementEnum::ACCEPTE->value]);
    }

    public function refuse(): static
    {
        return $this->state(['statut' => StatutPayementEnum::REFUSE->value]);
    }

    public function recharge(): static
    {
        return $this->state(['type_transaction' => TypeTransactionPayDunyaEnum::RECHARGE_PORTEFEUILLE->value]);
    }

    public function achatBillet(): static
    {
        return $this->state(['type_transaction' => TypeTransactionPayDunyaEnum::ACHAT_BILLET->value]);
    }
}

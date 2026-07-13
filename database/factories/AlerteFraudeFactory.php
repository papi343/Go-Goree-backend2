<?php

namespace Database\Factories;

use App\Enums\NiveauAlerteFraudeEnum;
use App\Enums\StatutAlerteFraudeEnum;
use App\Models\AlerteFraude;
use App\Models\Payement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlerteFraude>
 */
class AlerteFraudeFactory extends Factory
{
    protected $model = AlerteFraude::class;

    public function definition(): array
    {
        return [
            'payement_id' => Payement::factory(),
            'niveau' => NiveauAlerteFraudeEnum::SUSPECT->value,
            'regle_declenchee' => 'velocite_transactions',
            'payload_suspect' => ['tentatives' => 6, 'fenetre_minutes' => 10],
            'traite_par' => null,
            'statut' => StatutAlerteFraudeEnum::EN_ATTENTE->value,
        ];
    }

    public function critique(): static
    {
        return $this->state(['niveau' => NiveauAlerteFraudeEnum::CRITIQUE->value]);
    }
}

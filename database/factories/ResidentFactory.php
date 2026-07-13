<?php

namespace Database\Factories;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resident>
 */
class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    public function definition(): array
    {
        return [
            'active' => true,
            'user_id' => User::factory()->resident(),
        ];
    }
}

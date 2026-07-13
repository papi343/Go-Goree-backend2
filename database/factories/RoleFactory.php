<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->randomElement(RoleEnum::cases())->value,
        ];
    }

    public function admin(): static
    {
        return $this->state(['nom' => RoleEnum::ADMIN->value]);
    }

    public function agent(): static
    {
        return $this->state(['nom' => RoleEnum::AGENT->value]);
    }

    public function client(): static
    {
        return $this->state(['nom' => RoleEnum::CLIENT->value]);
    }
}

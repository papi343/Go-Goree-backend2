<?php

namespace Database\Factories;

use App\Enums\CanalEnum;
use App\Enums\NotificationEnum;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(NotificationEnum::cases())->value,
            'canal' => fake()->randomElement(CanalEnum::cases())->value,
            'lu_a' => null,
            'user_id' => User::factory(),
        ];
    }

    public function lue(): static
    {
        return $this->state(['lu_a' => now()]);
    }
}

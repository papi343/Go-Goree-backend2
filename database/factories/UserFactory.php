<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Mot de passe en clair par défaut, utile dans les tests d'authentification.
     */
    public const MOT_DE_PASSE = 'password';

    public function definition(): array
    {
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->numerify('7########'),
            'mot_de_passe' => Hash::make(self::MOT_DE_PASSE),
            'active' => true,
            'est_resident' => false,
            'role_id' => null,
        ];
    }

    /**
     * Associe l'utilisateur à un rôle donné (créé une seule fois par nom).
     */
    public function role(RoleEnum $role): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['nom' => $role->value])->id,
        ]);
    }

    public function admin(): static
    {
        return $this->role(RoleEnum::ADMIN);
    }

    public function agent(): static
    {
        return $this->role(RoleEnum::AGENT);
    }

    public function client(): static
    {
        return $this->role(RoleEnum::CLIENT);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function resident(): static
    {
        return $this->state(['est_resident' => true]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\CategorieEnum;
use App\Models\Tarif;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarif>
 */
class TarifFactory extends Factory
{
    protected $model = Tarif::class;

    public function definition(): array
    {
        return [
            'categorie' => fake()->randomElement(CategorieEnum::cases())->value,
            'prix' => fake()->randomElement([500, 1000, 1500, 2500]),
        ];
    }

    public function categorie(CategorieEnum $categorie, ?float $prix = null): static
    {
        return $this->state(array_filter([
            'categorie' => $categorie->value,
            'prix' => $prix,
        ], fn ($v) => $v !== null));
    }

    public function resident(float $prix = 500): static
    {
        return $this->categorie(CategorieEnum::RESIDENT, $prix);
    }

    public function adulte(float $prix = 1500): static
    {
        return $this->categorie(CategorieEnum::ADULTE, $prix);
    }

    public function etranger(float $prix = 2500): static
    {
        return $this->categorie(CategorieEnum::ETRANGER, $prix);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ResultatScanEnum;
use App\Models\Billet;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
    protected $model = Scan::class;

    public function definition(): array
    {
        return [
            'resultat' => ResultatScanEnum::VALIDE->value,
            'billet_id' => Billet::factory(),
        ];
    }
}

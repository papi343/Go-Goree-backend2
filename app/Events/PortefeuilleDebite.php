<?php

namespace App\Events;

use App\Models\Portefeuille;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PortefeuilleDebite
{
    use Dispatchable, SerializesModels;

    public function __construct(public Portefeuille $portefeuille, public float $montant)
    {
    }
}

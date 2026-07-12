<?php

namespace App\Events;

use App\Models\Payement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaiementRefuse
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payement $payement)
    {
    }
}

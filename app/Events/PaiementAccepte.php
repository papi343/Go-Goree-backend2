<?php

namespace App\Events;

use App\Models\Payement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaiementAccepte
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payement $payement)
    {
    }
}

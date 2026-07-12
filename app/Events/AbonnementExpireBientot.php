<?php

namespace App\Events;

use App\Models\Abonnement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AbonnementExpireBientot
{
    use Dispatchable, SerializesModels;

    public function __construct(public Abonnement $abonnement)
    {
    }
}

<?php

namespace App\Events;

use App\Models\DemandeResidence;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DemandeResidenceAcceptee
{
    use Dispatchable, SerializesModels;

    public function __construct(public DemandeResidence $demande)
    {
    }
}

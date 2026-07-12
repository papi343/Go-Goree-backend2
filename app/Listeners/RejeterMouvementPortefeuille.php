<?php

namespace App\Listeners;

use App\Events\PaiementRefuse;
use Illuminate\Support\Facades\Log;

class RejeterMouvementPortefeuille
{
    /**
     * Handle the event.
     */
    public function handle(PaiementRefuse $event): void
    {
        Log::info("RejeterMouvementPortefeuille triggered for event " . get_class($event));
    }
}
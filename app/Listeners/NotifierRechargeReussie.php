<?php

namespace App\Listeners;

use App\Events\PortefeuilleRecharge;
use Illuminate\Support\Facades\Log;

class NotifierRechargeReussie
{
    /**
     * Handle the event.
     */
    public function handle(PortefeuilleRecharge $event): void
    {
        Log::info("NotifierRechargeReussie triggered for event " . get_class($event));
    }
}
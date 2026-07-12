<?php

namespace App\Listeners;

use App\Events\PortefeuilleDebite;
use Illuminate\Support\Facades\Log;

class NotifierDebitPortefeuille
{
    /**
     * Handle the event.
     */
    public function handle(PortefeuilleDebite $event): void
    {
        Log::info("NotifierDebitPortefeuille triggered for event " . get_class($event));
    }
}
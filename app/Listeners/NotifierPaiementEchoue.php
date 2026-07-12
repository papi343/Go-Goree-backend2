<?php

namespace App\Listeners;

use App\Events\PaiementRefuse;
use Illuminate\Support\Facades\Log;

class NotifierPaiementEchoue
{
    /**
     * Handle the event.
     */
    public function handle(PaiementRefuse $event): void
    {
        Log::info("NotifierPaiementEchoue triggered for event " . get_class($event));
    }
}
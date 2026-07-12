<?php

namespace App\Listeners;

use App\Events\PaiementAccepte;
use Illuminate\Support\Facades\Log;

class NotifierPaiementReussi
{
    /**
     * Handle the event.
     */
    public function handle(PaiementAccepte $event): void
    {
        Log::info("NotifierPaiementReussi triggered for event " . get_class($event));
    }
}
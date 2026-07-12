<?php

namespace App\Listeners;

use App\Events\PaiementAccepte;
use Illuminate\Support\Facades\Log;

class CrediterPortefeuille
{
    /**
     * Handle the event.
     */
    public function handle(PaiementAccepte $event): void
    {
        Log::info("CrediterPortefeuille triggered for event " . get_class($event));
    }
}
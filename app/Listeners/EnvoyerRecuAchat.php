<?php

namespace App\Listeners;

use App\Events\BilletAchete;
use Illuminate\Support\Facades\Log;

class EnvoyerRecuAchat
{
    /**
     * Handle the event.
     */
    public function handle(BilletAchete $event): void
    {
        Log::info("EnvoyerRecuAchat triggered for event " . get_class($event));
    }
}
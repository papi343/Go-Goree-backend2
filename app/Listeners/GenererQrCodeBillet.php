<?php

namespace App\Listeners;

use App\Events\BilletAchete;
use Illuminate\Support\Facades\Log;

class GenererQrCodeBillet
{
    /**
     * Handle the event.
     */
    public function handle(BilletAchete $event): void
    {
        Log::info("GenererQrCodeBillet triggered for event " . get_class($event));
    }
}
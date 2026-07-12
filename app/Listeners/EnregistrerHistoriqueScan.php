<?php

namespace App\Listeners;

use App\Events\BilletScanne;
use Illuminate\Support\Facades\Log;

class EnregistrerHistoriqueScan
{
    /**
     * Handle the event.
     */
    public function handle(BilletScanne $event): void
    {
        Log::info("EnregistrerHistoriqueScan triggered for event " . get_class($event));
    }
}
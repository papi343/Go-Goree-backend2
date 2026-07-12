<?php

namespace App\Listeners;

use App\Events\BilletScanne;
use Illuminate\Support\Facades\Log;

class NotifierEmbarquement
{
    /**
     * Handle the event.
     */
    public function handle(BilletScanne $event): void
    {
        Log::info("NotifierEmbarquement triggered for event " . get_class($event));
    }
}
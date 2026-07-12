<?php

namespace App\Listeners;

use App\Events\FraudeDetectee;
use Illuminate\Support\Facades\Log;

class AlerterAdminFraude
{
    /**
     * Handle the event.
     */
    public function handle(FraudeDetectee $event): void
    {
        Log::info("AlerterAdminFraude triggered for event " . get_class($event));
    }
}
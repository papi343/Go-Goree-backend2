<?php

namespace App\Listeners;

use App\Events\FraudeDetectee;
use Illuminate\Support\Facades\Log;

class BloquerTransactionSuspecte
{
    /**
     * Handle the event.
     */
    public function handle(FraudeDetectee $event): void
    {
        Log::info("BloquerTransactionSuspecte triggered for event " . get_class($event));
    }
}
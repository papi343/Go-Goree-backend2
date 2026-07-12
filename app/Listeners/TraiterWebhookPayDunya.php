<?php

namespace App\Listeners;

use App\Events\PaiementWebhookRecu;
use Illuminate\Support\Facades\Log;

class TraiterWebhookPayDunya
{
    /**
     * Handle the event.
     */
    public function handle(PaiementWebhookRecu $event): void
    {
        Log::info("TraiterWebhookPayDunya triggered for event " . get_class($event));
    }
}
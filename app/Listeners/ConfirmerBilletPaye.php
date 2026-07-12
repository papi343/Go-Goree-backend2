<?php

namespace App\Listeners;

use App\Events\PaiementAccepte;
use Illuminate\Support\Facades\Log;

class ConfirmerBilletPaye
{
    /**
     * Handle the event.
     */
    public function handle(PaiementAccepte $event): void
    {
        Log::info("ConfirmerBilletPaye triggered for event " . get_class($event));
    }
}
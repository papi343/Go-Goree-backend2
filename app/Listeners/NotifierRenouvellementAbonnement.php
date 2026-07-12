<?php

namespace App\Listeners;

use App\Events\AbonnementExpireBientot;
use Illuminate\Support\Facades\Log;

class NotifierRenouvellementAbonnement
{
    /**
     * Handle the event.
     */
    public function handle(AbonnementExpireBientot $event): void
    {
        Log::info("NotifierRenouvellementAbonnement triggered for event " . get_class($event));
    }
}
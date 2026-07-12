<?php

namespace App\Listeners;

use App\Events\DemandeResidenceAcceptee;
use Illuminate\Support\Facades\Log;

class ActiverResidentEtAbonnement
{
    /**
     * Handle the event.
     */
    public function handle(DemandeResidenceAcceptee $event): void
    {
        Log::info("ActiverResidentEtAbonnement triggered for event " . get_class($event));
    }
}
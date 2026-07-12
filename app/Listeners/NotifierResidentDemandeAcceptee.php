<?php

namespace App\Listeners;

use App\Events\DemandeResidenceAcceptee;
use Illuminate\Support\Facades\Log;

class NotifierResidentDemandeAcceptee
{
    /**
     * Handle the event.
     */
    public function handle(DemandeResidenceAcceptee $event): void
    {
        Log::info("NotifierResidentDemandeAcceptee triggered for event " . get_class($event));
    }
}
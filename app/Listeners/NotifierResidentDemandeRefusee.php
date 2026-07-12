<?php

namespace App\Listeners;

use App\Events\DemandeResidenceRefusee;
use Illuminate\Support\Facades\Log;

class NotifierResidentDemandeRefusee
{
    /**
     * Handle the event.
     */
    public function handle(DemandeResidenceRefusee $event): void
    {
        Log::info("NotifierResidentDemandeRefusee triggered for event " . get_class($event));
    }
}
<?php

namespace App\Listeners;

use App\Events\PaiementInitie;
use Illuminate\Support\Facades\Log;

class EnregistrerTentativePaiement
{
    /**
     * Handle the event.
     */
    public function handle(PaiementInitie $event): void
    {
        Log::info("EnregistrerTentativePaiement triggered for event " . get_class($event));
    }
}
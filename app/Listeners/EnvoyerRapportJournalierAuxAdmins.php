<?php

namespace App\Listeners;

use App\Events\RapportJournalierGenere;
use Illuminate\Support\Facades\Log;

class EnvoyerRapportJournalierAuxAdmins
{
    /**
     * Handle the event.
     */
    public function handle(RapportJournalierGenere $event): void
    {
        Log::info("EnvoyerRapportJournalierAuxAdmins triggered for event " . get_class($event));
    }
}
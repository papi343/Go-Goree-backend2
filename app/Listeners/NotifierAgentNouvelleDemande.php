<?php

namespace App\Listeners;

use App\Events\DemandeResidenceSoumise;

class NotifierAgentNouvelleDemande
{
    public function handle(DemandeResidenceSoumise $event): void
    {
        // Log or dispatch notification to agent
    }
}

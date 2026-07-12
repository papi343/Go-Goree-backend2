<?php

namespace App\Events;

use App\Models\Billet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BilletAchete
{
    use Dispatchable, SerializesModels;

    public function __construct(public Billet $billet)
    {
    }
}

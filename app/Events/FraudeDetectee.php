<?php

namespace App\Events;

use App\Models\AlerteFraude;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FraudeDetectee
{
    use Dispatchable, SerializesModels;

    public function __construct(public AlerteFraude $alerte)
    {
    }
}

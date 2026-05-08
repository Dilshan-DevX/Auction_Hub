<?php

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Bid $bid
    ) {}
}

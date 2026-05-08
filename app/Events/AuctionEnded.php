<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionEnded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Auction $auction
    ) {}
}

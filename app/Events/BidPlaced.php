<?php

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C.1: Dispatched after a bid is successfully placed.
 * Triggers: BroadcastNewBid job + NotifyPreviousHighBidder listener.
 */
class BidPlaced
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Bid $bid
    ) {}
}

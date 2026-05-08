<?php

namespace App\Listeners;

use App\Events\BidPlaced;
use App\Jobs\BroadcastNewBid;

/**
 * C.1: Listens to BidPlaced — dispatches BroadcastNewBid job to the 'bids' queue.
 * Auto-discovered via Laravel 11's event discovery (handle() type-hint).
 */
class QueueBidBroadcast
{
    /**
     * Handle the event.
     */
    public function handle(BidPlaced $event): void
    {
        BroadcastNewBid::dispatch($event->bid);
    }
}

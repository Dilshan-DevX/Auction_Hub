<?php

namespace App\Listeners;

use App\Events\BidPlaced;
use App\Models\Bid;
use App\Notifications\OutbidNotification;

/**
 * C.1: Listens to BidPlaced — notifies the previous high bidder via database notification.
 * Auto-discovered via Laravel 11's event discovery (handle() type-hint).
 */
class NotifyPreviousHighBidder
{
    /**
     * Handle the event.
     */
    public function handle(BidPlaced $event): void
    {
        $newBid = $event->bid;

        // Find the previous highest bid on this auction (excluding the new bid)
        $previousHighBid = Bid::where('auction_id', $newBid->auction_id)
            ->where('id', '!=', $newBid->id)
            ->orderByDesc('amount')
            ->first();

        // Only notify if there was a previous bidder AND it's a different user
        if ($previousHighBid && $previousHighBid->user_id !== $newBid->user_id) {
            $previousHighBid->user->notify(
                new OutbidNotification($newBid)
            );
        }
    }
}

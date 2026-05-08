<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;

/**
 * B.3: BidPolicy with place ability.
 * Registered in AuthServiceProvider and invoked via Gate::authorize() in BidController.
 */
class BidPolicy
{
    /**
     * Determine if the user can place a bid on the given auction.
     * - Must be a bidder role
     * - Must NOT be the vendor of the auction (self-bidding fraud)
     * - Auction must be live
     */
    public function place(User $user, Bid $bid, Auction $auction): bool
    {
        // Must be a bidder
        if ($user->role !== 'bidder') {
            return false;
        }

        // Self-bidding fraud prevention: bidder must not be the vendor
        $auction->loadMissing('vendor');
        if ($auction->vendor && $auction->vendor->user_id === $user->id) {
            return false;
        }

        // Auction must be live
        if ($auction->status !== 'live') {
            return false;
        }

        return true;
    }
}

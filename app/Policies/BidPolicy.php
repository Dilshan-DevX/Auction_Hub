<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;

class BidPolicy
{
    public function place(User $user, Bid $bid, Auction $auction): bool
    {
        if ($user->role !== 'bidder') {
            return false;
        }

        $auction->loadMissing('vendor');
        if ($auction->vendor && $auction->vendor->user_id === $user->id) {
            return false;
        }

        if ($auction->status !== 'live') {
            return false;
        }

        return true;
    }
}

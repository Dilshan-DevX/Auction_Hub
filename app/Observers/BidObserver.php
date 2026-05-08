<?php

namespace App\Observers;

use App\Models\Bid;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BidObserver
{
    /**
     * Handle the Bid "creating" event.
     *
     * Validates inside a DB transaction with row-level locking:
     * 1. Auction is currently live (re-checked with lockForUpdate)
     * 2. Bid amount >= current_price + bid_increment
     * 3. Bidder's deposit_balance >= 10% of bid amount (deducted atomically)
     * 4. Bidder is NOT the vendor of the auction (self-bidding fraud prevention)
     *
     * On success, updates auctions.current_price in the same transaction.
     * Returns false to cancel creation on any validation failure.
     */
    public function creating(Bid $bid): bool
    {
        return DB::transaction(function () use ($bid) {

            // 1. Re-check auction is live with a row-level lock to prevent race conditions
            $auction = Auction::lockForUpdate()->find($bid->auction_id);

            if (! $auction) {
                return false;
            }

            // Check the auction is currently live (status + time window)
            if (
                $auction->status !== 'live' ||
                now()->lt($auction->starts_at) ||
                now()->gt($auction->ends_at)
            ) {
                return false;
            }

            // 2. Bid amount must be >= current_price + bid_increment
            // Work with raw decimal values from DB to avoid cast complications in observer
            $currentPrice = (float) $auction->getRawOriginal('current_price');
            $bidIncrement = (float) $auction->getRawOriginal('bid_increment');
            $bidAmount    = (float) ($bid->getAttributes()['amount'] ?? $bid->getRawOriginal('amount'));

            $minimumBid = $currentPrice + $bidIncrement;

            if ($bidAmount < $minimumBid) {
                return false;
            }

            // 3. Bidder deposit_balance >= 10% of bid amount; deduct atomically
            $requiredHold = $bidAmount * 0.10;

            $bidder = User::lockForUpdate()->find($bid->user_id);

            if (! $bidder) {
                return false;
            }

            $deposBalance = (float) $bidder->getRawOriginal('deposit_balance');

            if ($deposBalance < $requiredHold) {
                return false;
            }

            // Atomic deduction of the hold from deposit_balance
            $bidder->newQuery()
                ->where('id', $bidder->id)
                ->update([
                    'deposit_balance' => DB::raw("deposit_balance - {$requiredHold}"),
                ]);

            // 4. Bidder must NOT be the vendor of the auction (self-bidding fraud)
            if ($auction->vendor && $auction->vendor->user_id === $bid->user_id) {
                return false;
            }

            // 5. Update auction's current_price to the bid amount
            $auction->newQuery()
                ->where('id', $auction->id)
                ->update([
                    'current_price' => $bidAmount,
                ]);

            // Set placed_at if not already set
            if (! $bid->placed_at) {
                $bid->placed_at = now();
            }

            return true;
        });
    }
}

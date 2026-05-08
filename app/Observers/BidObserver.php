<?php

namespace App\Observers;

use App\Models\Bid;
use App\Models\Auction;
use App\Models\User;
use App\Events\BidPlaced;
use Illuminate\Support\Facades\DB;

class BidObserver
{
    public function creating(Bid $bid): bool
    {
        return DB::transaction(function () use ($bid) {

            $auction = Auction::lockForUpdate()->find($bid->auction_id);

            if (! $auction) {
                return false;
            }

            if (
                $auction->status !== 'live' ||
                now()->lt($auction->starts_at) ||
                now()->gt($auction->ends_at)
            ) {
                return false;
            }

            $currentPrice = (float) $auction->getRawOriginal('current_price');
            $bidIncrement = (float) $auction->getRawOriginal('bid_increment');
            $bidAmount    = (float) ($bid->getAttributes()['amount'] ?? $bid->getRawOriginal('amount'));

            $minimumBid = $currentPrice + $bidIncrement;

            if ($bidAmount < $minimumBid) {
                return false;
            }

            $requiredHold = $bidAmount * 0.10;

            $bidder = User::lockForUpdate()->find($bid->user_id);

            if (! $bidder) {
                return false;
            }

            $deposBalance = (float) $bidder->getRawOriginal('deposit_balance');

            if ($deposBalance < $requiredHold) {
                return false;
            }

            $bidder->newQuery()
                ->where('id', $bidder->id)
                ->update([
                    'deposit_balance' => DB::raw("deposit_balance - {$requiredHold}"),
                ]);

            if ($auction->vendor && $auction->vendor->user_id === $bid->user_id) {
                return false;
            }

            $auction->newQuery()
                ->where('id', $auction->id)
                ->update([
                    'current_price' => $bidAmount,
                ]);

            if (! $bid->placed_at) {
                $bid->placed_at = now();
            }

            return true;
        });
    }

    public function created(Bid $bid): void
    {
        BidPlaced::dispatch($bid);
    }
}

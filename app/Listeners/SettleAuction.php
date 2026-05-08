<?php

namespace App\Listeners;

use App\Events\AuctionEnded;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;

/**
 * C.1: Listens to AuctionEnded — settles the auction in a DB transaction:
 * 1. Transfer deposit hold to vendor minus commission
 * 2. Release all other bidders' holds
 * 3. Mark auction as settled (settled_at timestamp)
 *
 * Auto-discovered via Laravel 11's event discovery (handle() type-hint).
 */
class SettleAuction
{
    /**
     * Handle the event.
     */
    public function handle(AuctionEnded $event): void
    {
        $auction = $event->auction;

        // Skip if already settled
        if ($auction->settled_at !== null) {
            return;
        }

        DB::transaction(function () use ($auction) {
            // Lock the auction row
            $auction = $auction->newQuery()->lockForUpdate()->find($auction->id);

            if (! $auction || $auction->settled_at !== null) {
                return;
            }

            // Get the winning bid (highest amount)
            $winningBid = Bid::where('auction_id', $auction->id)
                ->orderByDesc('amount')
                ->first();

            // Load vendor with commission rate
            $vendor = $auction->vendor;
            $commissionRate = (float) $vendor->getRawOriginal('commission_rate');

            if ($winningBid) {
                $winningAmount = (float) $winningBid->getRawOriginal('amount');
                $holdAmount    = $winningAmount * 0.10;
                $commission    = $winningAmount * $commissionRate;
                $vendorPayout  = $holdAmount - $commission;

                // 1. Transfer hold to vendor's user minus commission
                //    (Vendor payout = hold amount - commission)
                if ($vendorPayout > 0) {
                    $vendor->user->newQuery()
                        ->where('id', $vendor->user_id)
                        ->update([
                            'deposit_balance' => DB::raw("deposit_balance + {$vendorPayout}"),
                        ]);
                }

                // 2. Release holds from ALL other bidders (non-winners)
                $otherBids = Bid::where('auction_id', $auction->id)
                    ->where('id', '!=', $winningBid->id)
                    ->get();

                foreach ($otherBids as $bid) {
                    $refundAmount = (float) $bid->getRawOriginal('amount') * 0.10;

                    $bid->user->newQuery()
                        ->where('id', $bid->user_id)
                        ->update([
                            'deposit_balance' => DB::raw("deposit_balance + {$refundAmount}"),
                        ]);
                }
            } else {
                // No bids — nothing to settle
            }

            // 3. Mark auction as settled
            $auction->newQuery()
                ->where('id', $auction->id)
                ->update([
                    'status'     => 'ended',
                    'settled_at' => now(),
                ]);
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBidRequest;
use App\Http\Resources\BidResource;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\QueryException;

class BidController extends Controller
{
    /**
     * POST /api/auctions/{auction}/bids
     * Authenticated bidders. Returns:
     *   409 on race-condition loss
     *   422 on invalid amount
     *   403 on self-bid
     *   201 on success
     */
    public function store(StoreBidRequest $request, Auction $auction)
    {
        // B.3: Authorise via Gate (BidPolicy::place)
        Gate::authorize('place', [new Bid(), $auction]);

        $bid = new Bid();
        $bid->user_id    = $request->user()->id;
        $bid->auction_id = $auction->id;
        $bid->amount     = $request->validated('amount');
        $bid->placed_at  = now();

        try {
            $saved = $bid->save();
        } catch (QueryException $e) {
            // Unique constraint violation → race condition (duplicate user+auction+amount)
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Race condition: another identical bid was placed first.',
                ], 409);
            }
            throw $e;
        }

        // BidObserver::creating() returns false if validation fails
        if (! $saved) {
            // Determine the specific reason for failure
            $freshAuction = Auction::find($auction->id);

            if (! $freshAuction || $freshAuction->status !== 'live') {
                return response()->json([
                    'message' => 'This auction is not currently live.',
                ], 422);
            }

            $minimumBid = (float) $freshAuction->getRawOriginal('current_price')
                        + (float) $freshAuction->getRawOriginal('bid_increment');

            if ((float) $request->amount < $minimumBid) {
                return response()->json([
                    'message' => "Bid amount must be at least {$minimumBid}.",
                    'minimum_next_bid' => $minimumBid,
                ], 422);
            }

            // Fallback: generic failure (deposit insufficient, etc.)
            return response()->json([
                'message' => 'Bid could not be placed. Check your deposit balance.',
            ], 422);
        }

        return new BidResource($bid->load(['user', 'auction']));
    }
}

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
    public function store(StoreBidRequest $request, Auction $auction)
    {
        Gate::authorize('place', [new Bid(), $auction]);

        $bid = new Bid();
        $bid->user_id    = $request->user()->id;
        $bid->auction_id = $auction->id;
        $bid->amount     = $request->validated('amount');
        $bid->placed_at  = now();

        try {
            $saved = $bid->save();
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Race condition: another identical bid was placed first.',
                ], 409);
            }
            throw $e;
        }

        if (! $saved) {
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

            return response()->json([
                'message' => 'Bid could not be placed. Check your deposit balance.',
            ], 422);
        }

        return new BidResource($bid->load(['user', 'auction']));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WatchlistController extends Controller
{
    /**
     * GET /api/me/watchlist
     * Returns authenticated user's watchlist with latest bid on each item.
     * ONE query using subquery selects — no N+1.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $auctions = $user->watchlistedAuctions()
            ->addSelect([
                'latest_bid_amount' => DB::table('bids')
                    ->selectRaw('MAX(amount)')
                    ->whereColumn('bids.auction_id', 'auctions.id'),
                'latest_bid_at' => DB::table('bids')
                    ->selectRaw('MAX(placed_at)')
                    ->whereColumn('bids.auction_id', 'auctions.id'),
            ])
            ->with(['vendor.user', 'category'])
            ->paginate(15);

        return AuctionResource::collection($auctions);
    }
}

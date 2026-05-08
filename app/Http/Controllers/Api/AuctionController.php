<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuctionRequest;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Filters\AuctionFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pipeline\Pipeline;

class AuctionController extends Controller
{
    /**
     * GET /api/auctions
     * Paginated, filterable via dedicated Filter class (pipeline pattern).
     */
    public function index(Request $request)
    {
        $query = Auction::query()->with(['vendor.user', 'category']);

        // Pipeline pattern filtering
        $filtered = app(Pipeline::class)
            ->send($query)
            ->through([
                AuctionFilter::class,
            ])
            ->thenReturn();

        $auctions = $filtered->paginate($request->input('per_page', 15));

        return AuctionResource::collection($auctions);
    }

    /**
     * GET /api/auctions/{id}
     * Eager-loads vendor, category, top 5 bids, and attachments in ≤ 3 queries.
     * Cache per auction for 30 seconds with tag-based invalidation.
     */
    public function show(int $id)
    {
        $auction = Cache::remember(
            "auction:{$id}",
            30,
            function () use ($id) {
                return Auction::with([
                    'vendor.user',
                    'category',
                    'bids' => fn($q) => $q->orderByDesc('amount')->limit(5),
                    'attachments',
                ])->findOrFail($id);
            }
        );

        return new AuctionResource($auction);
    }

    /**
     * POST /api/auctions
     * Vendor-only. Validates via StoreAuctionRequest.
     */
    public function store(StoreAuctionRequest $request)
    {
        Gate::authorize('create', Auction::class);

        $vendor = $request->user()->vendor;

        $auction = $vendor->auctions()->create($request->validated());

        // Invalidate auction cache
        Cache::forget("auction:{$auction->id}");

        return new AuctionResource($auction->load(['vendor.user', 'category']));
    }

    /**
     * DELETE /api/auctions/{id}
     * Soft delete. Authorised via AuctionPolicy::delete.
     * Cannot delete if bids exist.
     */
    public function destroy(int $id)
    {
        $auction = Auction::findOrFail($id);

        Gate::authorize('delete', $auction);

        // Cannot delete if bids exist
        if ($auction->bids()->exists()) {
            return response()->json([
                'message' => 'Cannot delete an auction that has bids.',
            ], 409);
        }

        $auction->delete();

        // Invalidate cache
        Cache::forget("auction:{$id}");

        return response()->json(null, 204);
    }
}

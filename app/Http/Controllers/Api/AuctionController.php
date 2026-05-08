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
    public function index(Request $request)
    {
        $query = Auction::query()->with(['vendor.user', 'category']);


        $filtered = app(Pipeline::class)
            ->send($query)
            ->through([
                AuctionFilter::class,
            ])
            ->thenReturn();

        $auctions = $filtered->paginate($request->input('per_page', 15));

        return AuctionResource::collection($auctions);
    }

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

    public function store(StoreAuctionRequest $request)
    {
        Gate::authorize('create', Auction::class);

        $vendor = $request->user()->vendor;

        $auction = $vendor->auctions()->create($request->validated());


        Cache::forget("auction:{$auction->id}");

        return new AuctionResource($auction->load(['vendor.user', 'category']));
    }

    public function destroy(int $id)
    {
        $auction = Auction::findOrFail($id);

        Gate::authorize('delete', $auction);


        if ($auction->bids()->exists()) {
            return response()->json([
                'message' => 'Cannot delete an auction that has bids.',
            ], 409);
        }

        $auction->delete();


        Cache::forget("auction:{$id}");

        return response()->json(null, 204);
    }
}

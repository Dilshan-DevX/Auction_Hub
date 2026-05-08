<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuctionResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API-style array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => 'auctions',
            'attributes'    => [
                'status'           => $this->status,
                'is_live'          => (bool) $this->is_live,
                'starts_at'        => $this->starts_at?->toIso8601String(),
                'ends_at'          => $this->ends_at?->toIso8601String(),
                'reserve_price'    => $this->getRawOriginal('reserve_price'),
                'current_price'    => $this->getRawOriginal('current_price'),
                'bid_increment'    => $this->getRawOriginal('bid_increment'),
                'minimum_next_bid' => $this->when(
                    $this->relationLoaded('bids') || $this->current_price,
                    fn () => $this->getRawOriginal('current_price') + $this->getRawOriginal('bid_increment')
                ),
                'created_at'       => $this->created_at?->toIso8601String(),
                'updated_at'       => $this->updated_at?->toIso8601String(),
                // Watchlist subquery fields (when available)
                'latest_bid_amount' => $this->when(isset($this->latest_bid_amount), $this->latest_bid_amount),
                'latest_bid_at'     => $this->when(isset($this->latest_bid_at), $this->latest_bid_at),
            ],
            'relationships' => [
                'vendor'   => $this->when($this->relationLoaded('vendor'), fn () => [
                    'id'         => $this->vendor->id,
                    'store_slug' => $this->vendor->store_slug,
                    'user'       => $this->when($this->vendor->relationLoaded('user'), fn () => [
                        'id'   => $this->vendor->user->id,
                        'name' => $this->vendor->user->name,
                    ]),
                ]),
                'category' => $this->when($this->relationLoaded('category'), fn () => [
                    'id'   => $this->category->id,
                    'name' => $this->category->name,
                ]),
                'bids'     => $this->when($this->relationLoaded('bids'), fn () =>
                    BidResource::collection($this->bids)
                ),
                'attachments' => $this->when($this->relationLoaded('attachments'), fn () =>
                    $this->attachments->map(fn ($a) => [
                        'id'        => $a->id,
                        'file_path' => $a->file_path,
                    ])
                ),
            ],
            'meta' => [
                'notify_at_close' => $this->whenPivotLoaded('watchlists', fn () =>
                    (bool) $this->pivot->notify_at_close
                ),
            ],
        ];
    }
}

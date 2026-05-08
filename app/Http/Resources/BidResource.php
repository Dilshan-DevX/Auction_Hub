<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BidResource extends JsonResource
{
    /**
     * Transform the resource into a JSON:API-style array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => 'bids',
            'attributes' => [
                'amount'    => $this->getRawOriginal('amount'),
                'placed_at' => $this->placed_at?->toIso8601String(),
                'created_at' => $this->created_at?->toIso8601String(),
            ],
            'relationships' => [
                'user' => $this->when($this->relationLoaded('user'), fn () => [
                    'id'   => $this->user->id,
                    'name' => $this->user->name,
                ]),
                'auction' => $this->when($this->relationLoaded('auction'), fn () => [
                    'id' => $this->auction->id,
                ]),
            ],
        ];
    }
}

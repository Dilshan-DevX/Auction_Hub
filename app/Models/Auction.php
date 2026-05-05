<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use SoftDeletes;

    // 1. Cast the columns using the custom cast
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reserve_price' => MoneyCast::class,
            'current_price' => MoneyCast::class,
            'bid_increment' => MoneyCast::class,
        ];
    }

    // 2. Base Relationships
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function bids() { return $this->hasMany(Bid::class); }

    // 3. scopeLive()
    public function scopeLive(Builder $query): void
    {
        $query->where('status', 'live')
              ->where('starts_at', '<=', now())
              ->where('ends_at', '>=', now());
    }

    // 4. scopeEndingSoon()
    public function scopeEndingSoon(Builder $query, int $minutes = 10): void
    {
        // Re-use the live scope, then add the ending soon constraint
        $query->live()->where('ends_at', '<=', now()->addMinutes($minutes));
    }

    // 5. minimumNextBid Accessor (Laravel 11 syntax)
    protected function minimumNextBid(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                // Since current_price and bid_increment are cast to Money objects, 
                // we sum their cents and return a new Money object.
                $totalCents = $this->current_price->cents + $this->bid_increment->cents;
                return new Money($totalCents);
            }
        );
    }
}
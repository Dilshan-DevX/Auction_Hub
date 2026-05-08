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

    protected $fillable = [
        'vendor_id', 'category_id', 'starts_at', 'ends_at',
        'reserve_price', 'current_price', 'bid_increment', 'status',
    ];

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

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function bids() { return $this->hasMany(Bid::class); }

    public function scopeLive(Builder $query): void
    {
        $query->where('status', 'live')
              ->where('starts_at', '<=', now())
              ->where('ends_at', '>=', now());
    }

    public function scopeEndingSoon(Builder $query, int $minutes = 10): void
    {      
        $query->live()->where('ends_at', '<=', now()->addMinutes($minutes));
    }

    protected function minimumNextBid(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {           
                $totalCents = $this->current_price->cents + $this->bid_increment->cents;
                return new Money($totalCents);
            }
        );
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'watchlists')
                    ->withPivot('notify_at_close');
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
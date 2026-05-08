<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Bid extends Model
{
    protected $fillable = [
        'user_id', 'auction_id', 'amount', 'placed_at',
    ];
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'placed_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function auction() { return $this->belongsTo(Auction::class); }

    public function scopeWinning(Builder $query): void
    {
        $query->where('amount', function ($subquery) {
            $subquery->selectRaw('MAX(amount)')
                     ->from('bids as b2')
                     ->whereColumn('b2.auction_id', 'bids.auction_id');
        });
    }
}
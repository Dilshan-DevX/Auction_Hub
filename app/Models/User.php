<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vendor() { return $this->hasOne(Vendor::class); }
    public function bids() { return $this->hasMany(Bid::class); }

    public function scopeWithActiveBidCount(Builder $query): void
    {
        $query->withCount(['bids as active_bids_count' => function (Builder $bidsQuery) {
            $bidsQuery->whereHas('auction', function (Builder $auctionQuery) {
                $auctionQuery->live();
            });
        }]);
    }

    public function watchlistedAuctions()
    {
        return $this->belongsToMany(Auction::class, 'watchlists')
                    ->withPivot('notify_at_close');
    }
}

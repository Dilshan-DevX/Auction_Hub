<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

/**
 * B.3: AuctionPolicy with abilities: view, create, update, delete, manageBids.
 * Gate::before is used for super-admins (handled in AppServiceProvider).
 */
class AuctionPolicy
{
    /**
     * Any user can view auctions.
     */
    public function view(?User $user, Auction $auction): bool
    {
        return true;
    }

    /**
     * Only vendors with an approved vendor profile can create auctions.
     */
    public function create(User $user): bool
    {
        return $user->role === 'vendor'
            && $user->vendor
            && $user->vendor->approved_at !== null;
    }

    /**
     * Only the vendor who owns the auction can update it.
     */
    public function update(User $user, Auction $auction): bool
    {
        return $user->role === 'vendor'
            && $user->vendor
            && $auction->vendor_id === $user->vendor->id;
    }

    /**
     * Vendor owner OR admin can delete.
     * Cannot delete if bids exist (checked in controller).
     */
    public function delete(User $user, Auction $auction): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'vendor'
            && $user->vendor
            && $auction->vendor_id === $user->vendor->id;
    }

    /**
     * Only the vendor who owns the auction can manage its bids.
     */
    public function manageBids(User $user, Auction $auction): bool
    {
        return $user->role === 'vendor'
            && $user->vendor
            && $auction->vendor_id === $user->vendor->id;
    }
}

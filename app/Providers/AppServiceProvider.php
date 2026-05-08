<?php

namespace App\Providers;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use App\Observers\BidObserver;
use App\Policies\AuctionPolicy;
use App\Policies\BidPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the BidObserver
        Bid::observe(BidObserver::class);

        // Register policies
        Gate::policy(Auction::class, AuctionPolicy::class);
        Gate::policy(Bid::class, BidPolicy::class);

        // B.3: Gate::before for super-admins — admins bypass all policy checks
        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'admin') {
                return true;
            }

            return null; // fall through to specific policy
        });

        // B.4: Rate limiting — max 30 bids per minute per user, keyed by user ID
        RateLimiter::for('bids', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many bids. Please try again later.',
                    ], 429, $headers);
                });
        });
    }
}

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
    public function register(): void
    {
        $this->app->bind(\App\Contracts\PaymentGatewayContract::class, function ($app) {
            $driver = config('services.gateway.driver');
            
            return match ($driver) {
                'stripe' => new \App\Services\StripeGateway(),
                default => new \App\Services\MockGateway(),
            };
        });

        $this->app->when(\App\Http\Controllers\Admin\RefundController::class)
            ->needs(\App\Contracts\PaymentGatewayContract::class)
            ->give(\App\Services\MockGateway::class);
    }

    public function boot(): void
    {

        Bid::observe(BidObserver::class);


        Gate::policy(Auction::class, AuctionPolicy::class);
        Gate::policy(Bid::class, BidPolicy::class);


        Gate::before(function (User $user, string $ability) {
            if ($user->role === 'admin') {
                return true;
            }

            return null;
        });


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

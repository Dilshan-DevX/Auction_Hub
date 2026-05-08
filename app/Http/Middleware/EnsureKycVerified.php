<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * B.3: Middleware that blocks bidders without kyc_verified_at from the bid endpoint.
 * Registered as route middleware alias 'kyc'.
 */
class EnsureKycVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->kyc_verified_at === null) {
            return response()->json([
                'message' => 'KYC verification is required before placing bids.',
            ], 403);
        }

        return $next($request);
    }
}

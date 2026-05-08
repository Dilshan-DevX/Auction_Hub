<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKycVerified
{
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

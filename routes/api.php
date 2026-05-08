<?php

use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| B.1 & B.2: All API endpoints for AuctionHub
|
*/

// B.1: Authentication (public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

// B.2: Public auction endpoints
Route::get('/auctions', [AuctionController::class, 'index']);
Route::get('/auctions/{id}', [AuctionController::class, 'show']);

// B.2: Protected auction endpoints (auth required)
Route::middleware('auth:sanctum')->group(function () {

    // Vendor-only: create auctions
    Route::post('/auctions', [AuctionController::class, 'store']);

    // Vendor/Admin: delete auctions (policy enforced)
    Route::delete('/auctions/{id}', [AuctionController::class, 'destroy']);

    // B.2: Bidding endpoint — requires auth + KYC + rate limiting
    Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])
        ->middleware(['kyc', 'throttle:bids']);

    // B.2: Watchlist
    Route::get('/me/watchlist', [WatchlistController::class, 'index']);
});

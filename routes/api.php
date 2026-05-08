<?php

use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;




Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');


Route::get('/auctions', [AuctionController::class, 'index']);
Route::get('/auctions/{id}', [AuctionController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {


    Route::post('/auctions', [AuctionController::class, 'store']);

    Route::delete('/auctions/{id}', [AuctionController::class, 'destroy']);

    Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])
        ->middleware(['kyc', 'throttle:bids']);

    Route::get('/me/watchlist', [WatchlistController::class, 'index']);
});

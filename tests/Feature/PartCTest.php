<?php

use App\Events\AuctionEnded;
use App\Events\BidPlaced;
use App\Jobs\BroadcastNewBid;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\OutbidNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function seedTestCData(): array
{
    $vendorUser = User::create([
        'name' => 'Vendor', 'email' => 'vendor@test.com',
        'password' => Hash::make('password'), 'role' => 'vendor',
        'kyc_verified_at' => now(), 'deposit_balance' => 0,
    ]);

    $bidder1 = User::create([
        'name' => 'Bidder 1', 'email' => 'bidder1@test.com',
        'password' => Hash::make('password'), 'role' => 'bidder',
        'kyc_verified_at' => now(), 'deposit_balance' => 1000.00,
    ]);

    $bidder2 = User::create([
        'name' => 'Bidder 2', 'email' => 'bidder2@test.com',
        'password' => Hash::make('password'), 'role' => 'bidder',
        'kyc_verified_at' => now(), 'deposit_balance' => 1000.00,
    ]);

    $vendor = Vendor::create([
        'user_id' => $vendorUser->id, 'store_slug' => 'test-store',
        'commission_rate' => 0.1000, // 10% commission
        'approved_at' => now(),
    ]);

    $category = Category::create(['name' => 'Test Category']);

    $auction = Auction::create([
        'vendor_id' => $vendor->id, 'category_id' => $category->id,
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
        'reserve_price' => 100.00, 'current_price' => 100.00,
        'bid_increment' => 10.00, 'status' => 'live',
    ]);

    return compact('vendorUser', 'bidder1', 'bidder2', 'vendor', 'auction');
}

test('C.1: BidPlaced event dispatches BroadcastNewBid job', function () {
    $data = seedTestCData();
    Queue::fake();

    $bid = Bid::create([
        'user_id' => $data['bidder1']->id,
        'auction_id' => $data['auction']->id,
        'amount' => 120.00,
    ]);

    Queue::assertPushed(BroadcastNewBid::class, function ($job) use ($bid) {
        return $job->bid->id === $bid->id && $job->queue === 'bids';
    });
});

test('C.1: BidPlaced event notifies previous high bidder', function () {
    $data = seedTestCData();
    Notification::fake();

    // First bid
    $bid1 = Bid::create([
        'user_id' => $data['bidder1']->id,
        'auction_id' => $data['auction']->id,
        'amount' => 120.00,
    ]);

    // Second bid (higher)
    $bid2 = Bid::create([
        'user_id' => $data['bidder2']->id,
        'auction_id' => $data['auction']->id,
        'amount' => 140.00,
    ]);

    Notification::assertSentTo(
        $data['bidder1'],
        OutbidNotification::class,
        function ($notification) use ($bid2) {
            return $notification->newBid->id === $bid2->id;
        }
    );
});

test('C.1: AuctionEnded settles auction correctly', function () {
    $data = seedTestCData();

    $bid1 = Bid::create([
        'user_id' => $data['bidder1']->id,
        'auction_id' => $data['auction']->id,
        'amount' => 200.00,
    ]);

    
    $bid2 = Bid::create([
        'user_id' => $data['bidder2']->id,
        'auction_id' => $data['auction']->id,
        'amount' => 250.00,
    ]);

    $data['bidder1']->refresh();
    $data['bidder2']->refresh();

    expect((float)$data['bidder1']->getRawOriginal('deposit_balance'))->toBe(980.00);
    expect((float)$data['bidder2']->getRawOriginal('deposit_balance'))->toBe(975.00); 

    
    $data['vendor']->update(['commission_rate' => 0.05]); 
    
   
    $data['auction']->update(['status' => 'ended']);
    AuctionEnded::dispatch($data['auction']);

    $data['vendorUser']->refresh();
    $data['bidder1']->refresh();
    $data['bidder2']->refresh();
    $data['auction']->refresh();

    
    expect((float)$data['bidder1']->getRawOriginal('deposit_balance'))->toBe(1000.00);

    expect((float)$data['vendorUser']->getRawOriginal('deposit_balance'))->toBe(12.50);
    
    expect($data['auction']->settled_at)->not->toBeNull();
});

test('C.1: SettleAuction handles auction with no bids', function () {
    $data = seedTestCData();


    $data['auction']->update(['status' => 'ended']);
    AuctionEnded::dispatch($data['auction']);

    $data['auction']->refresh();
    expect($data['auction']->settled_at)->not->toBeNull();
  
    expect((float)$data['vendorUser']->getRawOriginal('deposit_balance'))->toBe(0.0);
});

test('C.2: auction:close-live command marks live auctions as ended', function () {
    $data = seedTestCData();

    $data['auction']->update(['ends_at' => now()->subMinute()]);

    $this->artisan('auction:close-live')
        ->expectsOutput("Closed auction ID: {$data['auction']->id}")
        ->assertExitCode(0);

    $data['auction']->refresh();
    expect($data['auction']->status)->toBe('ended');
});

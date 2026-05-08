<?php

namespace Tests\Feature;

use App\Events\BidPlaced;
use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConcurrentBiddingTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendorUser;
    protected Vendor $vendor;
    protected Auction $auction;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'kyc_verified_at' => now(),
        ]);

        $this->vendor = Vendor::create([
            'user_id' => $this->vendorUser->id,
            'store_slug' => 'test-store',
            'commission_rate' => 0.05,
            'approved_at' => now(),
        ]);

        $this->category = Category::create(['name' => 'Test Category']);

        $this->auction = Auction::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'reserve_price' => 100,
            'current_price' => 100,
            'bid_increment' => 10,
            'status' => 'live',
        ]);
    }

    public function test_parallel_identical_bids_only_one_wins(): void
    {
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'role' => 'bidder',
            'kyc_verified_at' => now(),
            'deposit_balance' => 1000,
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'role' => 'bidder',
            'kyc_verified_at' => now(),
            'deposit_balance' => 1000,
        ]);

        $response1 = $this->actingAs($user1)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 110,
        ]);
        $response1->assertStatus(201);

        $response2 = $this->actingAs($user2)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 110,
        ]);
        $response2->assertStatus(422);

        $this->assertEquals(1, $this->auction->bids()->count());
        $this->assertEquals(110, $this->auction->fresh()->getRawOriginal('current_price'));
    }

    public function test_bid_below_minimum_returns_422(): void
    {
        $user = User::create([
            'name' => 'Bidder',
            'email' => 'bidder@example.com',
            'password' => bcrypt('password'),
            'role' => 'bidder',
            'kyc_verified_at' => now(),
            'deposit_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 105, // Min is 110
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_self_bid_returns_403(): void
    {
        $response = $this->actingAs($this->vendorUser)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 150,
        ]);

        $response->assertStatus(403);
    }

    public function test_winning_bid_dispatches_event_once(): void
    {
        Event::fake([BidPlaced::class]);

        $user = User::create([
            'name' => 'Bidder',
            'email' => 'bidder@example.com',
            'password' => bcrypt('password'),
            'role' => 'bidder',
            'kyc_verified_at' => now(),
            'deposit_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 110,
        ]);
        
        $response->assertStatus(201);

        Event::assertDispatched(BidPlaced::class, 1);
    }

    public function test_non_kyc_user_rejected_with_403(): void
    {
        $user = User::create([
            'name' => 'Non-KYC Bidder',
            'email' => 'nokyc@example.com',
            'password' => bcrypt('password'),
            'role' => 'bidder',
            'kyc_verified_at' => null,
            'deposit_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson("/api/auctions/{$this->auction->id}/bids", [
            'amount' => 110,
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'KYC verification is required before placing bids.']);
    }
}

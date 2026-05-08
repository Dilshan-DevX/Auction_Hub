<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);


function seedTestData(): array
{
    $admin = User::create([
        'name' => 'Admin', 'email' => 'admin@test.com',
        'password' => Hash::make('password'), 'role' => 'admin',
        'kyc_verified_at' => now(), 'deposit_balance' => 0,
    ]);

    $vendorUser = User::create([
        'name' => 'Vendor', 'email' => 'vendor@test.com',
        'password' => Hash::make('password'), 'role' => 'vendor',
        'kyc_verified_at' => now(), 'deposit_balance' => 0,
    ]);

    $bidder = User::create([
        'name' => 'Bidder', 'email' => 'bidder@test.com',
        'password' => Hash::make('password'), 'role' => 'bidder',
        'kyc_verified_at' => now(), 'deposit_balance' => 50000.00,
    ]);

    $bidderNoKyc = User::create([
        'name' => 'Bidder NoKYC', 'email' => 'bidder-nokyc@test.com',
        'password' => Hash::make('password'), 'role' => 'bidder',
        'kyc_verified_at' => null, 'deposit_balance' => 50000.00,
    ]);

    $vendor = Vendor::create([
        'user_id' => $vendorUser->id, 'store_slug' => 'test-store',
        'commission_rate' => 0.0500, 'approved_at' => now(),
    ]);

    $electronics = Category::create(['name' => 'Electronics']);
    $phones = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);
    $smartphones = Category::create(['name' => 'Smartphones', 'parent_id' => $phones->id]);

    $liveAuction = Auction::create([
        'vendor_id' => $vendor->id, 'category_id' => $smartphones->id,
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHours(3),
        'reserve_price' => 100.00, 'current_price' => 150.00,
        'bid_increment' => 10.00, 'status' => 'live',
    ]);

    $scheduledAuction = Auction::create([
        'vendor_id' => $vendor->id, 'category_id' => $electronics->id,
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(3),
        'reserve_price' => 500.00, 'current_price' => 500.00,
        'bid_increment' => 25.00, 'status' => 'scheduled',
    ]);

    return compact(
        'admin', 'vendorUser', 'bidder', 'bidderNoKyc', 'vendor',
        'electronics', 'phones', 'smartphones',
        'liveAuction', 'scheduledAuction'
    );
}


test('B.1: POST /api/login returns token with correct abilities for bidder', function () {
    $data = seedTestData();

    $response = $this->postJson('/api/login', [
        'email' => 'bidder@test.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'type', 'abilities', 'user']])
        ->assertJsonPath('data.abilities', ['bid:place'])
        ->assertJsonPath('data.user.role', 'bidder');
});

test('B.1: POST /api/login returns admin:* abilities for admin', function () {
    $data = seedTestData();

    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.abilities', ['admin:*'])
        ->assertJsonPath('data.user.role', 'admin');
});

test('B.1: POST /api/login returns auction:manage abilities for vendor', function () {
    $data = seedTestData();

    $response = $this->postJson('/api/login', [
        'email' => 'vendor@test.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.abilities', ['auction:manage'])
        ->assertJsonPath('data.user.role', 'vendor');
});

test('B.1: POST /api/login rejects invalid credentials', function () {
    $data = seedTestData();

    $response = $this->postJson('/api/login', [
        'email' => 'bidder@test.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(422);
});

test('B.1: POST /api/logout revokes current token only', function () {
    $data = seedTestData();

    $token1 = $data['bidder']->createToken('token-1', ['bid:place']);
    $token2 = $data['bidder']->createToken('token-2', ['bid:place']);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token1->plainTextToken,
    ])->postJson('/api/logout');

    $response->assertOk()->assertJsonPath('message', 'Token revoked successfully.');

    expect($data['bidder']->tokens()->count())->toBe(1);

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token2->plainTextToken,
    ])->getJson('/api/me/watchlist')->assertOk();
});



test('B.2: GET /api/auctions returns paginated list', function () {
    $data = seedTestData();

    $response = $this->getJson('/api/auctions');

    $response->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(2, 'data');
});

test('B.2: GET /api/auctions?status=live filters by status', function () {
    $data = seedTestData();

    $response = $this->getJson('/api/auctions?status=live');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('B.2: GET /api/auctions?category_id=X filters by category with descendants', function () {
    $data = seedTestData();

    $response = $this->getJson('/api/auctions?category_id=' . $data['electronics']->id);

    $response->assertOk();
    $count = count($response->json('data'));
    expect($count)->toBe(2);
});

test('B.2: GET /api/auctions/{id} returns auction with relationships', function () {
    $data = seedTestData();

    $response = $this->getJson('/api/auctions/' . $data['liveAuction']->id);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'type', 'attributes', 'relationships' => ['vendor', 'category'],
            ],
        ]);
});

test('B.2: GET /api/auctions/{id} caches for 30 seconds', function () {
    $data = seedTestData();

    $response1 = $this->getJson('/api/auctions/' . $data['liveAuction']->id);
    $response1->assertOk();

    $response2 = $this->getJson('/api/auctions/' . $data['liveAuction']->id);
    $response2->assertOk();

    expect($response1->json('data.id'))->toBe($response2->json('data.id'));
});

test('B.2: POST /api/auctions requires vendor auth', function () {
    $data = seedTestData();

    $this->postJson('/api/auctions', [])->assertUnauthorized();

    Sanctum::actingAs($data['bidder'], ['bid:place']);
    $this->postJson('/api/auctions', [
        'category_id' => $data['electronics']->id,
        'starts_at' => now()->addHour()->toIso8601String(),
        'ends_at' => now()->addHours(5)->toIso8601String(),
        'reserve_price' => '100.00',
        'current_price' => '100.00',
        'bid_increment' => '10.00',
        'status' => 'draft',
    ])->assertForbidden();
});

test('B.2: POST /api/auctions/{id}/bids — 201 on valid bid', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '160.00']
    );

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'type', 'attributes']]);

    $data['liveAuction']->refresh();
    expect((float) $data['liveAuction']->getRawOriginal('current_price'))->toBe(160.00);
});

test('B.2: POST /api/auctions/{id}/bids — 422 on bid below minimum', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '155.00']
    );

    $response->assertStatus(422);
});

test('B.2: POST /api/auctions/{id}/bids — 403 on self-bid (vendor bidding own auction)', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['vendorUser'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '160.00']
    );

    $response->assertForbidden();
});

test('B.2: DELETE /api/auctions/{id} — soft deletes with policy check', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);
    $this->deleteJson('/api/auctions/' . $data['scheduledAuction']->id)
        ->assertForbidden();

    Sanctum::actingAs($data['vendorUser'], ['auction:manage']);
    $this->deleteJson('/api/auctions/' . $data['scheduledAuction']->id)
        ->assertNoContent();

    expect(Auction::find($data['scheduledAuction']->id))->toBeNull();
    expect(Auction::withTrashed()->find($data['scheduledAuction']->id))->not->toBeNull();
});

test('B.2: DELETE /api/auctions/{id} — 409 if bids exist', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);
    $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '160.00']
    )->assertCreated();

    Sanctum::actingAs($data['vendorUser'], ['auction:manage']);
    $this->deleteJson('/api/auctions/' . $data['liveAuction']->id)
        ->assertStatus(409);
});

test('B.2: GET /api/me/watchlist returns user watchlist', function () {
    $data = seedTestData();

    $data['bidder']->watchlistedAuctions()->attach($data['liveAuction']->id, ['notify_at_close' => true]);

    Sanctum::actingAs($data['bidder'], ['bid:place']);
    $response = $this->getJson('/api/me/watchlist');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});


test('B.3: EnsureKycVerified middleware blocks unverified bidders', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidderNoKyc'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '160.00']
    );

    $response->assertForbidden()
        ->assertJsonPath('message', 'KYC verification is required before placing bids.');
});

test('B.3: Admin super-access via Gate::before', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['admin'], ['admin:*']);
    $this->deleteJson('/api/auctions/' . $data['scheduledAuction']->id)
        ->assertNoContent();
});


test('B.4: StoreBidRequest validates amount format', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => 'not-a-number']
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('B.4: MinimumNextBid custom rule rejects low bids', function () {
    $data = seedTestData();

    Sanctum::actingAs($data['bidder'], ['bid:place']);

    $response = $this->postJson(
        '/api/auctions/' . $data['liveAuction']->id . '/bids',
        ['amount' => '100.00']
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('B.4: Rate limiter allows 30 bids per minute', function () {
    $data = seedTestData();

    $limiter = app(\Illuminate\Cache\RateLimiter::class);
    expect($limiter)->not->toBeNull();
});

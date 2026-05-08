<?php

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with test data for all Part B endpoints.
     */
    public function run(): void
    {
        // --- Users ---
        $admin = User::create([
            'name'             => 'Admin User',
            'email'            => 'admin@auctionhub.com',
            'password'         => Hash::make('password'),
            'role'             => 'admin',
            'kyc_verified_at'  => now(),
            'deposit_balance'  => 0,
        ]);

        $vendorUser = User::create([
            'name'             => 'Vendor User',
            'email'            => 'vendor@auctionhub.com',
            'password'         => Hash::make('password'),
            'role'             => 'vendor',
            'kyc_verified_at'  => now(),
            'deposit_balance'  => 0,
        ]);

        $bidderUser = User::create([
            'name'             => 'Bidder User',
            'email'            => 'bidder@auctionhub.com',
            'password'         => Hash::make('password'),
            'role'             => 'bidder',
            'kyc_verified_at'  => now(),
            'deposit_balance'  => 50000.00,
        ]);

        $bidderNoKyc = User::create([
            'name'             => 'Bidder No KYC',
            'email'            => 'bidder-nokyc@auctionhub.com',
            'password'         => Hash::make('password'),
            'role'             => 'bidder',
            'kyc_verified_at'  => null,
            'deposit_balance'  => 50000.00,
        ]);

        $vendor = Vendor::create([
            'user_id'         => $vendorUser->id,
            'store_slug'      => 'premium-auctions',
            'commission_rate'  => 0.0500,
            'approved_at'     => now(),
        ]);

        $electronics = Category::create(['name' => 'Electronics']);
        $phones      = Category::create(['name' => 'Phones', 'parent_id' => $electronics->id]);
        $smartphones = Category::create(['name' => 'Smartphones', 'parent_id' => $phones->id]);
        $laptops     = Category::create(['name' => 'Laptops', 'parent_id' => $electronics->id]);
        $art         = Category::create(['name' => 'Art']);

    
        Auction::insert([
            [
                'vendor_id'     => $vendor->id,
                'category_id'   => $smartphones->id,
                'starts_at'     => now()->subHour(),
                'ends_at'       => now()->addHours(3),
                'reserve_price' => 100.00,
                'current_price' => 150.00,
                'bid_increment' => 10.00,
                'status'        => 'live',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'vendor_id'     => $vendor->id,
                'category_id'   => $laptops->id,
                'starts_at'     => now()->subHour(),
                'ends_at'       => now()->addMinutes(5),
                'reserve_price' => 500.00,
                'current_price' => 700.00,
                'bid_increment' => 25.00,
                'status'        => 'live',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'vendor_id'     => $vendor->id,
                'category_id'   => $art->id,
                'starts_at'     => now()->addDay(),
                'ends_at'       => now()->addDays(3),
                'reserve_price' => 1000.00,
                'current_price' => 1000.00,
                'bid_increment' => 50.00,
                'status'        => 'scheduled',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);

        $this->command->info('Seeded: admin, vendor, bidder, bidder-no-kyc, 5 categories, 3 auctions');
    }
}

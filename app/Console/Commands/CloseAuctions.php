<?php

namespace App\Console\Commands;

use App\Events\AuctionEnded;
use App\Models\Auction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloseAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and close expired live auctions.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hasFailures = false;

        Auction::where('status', 'live')
            ->where('ends_at', '<', now())
            ->chunkById(100, function ($auctions) use (&$hasFailures) {
                foreach ($auctions as $auction) {
                    try {
                        AuctionEnded::dispatch($auction);
                    } catch (Throwable $e) {
                        $hasFailures = true;
                        Log::error("Failed to close auction {$auction->id}", [
                            'auction_id' => $auction->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        return $hasFailures ? 1 : 0;
    }
}

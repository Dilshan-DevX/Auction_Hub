<?php

namespace App\Console\Commands;

use App\Events\AuctionEnded;
use App\Models\Auction;
use Illuminate\Console\Command;

/**
 * C.2: Finds all auctions where status='live' AND ends_at <= now().
 * Updates status to 'ended' and dispatches AuctionEnded event.
 */
class CloseLiveAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auction:close-live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close auctions that have reached their end time';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $auctions = Auction::where('status', 'live')
            ->where('ends_at', '<=', now())
            ->get();

        if ($auctions->isEmpty()) {
            $this->info('No auctions to close.');
            return;
        }

        foreach ($auctions as $auction) {
            $auction->update(['status' => 'ended']);
            AuctionEnded::dispatch($auction);
            $this->info("Closed auction ID: {$auction->id}");
        }

        $this->info('Finished closing auctions.');
    }
}

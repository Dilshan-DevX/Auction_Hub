<?php

namespace App\Jobs;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * C.1: Job pushed to the 'bids' queue when a BidPlaced event fires.
 * Implements ShouldQueue with 3 retries and exponential backoff.
 */
class BroadcastNewBid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public readonly Bid $bid
    ) {
        $this->onQueue('bids');
    }

    /**
     * Exponential backoff intervals (in seconds).
     * Retry 1: 2s, Retry 2: 10s, Retry 3: 30s.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [2, 10, 30];
    }

    /**
     * Execute the job — broadcast the new bid to connected clients.
     */
    public function handle(): void
    {
        // In production, this would broadcast via WebSockets (Pusher/Reverb).
        // For now, log the broadcast event for verification.
        Log::info('BroadcastNewBid: Broadcasting bid', [
            'bid_id'     => $this->bid->id,
            'auction_id' => $this->bid->auction_id,
            'amount'     => $this->bid->getRawOriginal('amount'),
            'user_id'    => $this->bid->user_id,
        ]);
    }
}

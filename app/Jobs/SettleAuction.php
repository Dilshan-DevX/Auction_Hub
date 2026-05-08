<?php

namespace App\Jobs;

use App\Contracts\PaymentGatewayContract;
use App\Models\Auction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SettleAuction implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Auction $auction,
        protected readonly PaymentGatewayContract $gateway
    ) {}

    public function handle(): void
    {

    }
}

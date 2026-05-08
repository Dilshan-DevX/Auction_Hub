<?php

namespace App\Notifications;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * C.1: Database notification sent to the previous high bidder when they've been outbid.
 */
class OutbidNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Bid $newBid
    ) {}

    /**
     * Deliver via database channel.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message'    => 'You have been outbid!',
            'auction_id' => $this->newBid->auction_id,
            'new_amount' => $this->newBid->getRawOriginal('amount'),
            'bid_id'     => $this->newBid->id,
        ];
    }
}

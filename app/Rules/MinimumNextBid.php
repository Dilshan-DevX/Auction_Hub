<?php

namespace App\Rules;

use App\Models\Auction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinimumNextBid implements ValidationRule
{
    public function __construct(
        protected Auction $auction
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currentPrice = (float) $this->auction->getRawOriginal('current_price');
        $bidIncrement = (float) $this->auction->getRawOriginal('bid_increment');
        $minimumBid   = $currentPrice + $bidIncrement;

        if ((float) $value < $minimumBid) {
            $fail("The :attribute must be at least {$minimumBid}. (current price: {$currentPrice} + increment: {$bidIncrement})");
        }
    }
}

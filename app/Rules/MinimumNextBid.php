<?php

namespace App\Rules;

use App\Models\Auction;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * B.4: Custom validation rule implementing Laravel 11 ValidationRule contract.
 * Accepts the auction instance via constructor DI.
 * Validates that the bid amount >= current_price + bid_increment.
 */
class MinimumNextBid implements ValidationRule
{
    public function __construct(
        protected Auction $auction
    ) {}

    /**
     * Run the validation rule.
     */
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

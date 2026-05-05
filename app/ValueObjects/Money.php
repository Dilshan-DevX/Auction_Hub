<?php

namespace App\ValueObjects;

class Money
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency = 'USD'
    ) {}

    // Helper to get the decimal format for the DB
    public function toDecimal(): float
    {
        return $this->cents / 100;
    }
}
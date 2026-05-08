<?php

namespace App\Contracts;

use App\ValueObjects\Money;

interface PaymentGatewayContract
{
    public function authorise(Money $money);
    public function capture(string $ref);
    public function refund(string $ref);
}

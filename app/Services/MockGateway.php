<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\ValueObjects\Money;

class MockGateway implements PaymentGatewayContract
{
    public function authorise(Money $money)
    {
        return 'mock_ref_' . uniqid();
    }

    public function capture(string $ref)
    {

    }

    public function refund(string $ref)
    {

    }
}

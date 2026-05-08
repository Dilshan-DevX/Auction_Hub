<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\ValueObjects\Money;

class StripeGateway implements PaymentGatewayContract
{
    public function authorise(Money $money)
    {

        return 'stripe_ref_' . uniqid();
    }

    public function capture(string $ref)
    {

    }

    public function refund(string $ref)
    {

    }
}

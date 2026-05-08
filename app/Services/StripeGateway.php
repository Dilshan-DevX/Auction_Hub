<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\ValueObjects\Money;

class StripeGateway implements PaymentGatewayContract
{
    public function authorise(Money $money)
    {
        // Stripe authorization logic
        return 'stripe_ref_' . uniqid();
    }

    public function capture(string $ref)
    {
        // Stripe capture logic
    }

    public function refund(string $ref)
    {
        // Stripe refund logic
    }
}

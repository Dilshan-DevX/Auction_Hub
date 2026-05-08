<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PaymentGatewayContract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        protected readonly PaymentGatewayContract $gateway
    ) {}

    public function refund(Request $request, string $ref)
    {
        $this->gateway->refund($ref);

        return response()->json([
            'message' => 'Refund processed successfully using ' . get_class($this->gateway),
        ]);
    }
}

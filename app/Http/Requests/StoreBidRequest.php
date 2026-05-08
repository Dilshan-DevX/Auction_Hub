<?php

namespace App\Http\Requests;

use App\Models\Auction;
use App\Rules\MinimumNextBid;
use Illuminate\Foundation\Http\FormRequest;

class StoreBidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'bidder';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $auction = $this->route('auction');

        return [
            'amount' => [
                'required',
                'decimal:0,2',
                new MinimumNextBid($auction),
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'amount.required'   => 'A bid amount is required.',
            'amount.decimal'    => 'Bid amount must have at most 2 decimal places.',
        ];
    }
}

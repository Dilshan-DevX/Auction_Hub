<?php

namespace App\Http\Requests;

use App\Rules\NotInBlackoutWindow;
use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'vendor';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id'   => ['required', 'exists:categories,id'],
            'starts_at'     => ['required', 'date', 'after:now', new NotInBlackoutWindow($this->user())],
            'ends_at'       => ['required', 'date', 'after:starts_at'],
            'reserve_price' => ['required', 'decimal:0,2', 'min:0.01'],
            'current_price' => ['required', 'decimal:0,2', 'min:0'],
            'bid_increment' => ['required', 'decimal:0,2', 'min:0.01'],
            'status'        => ['required', 'in:draft,scheduled,live,ended,cancelled'],
        ];
    }
}

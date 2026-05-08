<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * B.2: Custom rule that rejects starts_at during vendor's blackout windows.
 * The exam says "another table you may omit — mock the rule".
 * This is the mock implementation.
 */
class NotInBlackoutWindow implements ValidationRule
{
    public function __construct(
        protected ?User $vendor = null
    ) {}

    /**
     * Run the validation rule.
     * In a real implementation, this would query a blackout_windows table.
     * For exam purposes, this is a mocked validation that always passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Mock implementation — in production, query blackout_windows table:
        // $hasBlackout = DB::table('blackout_windows')
        //     ->where('vendor_id', $this->vendor?->vendor?->id)
        //     ->where('start', '<=', $value)
        //     ->where('end', '>=', $value)
        //     ->exists();
        //
        // if ($hasBlackout) {
        //     $fail('The :attribute falls within a blackout window.');
        // }

        // Currently passes all dates (mocked per exam instructions)
    }
}

<?php

namespace App\Filters;

use App\Models\Category;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Dedicated Filter class using the Pipeline pattern.
 * Applies all auction filters in a single pass — no if-statements in the controller.
 */
class AuctionFilter
{
    public function __construct(protected Request $request) {}

    /**
     * Handle the pipeline — apply all applicable filters.
     */
    public function handle(Builder $query, Closure $next): Builder
    {
        // Filter by category (including descendants via recursive CTE)
        if ($this->request->filled('category_id')) {
            $categoryId = (int) $this->request->input('category_id');

            // Include the category itself + all descendants
            $descendantIds = Category::query()->descendants($categoryId)->pluck('id');
            $allIds = $descendantIds->prepend($categoryId)->unique();

            $query->whereIn('category_id', $allIds);
        }

        // Filter by status
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->input('status'));
        }

        // Filter by price range (min)
        if ($this->request->filled('price_min')) {
            $query->where('current_price', '>=', $this->request->input('price_min'));
        }

        // Filter by price range (max)
        if ($this->request->filled('price_max')) {
            $query->where('current_price', '<=', $this->request->input('price_max'));
        }

        // Filter by vendor slug
        if ($this->request->filled('vendor_slug')) {
            $query->whereHas('vendor', function (Builder $q) {
                $q->where('store_slug', $this->request->input('vendor_slug'));
            });
        }

        return $next($query);
    }
}

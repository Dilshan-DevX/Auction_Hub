<?php

namespace App\Filters;

use App\Models\Category;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuctionFilter
{
    public function __construct(protected Request $request) {}

    public function handle(Builder $query, Closure $next): Builder
    {

        if ($this->request->filled('category_id')) {
            $categoryId = (int) $this->request->input('category_id');


            $descendantIds = Category::query()->descendants($categoryId)->pluck('id');
            $allIds = $descendantIds->prepend($categoryId)->unique();

            $query->whereIn('category_id', $allIds);
        }


        if ($this->request->filled('status')) {
            $query->where('status', $this->request->input('status'));
        }


        if ($this->request->filled('price_min')) {
            $query->where('current_price', '>=', $this->request->input('price_min'));
        }


        if ($this->request->filled('price_max')) {
            $query->where('current_price', '<=', $this->request->input('price_max'));
        }


        if ($this->request->filled('vendor_slug')) {
            $query->whereHas('vendor', function (Builder $q) {
                $q->where('store_slug', $this->request->input('vendor_slug'));
            });
        }

        return $next($query);
    }
}

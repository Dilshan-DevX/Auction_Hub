<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id'];
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

 
    public function scopeDescendants(Builder $query, int $parentId): void
    {
        $query->whereRaw('id IN (
            WITH RECURSIVE cte AS (
                SELECT id FROM categories WHERE parent_id = ?
                UNION ALL
                SELECT c.id FROM categories c INNER JOIN cte ON c.parent_id = cte.id
            )
            SELECT id FROM cte
        )', [$parentId]);
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }
}

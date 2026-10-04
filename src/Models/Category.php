<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'ecom_categories';

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image', 'banner', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Ids of every category below this one (children, grandchildren, ...).
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $parentIds = [$this->id];

        while ($parentIds) {
            $parentIds = self::whereIn('parent_id', $parentIds)->pluck('id')->all();
            $ids = array_merge($ids, $parentIds);
        }

        return $ids;
    }

    /**
     * All categories in tree order with a "depth" attribute, for nested selects and lists.
     */
    public static function tree(?int $exceptId = null): Collection
    {
        $all = self::withCount('products')->orderBy('sort_order')->orderBy('name')->get();
        $except = $exceptId ? $all->firstWhere('id', $exceptId) : null;
        $excluded = $except ? array_merge([$except->id], $except->descendantIds()) : [];
        $grouped = $all->reject(fn ($category) => in_array($category->id, $excluded))->groupBy('parent_id');
        $result = new Collection;

        $walk = function ($parentId, int $depth) use (&$walk, $grouped, $result): void {
            foreach ($grouped->get($parentId ?? '', []) as $category) {
                $category->depth = $depth;
                $result->push($category);
                $walk($category->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $result;
    }
}

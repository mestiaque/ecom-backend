<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $table = 'ecom_products';

    protected $fillable = [
        'category_id', 'brand_id', 'warranty_id', 'title', 'slug', 'sku', 'short_description', 'description',
        'price', 'discount_price', 'cost_price', 'stock', 'low_stock_threshold', 'has_variants',
        'is_active', 'is_featured', 'weight', 'free_delivery', 'delivery_charge_adjustment',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'free_delivery' => 'boolean',
        'delivery_charge_adjustment' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'ecom_campaign_product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Products at or below their low-stock level (own threshold, else the shop default).
     */
    public function scopeLowStock(Builder $query): Builder
    {
        $default = (int) ecom_setting('low_stock_threshold', config('ecom.low_stock_threshold'));

        return $query->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [$default]);
    }

    /**
     * ?search= (title / SKU), ?category=, ?brand=, ?status=active|inactive, ?stock=low|out
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($filters['category'] ?? null, function ($q, $categoryId) {
                $category = Category::find($categoryId);
                $q->whereIn('category_id', $category ? array_merge([$category->id], $category->descendantIds()) : [0]);
            })
            ->when($filters['brand'] ?? null, fn ($q, $brand) => $q->where('brand_id', $brand))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock()->where('stock', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn ($q) => $q->where('stock', '<=', 0));
    }

    public function getThumbnailAttribute(): ?string
    {
        return $this->primaryImage?->thumb_url;
    }

    public function getLowStockLevelAttribute(): int
    {
        return $this->low_stock_threshold ?? (int) ecom_setting('low_stock_threshold', config('ecom.low_stock_threshold'));
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_level;
    }

    /**
     * Price before campaign discounts (discount price if set).
     */
    public function getSellingPriceAttribute(): float
    {
        return (float) ($this->discount_price ?? $this->price);
    }

    /**
     * Running campaign for this product, if any.
     */
    public function activeCampaign(): ?Campaign
    {
        return $this->campaigns->first(fn (Campaign $campaign) => $campaign->isRunning());
    }

    /**
     * Final price after a running flash sale / campaign.
     */
    public function finalPrice(?ProductVariant $variant = null): float
    {
        $price = $variant ? $variant->selling_price : $this->selling_price;
        $campaign = $this->activeCampaign();

        return $campaign ? $campaign->apply($price) : $price;
    }

    /**
     * Products with variants keep the total variant stock in "stock" (used by lists and low-stock alerts).
     */
    public function syncStockFromVariants(): void
    {
        if ($this->has_variants) {
            $this->forceFill(['stock' => (int) $this->variants()->sum('stock')])->saveQuietly();
        }
    }

    /**
     * Change stock of the product (and variant). Negative = take out.
     */
    public function adjustStock(int $quantity, ?int $variantId = null): void
    {
        DB::transaction(function () use ($quantity, $variantId) {
            if ($variantId && ($variant = $this->variants()->find($variantId))) {
                $variant->increment('stock', $quantity);
                $this->syncStockFromVariants();

                return;
            }

            $this->increment('stock', $quantity);
        });
    }
}

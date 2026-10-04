<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class ProductVariant extends Model
{
    protected $table = 'ecom_product_variants';

    protected $fillable = ['product_id', 'product_image_id', 'sku', 'price', 'discount_price', 'stock', 'is_active'];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }

    /**
     * The attribute values of this variant (Color: Red, Size: M, ...).
     */
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'ecom_product_variant_values', 'variant_id', 'attribute_value_id')
            ->with('attribute');
    }

    /**
     * Values in attribute order (Color before Size, as set on the Attributes page).
     *
     * @return Collection<int, AttributeValue>
     */
    public function orderedValues(): Collection
    {
        return $this->values->sortBy(fn (AttributeValue $value) => [$value->attribute->sort_order, $value->attribute->id])->values();
    }

    /**
     * "Color: Red / Size: M"
     */
    public function getLabelAttribute(): string
    {
        return $this->orderedValues()
            ->map(fn (AttributeValue $value) => "{$value->attribute->name}: {$value->value}")
            ->implode(' / ');
    }

    /**
     * Sorted value ids — identifies the combination (used to stop duplicate variants).
     */
    public function getCombinationKeyAttribute(): string
    {
        return $this->values->pluck('id')->sort()->implode('-');
    }

    public function getRegularPriceAttribute(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    /**
     * Selling price before campaign discounts.
     */
    public function getSellingPriceAttribute(): float
    {
        if ($this->discount_price !== null) {
            return (float) $this->discount_price;
        }

        return $this->price !== null ? (float) $this->price : $this->product->selling_price;
    }
}

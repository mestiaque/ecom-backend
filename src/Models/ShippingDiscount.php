<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery discount by order amount: "orders of at least X get free / % off / fixed off delivery",
 * for every zone or one zone.
 */
class ShippingDiscount extends Model
{
    public const TYPES = ['free' => 'Free delivery', 'percent' => '% off delivery', 'fixed' => 'Amount off delivery'];

    protected $table = 'ecom_shipping_discounts';

    protected $fillable = ['shipping_zone_id', 'min_order_amount', 'type', 'value', 'is_active'];

    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Rules that can apply to this zone (its own and the all-zone ones).
     */
    public function scopeForZone(Builder $query, ?ShippingZone $zone): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('shipping_zone_id')->when($zone, fn ($q) => $q->orWhere('shipping_zone_id', $zone->id)));
    }

    /**
     * How much of $charge this rule takes off.
     */
    public function discountFor(float $charge): float
    {
        $discount = match ($this->type) {
            'free' => $charge,
            'percent' => $charge * min(100, (float) $this->value) / 100,
            default => (float) $this->value,
        };

        return round(min(max(0, $discount), $charge), 2);
    }

    /**
     * "Free delivery", "50% off delivery", "৳30 off delivery"
     */
    public function getLabelAttribute(): string
    {
        return match ($this->type) {
            'free' => 'Free delivery',
            'percent' => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off delivery',
            default => ecom_money($this->value).' off delivery',
        };
    }
}

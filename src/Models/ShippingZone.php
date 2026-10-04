<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ME\Ecom\Services\ShippingCalculator;

class ShippingZone extends Model
{
    protected $table = 'ecom_shipping_zones';

    protected $fillable = ['name', 'charge', 'delivery_time', 'sort_order', 'is_active'];

    protected $casts = ['charge' => 'decimal:2', 'is_active' => 'boolean'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(ShippingDiscount::class);
    }

    /**
     * Delivery charge for an order subtotal (zone charge minus order-amount discount, no product rules).
     * Use ShippingCalculator::quote() when the products are known.
     */
    public function chargeFor(float $subtotal): float
    {
        return app(ShippingCalculator::class)->quote($this, [], $subtotal)['charge'];
    }
}

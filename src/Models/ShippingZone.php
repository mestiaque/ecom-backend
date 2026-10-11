<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ME\Ecom\Services\ShippingCalculator;
use ME\Models\GeoLocation;

class ShippingZone extends Model
{
    protected $table = 'ecom_shipping_zones';

    protected $fillable = ['name', 'charge', 'delivery_time', 'is_default', 'sort_order', 'is_active'];

    protected $casts = ['charge' => 'decimal:2', 'is_default' => 'boolean', 'is_active' => 'boolean'];

    /**
     * Divisions / districts / upazilas this zone delivers to (an area belongs to one zone only).
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(GeoLocation::class, 'ecom_shipping_zone_areas', 'shipping_zone_id', 'geo_location_id');
    }

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

<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $table = 'ecom_coupons';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_order_amount', 'max_discount',
        'usage_limit', 'usage_limit_per_customer', 'used_count', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Why the coupon cannot be used for this order, or null when it can.
     */
    public function rejectionReason(float $subtotal, ?Customer $customer = null): ?string
    {
        return match (true) {
            ! $this->is_active => 'This coupon is not active.',
            $this->starts_at && $this->starts_at->isFuture() => 'This coupon is not valid yet.',
            $this->expires_at && $this->expires_at->isPast() => 'This coupon has expired.',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'This coupon has reached its usage limit.',
            $this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount => 'Minimum order amount is '.ecom_money($this->min_order_amount).'.',
            $customer && $this->usage_limit_per_customer !== null
                && $this->orders()->where('customer_id', $customer->id)->count() >= $this->usage_limit_per_customer => 'You have already used this coupon.',
            default => null,
        };
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        if ($this->type === 'percent' && $this->max_discount !== null) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            ! $this->is_active => 'Inactive',
            $this->expires_at && $this->expires_at->isPast() => 'Expired',
            $this->starts_at && $this->starts_at->isFuture() => 'Scheduled',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'Used up',
            default => 'Active',
        };
    }
}

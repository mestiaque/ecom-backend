<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    protected $table = 'ecom_campaigns';

    protected $fillable = ['title', 'slug', 'description', 'banner', 'discount_type', 'discount_value', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecom_campaign_product');
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>=', now());
    }

    public function isRunning(): bool
    {
        return $this->is_active && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function apply(float $price): float
    {
        $discount = $this->discount_type === 'percent'
            ? $price * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return round(max(0, $price - $discount), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            ! $this->is_active => 'Inactive',
            $this->ends_at->isPast() => 'Ended',
            $this->starts_at->isFuture() => 'Upcoming',
            default => 'Running',
        };
    }
}

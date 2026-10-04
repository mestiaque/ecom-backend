<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ME\Ecom\Enums\OrderStatus;

class Customer extends Model
{
    protected $table = 'ecom_customers';

    protected $fillable = [
        'name', 'phone', 'email', 'avatar', 'password', 'address', 'city', 'is_blocked', 'block_reason',
        'phone_verified_at', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_blocked' => 'boolean',
        'password' => 'hashed',
        'phone_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        return ecom_image($this->avatar);
    }

    public function getInitialAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->name, 0, 1));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Adds orders_count and lifetime_value (total of orders that count as sales).
     */
    public function scopeWithLifetimeValue(Builder $query): Builder
    {
        return $query
            ->withCount('orders')
            ->withSum(['orders as lifetime_value' => fn ($q) => $q->whereIn('status', OrderStatus::revenueValues())], 'total')
            ->withMax('orders as last_order_at', 'created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(($filters['status'] ?? null) === 'blocked', fn ($q) => $q->where('is_blocked', true))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_blocked', false));
    }
}

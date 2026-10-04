<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ME\Models\User;

class Transaction extends Model
{
    protected $table = 'ecom_transactions';

    protected $fillable = ['order_id', 'type', 'method', 'amount', 'trx_id', 'status', 'gateway_response', 'note', 'user_id'];

    protected $casts = ['amount' => 'decimal:2', 'gateway_response' => 'array'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('trx_id', 'like', "%{$search}%")
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"))))
            ->when($filters['method'] ?? null, fn ($q, $method) => $q->where('method', $method))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }
}

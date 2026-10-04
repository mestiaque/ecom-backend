<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ME\Ecom\Enums\OrderStatus;
use ME\Models\User;

/**
 * Order timeline row: a status change (status set) or an admin comment (status null).
 */
class OrderNote extends Model
{
    protected $table = 'ecom_order_notes';

    protected $fillable = ['order_id', 'user_id', 'status', 'note'];

    protected $casts = ['status' => OrderStatus::class];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

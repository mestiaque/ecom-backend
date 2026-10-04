<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class OrderItem extends Model
{
    protected $table = 'ecom_order_items';

    protected $fillable = ['order_id', 'product_id', 'variant_id', 'product_name', 'variant_label', 'sku', 'unit_price', 'quantity', 'line_total', 'warranty_label', 'warranty_days'];

    protected $casts = ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'quantity' => 'integer', 'warranty_days' => 'integer'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Last day of the warranty, counted from delivery. Null when there is no warranty or the order is not delivered yet.
     */
    public function warrantyEndsAt(): ?Carbon
    {
        $deliveredAt = $this->order?->delivered_at;

        return ($this->warranty_days && $deliveredAt) ? $deliveredAt->copy()->addDays($this->warranty_days) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}

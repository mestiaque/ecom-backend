<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Enums\PaymentStatus;

class Order extends Model
{
    protected $table = 'ecom_orders';

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_phone', 'customer_email', 'shipping_address',
        'city', 'billing_address', 'shipping_zone_id', 'subtotal', 'discount', 'shipping_charge', 'shipping_discount', 'total', 'coupon_id', 'coupon_code',
        'status', 'payment_method', 'payment_status', 'courier', 'tracking_id', 'consignment_id', 'sent_to_courier_at',
        'customer_note', 'stock_restored', 'delivered_at', 'cancelled_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentStatus::class,
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'shipping_discount' => 'decimal:2',
        'total' => 'decimal:2',
        'stock_restored' => 'boolean',
        'sent_to_courier_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            if (! $order->order_number) {
                $prefix = ecom_setting('order_prefix', 'ORD-');
                $order->forceFill(['order_number' => $prefix.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest('id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest('id');
    }

    public function scopeCountsAsSale(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::revenueValues());
    }

    /**
     * ?search= (order no / name / phone), ?status=, ?payment_status=, ?payment_method=, ?from=, ?to=, ?courier=
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")
                ->orWhere('tracking_id', 'like', "%{$search}%")))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['payment_method'] ?? null, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($filters['courier'] ?? null, fn ($q, $courier) => $q->where('courier', $courier))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to));
    }

    public function paidAmount(): float
    {
        $transactions = $this->transactions()->where('status', 'success')->get();

        return (float) $transactions->where('type', 'payment')->sum('amount') - (float) $transactions->where('type', 'refund')->sum('amount');
    }

    public function refundedAmount(): float
    {
        return (float) $this->transactions()->where('status', 'success')->where('type', 'refund')->sum('amount');
    }

    /**
     * Customer tracking page (signed link, valid for 30 days) — share it by SMS / WhatsApp.
     */
    public function trackingPageUrl(): string
    {
        return URL::temporarySignedRoute('ecom.track.show', now()->addDays(30), ['order' => $this->order_number]);
    }

    public function trackingUrl(): ?string
    {
        $template = config("ecom.couriers.{$this->courier}.tracking");

        return ($template && $this->tracking_id) ? str_replace('{tracking}', urlencode($this->tracking_id), $template) : null;
    }
}

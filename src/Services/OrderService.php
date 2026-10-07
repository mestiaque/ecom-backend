<?php

namespace ME\Ecom\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Enums\PaymentStatus;
use ME\Ecom\Models\Coupon;
use ME\Ecom\Models\Customer;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\OrderNote;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Models\Transaction;

class OrderService
{
    /**
     * Place an order: checks stock, prices items (campaigns included), applies coupon and
     * shipping, takes stock out. Used by the storefront and the demo seeder.
     *
     * @param  array{customer_name: string, customer_phone: string, customer_email?: ?string, shipping_address: string, city?: ?string, shipping_zone_id?: ?int, payment_method?: string, coupon_code?: ?string, customer_note?: ?string, customer_id?: ?int}  $data
     * @param  array<int, array{product_id: int, variant_id?: ?int, quantity: int}>  $items
     *
     * @throws ValidationException
     */
    public function place(array $data, array $items): Order
    {
        return DB::transaction(function () use ($data, $items) {
            $lines = [];
            $shippingItems = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $product = Product::with(['campaigns', 'warranty'])->lockForUpdate()->findOrFail($item['product_id']);
                $variant = ! empty($item['variant_id']) ? $product->variants()->lockForUpdate()->findOrFail($item['variant_id']) : null;
                $quantity = max(1, (int) $item['quantity']);
                $available = $variant ? $variant->stock : $product->stock;

                if (! $product->is_active || $available < $quantity) {
                    throw ValidationException::withMessages(['items' => "{$product->title} is out of stock."]);
                }

                $price = $product->finalPrice($variant);
                $subtotal += $price * $quantity;
                $shippingItems[] = ['product' => $product, 'quantity' => $quantity];
                $lines[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->title,
                    'variant_label' => $variant?->label,
                    'sku' => $variant?->sku ?? $product->sku,
                    'warranty_label' => $product->warranty?->name,
                    'warranty_days' => $product->warranty?->days,
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'line_total' => $price * $quantity,
                ];
            }

            $customer = isset($data['customer_id']) ? Customer::find($data['customer_id']) : null;
            $coupon = null;
            $discount = 0.0;

            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', strtoupper($data['coupon_code']))->lockForUpdate()->first();
                $reason = $coupon ? $coupon->rejectionReason($subtotal, $customer) : 'Invalid coupon code.';

                if ($reason) {
                    throw ValidationException::withMessages(['coupon_code' => $reason]);
                }

                $discount = $coupon->discountFor($subtotal);
                $coupon->increment('used_count');
            }

            $zone = ! empty($data['shipping_zone_id']) ? ShippingZone::find($data['shipping_zone_id']) : null;
            $quote = $zone ? app(ShippingCalculator::class)->quote($zone, $shippingItems, $subtotal) : null;
            $shipping = $quote['charge'] ?? 0.0;

            $order = Order::create([
                ...$data,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_charge' => $shipping,
                'shipping_discount' => $quote['discount'] ?? 0.0,
                'total' => max(0, $subtotal - $discount + $shipping),
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'status' => OrderStatus::Pending,
                'payment_method' => $data['payment_method'] ?? PaymentMethod::CashOnDelivery->value,
                'payment_status' => PaymentStatus::Unpaid,
            ]);
            $order->items()->createMany($lines);

            foreach ($lines as $line) {
                Product::find($line['product_id'])->adjustStock(-$line['quantity'], $line['variant_id']);
            }

            $order->notes()->create(['status' => OrderStatus::Pending, 'note' => 'Order placed']);

            return $order;
        });
    }

    /**
     * Move an order to a new status (only along OrderStatus::allowedNext()).
     *
     * @throws ValidationException
     */
    public function changeStatus(Order $order, OrderStatus $status, ?string $note = null, ?int $userId = null): void
    {
        if (! $order->status->canMoveTo($status)) {
            throw ValidationException::withMessages([
                'status' => "Order cannot move from {$order->status->label()} to {$status->label()}.",
            ]);
        }

        // A parcel is "shipped" only once it is with a courier we can track
        if ($status === OrderStatus::Shipped && (blank($order->courier) || blank($order->tracking_id))) {
            throw ValidationException::withMessages([
                'status' => 'Add the courier and tracking ID first (Courier → Send to courier), then mark the order as Shipped.',
            ]);
        }

        DB::transaction(function () use ($order, $status, $note, $userId) {
            $order->status = $status;

            if ($status->releasesStock() && ! $order->stock_restored) {
                $this->restoreStock($order);
                $order->stock_restored = true;
            }

            if ($status === OrderStatus::Cancelled) {
                $order->cancelled_at = now();
            }

            if ($status === OrderStatus::Delivered) {
                $order->delivered_at = now();
            }

            $order->save();
            $order->notes()->create(['status' => $status, 'note' => $note, 'user_id' => $userId]);

            // Cash on delivery: money is collected when the parcel is delivered
            if ($status === OrderStatus::Delivered && $order->payment_method === PaymentMethod::CashOnDelivery && $order->payment_status === PaymentStatus::Unpaid) {
                $due = (float) $order->total - $order->paidAmount();

                if ($due > 0) {
                    $this->recordPayment($order, [
                        'method' => PaymentMethod::CashOnDelivery->value,
                        'amount' => $due,
                        'note' => 'Collected on delivery',
                    ], $userId);
                }
            }
        });
    }

    public function addNote(Order $order, string $note, ?int $userId = null): OrderNote
    {
        return $order->notes()->create(['note' => $note, 'user_id' => $userId]);
    }

    /**
     * @param  array{method: string, amount: float|string, trx_id?: ?string, note?: ?string}  $data
     */
    public function recordPayment(Order $order, array $data, ?int $userId = null): Transaction
    {
        $transaction = $order->transactions()->create([
            'type' => 'payment',
            'method' => $data['method'],
            'amount' => $data['amount'],
            'trx_id' => $data['trx_id'] ?? null,
            'status' => 'success',
            'note' => $data['note'] ?? null,
            'user_id' => $userId,
        ]);

        $this->refreshPaymentStatus($order);

        return $transaction;
    }

    /**
     * @param  array{amount: float|string, method?: ?string, trx_id?: ?string, note?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function refund(Order $order, array $data, ?int $userId = null): Transaction
    {
        if ((float) $data['amount'] > $order->paidAmount() + 0.001) {
            throw ValidationException::withMessages(['amount' => 'Refund cannot be more than the paid amount ('.ecom_money($order->paidAmount()).').']);
        }

        $transaction = $order->transactions()->create([
            'type' => 'refund',
            'method' => $data['method'] ?? $order->payment_method->value,
            'amount' => $data['amount'],
            'trx_id' => $data['trx_id'] ?? null,
            'status' => 'success',
            'note' => $data['note'] ?? null,
            'user_id' => $userId,
        ]);

        $this->refreshPaymentStatus($order);

        return $transaction;
    }

    /**
     * paid = fully paid; refunded = money was returned and nothing is left paid; otherwise unpaid.
     */
    public function refreshPaymentStatus(Order $order): void
    {
        $paid = $order->paidAmount();

        $status = match (true) {
            $paid > 0 && $paid + 0.001 >= (float) $order->total => PaymentStatus::Paid,
            $paid <= 0 && $order->refundedAmount() > 0 => PaymentStatus::Refunded,
            default => PaymentStatus::Unpaid,
        };

        $order->forceFill(['payment_status' => $status])->save();
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $item->product?->adjustStock($item->quantity, $item->variant_id);
        }
    }
}

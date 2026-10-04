<?php

namespace ME\Ecom\Services;

use ME\Ecom\Models\Product;
use ME\Ecom\Models\ShippingDiscount;
use ME\Ecom\Models\ShippingZone;

/**
 * The delivery charge of an order, used by checkout and OrderService:
 *
 *   1. zone charge (Shipping → Delivery Zones)
 *   2. + each product's extra / less charge × quantity (products with free delivery add nothing)
 *   3. the whole order ships free when every product has free delivery
 *   4. − the best order-amount rule (Shipping → Delivery Discounts) the subtotal reaches
 */
class ShippingCalculator
{
    /**
     * @param  iterable<int, array{product: Product, quantity: int}>  $items
     * @return array{base: float, adjustment: float, before_discount: float, discount: float, charge: float, all_free: bool, rule: ?ShippingDiscount}
     */
    public function quote(ShippingZone $zone, iterable $items, float $subtotal): array
    {
        $base = (float) $zone->charge;
        $adjustment = 0.0;
        $count = 0;
        $freeCount = 0;

        foreach ($items as $item) {
            $count++;

            if ($item['product']->free_delivery) {
                $freeCount++;

                continue;
            }

            $adjustment += (float) $item['product']->delivery_charge_adjustment * max(1, (int) $item['quantity']);
        }

        $allFree = $count > 0 && $freeCount === $count;
        $beforeDiscount = $allFree ? 0.0 : round(max(0, $base + $adjustment), 2);
        $rule = $beforeDiscount > 0 ? $this->ruleFor($zone, $subtotal) : null;
        $discount = $rule ? $rule->discountFor($beforeDiscount) : 0.0;

        return [
            'base' => $base,
            'adjustment' => round($adjustment, 2),
            'before_discount' => $beforeDiscount,
            'discount' => $discount,
            'charge' => round($beforeDiscount - $discount, 2),
            'all_free' => $allFree,
            'rule' => $rule,
        ];
    }

    /**
     * The best rule the subtotal reaches: the highest minimum, a zone's own rule winning a tie.
     */
    public function ruleFor(?ShippingZone $zone, float $subtotal): ?ShippingDiscount
    {
        return ShippingDiscount::active()
            ->forZone($zone)
            ->where('min_order_amount', '<=', $subtotal)
            ->orderByDesc('min_order_amount')
            ->orderByRaw('shipping_zone_id IS NULL')
            ->first();
    }

    /**
     * Lowest order amount that ships free in every zone (for "free delivery over ৳X" messages), or null.
     */
    public function freeDeliveryMin(): ?float
    {
        $min = ShippingDiscount::active()->whereNull('shipping_zone_id')->where('type', 'free')->min('min_order_amount');

        return $min !== null ? (float) $min : null;
    }
}

<?php

namespace ME\Ecom\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Models\Customer;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\OrderItem;

/**
 * Sales figures. Only orders that count as sales are included (cancelled / returned excluded).
 */
class ReportService
{
    /**
     * Totals for orders placed in [from, to].
     *
     * @return array{orders: int, revenue: float, discount: float, shipping: float, items: int}
     */
    public function totals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = Order::countsAsSale()->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discount, COALESCE(SUM(shipping_charge),0) as shipping')
            ->first();

        $items = OrderItem::whereHas('order', fn ($q) => $q->countsAsSale()->whereBetween('created_at', [$from, $to]))->sum('quantity');

        return [
            'orders' => (int) $row->orders,
            'revenue' => (float) $row->revenue,
            'discount' => (float) $row->discount,
            'shipping' => (float) $row->shipping,
            'items' => (int) $items,
        ];
    }

    /**
     * Sales grouped by day, week or month, with empty periods filled in.
     *
     * @return Collection<int, array{label: string, orders: int, revenue: float, discount: float, shipping: float}>
     */
    public function salesByPeriod(CarbonImmutable $from, CarbonImmutable $to, string $period = 'daily'): Collection
    {
        $orders = Order::countsAsSale()->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'total', 'discount', 'shipping_charge']);

        $keyFor = fn (CarbonImmutable $date) => match ($period) {
            'monthly' => $date->format('Y-m'),
            'weekly' => $date->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m-d'),
        };
        $labelFor = fn (CarbonImmutable $date) => match ($period) {
            'monthly' => $date->format('M Y'),
            'weekly' => $date->startOfWeek()->format('d M').' – '.$date->endOfWeek()->format('d M'),
            default => $date->format('d M'),
        };
        $step = match ($period) {
            'monthly' => '1 month',
            'weekly' => '1 week',
            default => '1 day',
        };

        $grouped = $orders->groupBy(fn ($order) => $keyFor(CarbonImmutable::parse($order->created_at)));
        $rows = collect();
        $cursor = match ($period) {
            'monthly' => $from->startOfMonth(),
            'weekly' => $from->startOfWeek(),
            default => $from->startOfDay(),
        };

        while ($cursor <= $to) {
            $group = $grouped->get($keyFor($cursor), collect());
            $rows->push([
                'label' => $labelFor($cursor),
                'orders' => $group->count(),
                'revenue' => (float) $group->sum('total'),
                'discount' => (float) $group->sum('discount'),
                'shipping' => (float) $group->sum('shipping_charge'),
            ]);
            $cursor = $cursor->add($step);
        }

        return $rows;
    }

    /**
     * Quantity sold and revenue per product.
     */
    public function productSales(CarbonImmutable $from, CarbonImmutable $to, ?int $limit = null): Collection
    {
        return OrderItem::query()
            ->join('ecom_orders', 'ecom_orders.id', '=', 'ecom_order_items.order_id')
            ->whereIn('ecom_orders.status', OrderStatus::revenueValues())
            ->whereBetween('ecom_orders.created_at', [$from, $to])
            ->groupBy('ecom_order_items.product_id', 'ecom_order_items.product_name')
            ->select('ecom_order_items.product_id', 'ecom_order_items.product_name')
            ->selectRaw('SUM(ecom_order_items.quantity) as quantity, SUM(ecom_order_items.line_total) as revenue, COUNT(DISTINCT ecom_orders.id) as orders')
            ->orderByDesc('quantity')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    /**
     * Customers with their order count and spend in the period.
     */
    public function customerSales(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Customer::query()
            ->join('ecom_orders', 'ecom_orders.customer_id', '=', 'ecom_customers.id')
            ->whereIn('ecom_orders.status', OrderStatus::revenueValues())
            ->whereBetween('ecom_orders.created_at', [$from, $to])
            ->groupBy('ecom_customers.id', 'ecom_customers.name', 'ecom_customers.phone', 'ecom_customers.email', 'ecom_customers.created_at')
            ->select('ecom_customers.id', 'ecom_customers.name', 'ecom_customers.phone', 'ecom_customers.email', 'ecom_customers.created_at')
            ->selectRaw('COUNT(ecom_orders.id) as orders, SUM(ecom_orders.total) as spent, MAX(ecom_orders.created_at) as last_order_at')
            ->orderByDesc('spent')
            ->get();
    }

    /**
     * Order count per status (all statuses, zero-filled).
     *
     * @return array<string, int>
     */
    public function statusCounts(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $counts = Order::query()
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('status')
            ->pluck(DB::raw('COUNT(*)'), 'status')
            ->all();

        return collect(OrderStatus::cases())->mapWithKeys(fn ($status) => [$status->value => (int) ($counts[$status->value] ?? 0)])->all();
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Customer;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\Review;
use ME\Ecom\Services\ReportService;

class DashboardController extends EcomController
{
    public function __construct(private ReportService $reports)
    {
        $this->middleware('authorization:ecom.dashboard');
    }

    public function index(): View
    {
        $now = CarbonImmutable::now();

        return view('ecom::dashboard', [
            'today' => $this->reports->totals($now->startOfDay(), $now->endOfDay()),
            'month' => $this->reports->totals($now->startOfMonth(), $now->endOfDay()),
            'statusCounts' => $this->reports->statusCounts(),
            'topProducts' => $this->reports->productSales($now->subDays(29)->startOfDay(), $now->endOfDay(), 8),
            'lowStock' => Product::with('primaryImage')->active()->lowStock()->orderBy('stock')->take(8)->get(),
            'lowStockCount' => Product::active()->lowStock()->count(),
            'newCustomersToday' => Customer::where('created_at', '>=', $now->startOfDay())->count(),
            'newCustomersMonth' => Customer::where('created_at', '>=', $now->startOfMonth())->count(),
            'recentOrders' => Order::latest('id')->take(8)->get(),
            'pendingReviews' => Review::where('is_approved', false)->count(),
        ]);
    }

    /**
     * Sales chart data: ?period=daily (30 days) | weekly (12 weeks) | monthly (12 months).
     */
    public function chart(Request $request): JsonResponse
    {
        $now = CarbonImmutable::now()->endOfDay();
        [$period, $from] = match ($request->period) {
            'weekly' => ['weekly', $now->subWeeks(11)->startOfWeek()],
            'monthly' => ['monthly', $now->subMonths(11)->startOfMonth()],
            default => ['daily', $now->subDays(29)->startOfDay()],
        };
        $rows = $this->reports->salesByPeriod($from, $now, $period);

        return response()->json([
            'labels' => $rows->pluck('label'),
            'revenue' => $rows->pluck('revenue'),
            'orders' => $rows->pluck('orders'),
        ]);
    }
}

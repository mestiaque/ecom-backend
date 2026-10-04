<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Services\ReportService;
use ME\Ecom\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends EcomController
{
    public function __construct(private ReportService $reports)
    {
        $this->middleware('authorization:ecom_report.sales')->only('sales');
        $this->middleware('authorization:ecom_report.product')->only('products');
        $this->middleware('authorization:ecom_report.customer')->only('customers');
    }

    public function sales(Request $request): View|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $period = in_array($request->period, ['daily', 'weekly', 'monthly'], true) ? $request->period : 'daily';
        $rows = $this->reports->salesByPeriod($from, $to, $period);

        if ($request->query('export') === 'csv') {
            return Csv::download("sales-report-{$from->toDateString()}-to-{$to->toDateString()}.csv",
                ['Period', 'Orders', 'Revenue', 'Discount', 'Shipping'],
                $rows->map(fn ($row) => [$row['label'], $row['orders'], $row['revenue'], $row['discount'], $row['shipping']]));
        }

        return view('ecom::reports.sales', [
            'rows' => $rows,
            'totals' => $this->reports->totals($from, $to),
            'statusCounts' => $this->reports->statusCounts($from, $to),
            'from' => $from,
            'to' => $to,
            'period' => $period,
        ]);
    }

    public function products(Request $request): View|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $rows = $this->reports->productSales($from, $to);

        if ($request->query('export') === 'csv') {
            return Csv::download("product-report-{$from->toDateString()}-to-{$to->toDateString()}.csv",
                ['Product', 'Quantity Sold', 'Orders', 'Revenue'],
                $rows->map(fn ($row) => [$row->product_name, $row->quantity, $row->orders, $row->revenue]));
        }

        return view('ecom::reports.products', compact('rows', 'from', 'to'));
    }

    public function customers(Request $request): View|StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $rows = $this->reports->customerSales($from, $to);

        if ($request->query('export') === 'csv') {
            return Csv::download("customer-report-{$from->toDateString()}-to-{$to->toDateString()}.csv",
                ['Customer', 'Phone', 'Email', 'Orders', 'Spent', 'Last Order', 'Joined'],
                $rows->map(fn ($row) => [$row->name, $row->phone, $row->email, $row->orders, $row->spent, $row->last_order_at, $row->created_at?->format('Y-m-d')]));
        }

        return view('ecom::reports.customers', compact('rows', 'from', 'to'));
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Models\Order;
use ME\Ecom\Services\Couriers\CourierException;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Services\OrderService;
use ME\Ecom\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends EcomController
{
    private const FILTERS = ['search', 'status', 'payment_status', 'payment_method', 'courier', 'from', 'to'];

    public function __construct(private OrderService $orders, private CourierManager $couriers)
    {
        $this->middleware('authorization:ecom_order.view')->only(['index', 'show']);
        $this->middleware('authorization:ecom_order.status')->only('updateStatus');
        $this->middleware('authorization:ecom_order.note')->only('addNote');
        $this->middleware('authorization:ecom_order.courier')->only('sendToCourier');
        $this->middleware('authorization:ecom_order.invoice')->only(['invoice', 'invoicePdf']);
        $this->middleware('authorization:ecom_order.export')->only('export');
        $this->middleware('authorization:ecom_payment.record')->only('recordPayment');
        $this->middleware('authorization:ecom_payment.refund')->only('refund');
    }

    public function index(Request $request): View
    {
        $orders = Order::withCount('items')
            ->filter($request->only(self::FILTERS))
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        $statusCounts = Order::filter($request->except('status'))->groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status');

        return view('ecom::orders.index', compact('orders', 'statusCounts'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product.primaryImage', 'notes.user', 'transactions.user', 'customer', 'shippingZone', 'coupon']);

        return view('ecom::orders.show', [
            'order' => $order,
            'paid' => $order->paidAmount(),
            'couriers' => $this->couriers->all(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => 'nullable|string|max:1000',
        ]);
        $from = $order->status;

        $this->orders->changeStatus($order, OrderStatus::from($data['status']), $data['note'] ?? null, auth()->id());
        me_change_log("Order {$order->order_number} status changed", 'ecom.order.status')
            ->subject($order)
            ->record(['status' => $from->label()], ['status' => $order->status->label()]);

        return back()->with('success', "Order marked as {$order->status->label()}.");
    }

    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:2000']);
        $this->orders->addNote($order, $data['note'], auth()->id());

        return back()->with('success', 'Comment added.');
    }

    public function sendToCourier(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'courier' => ['required', Rule::in(array_merge(array_keys($this->couriers->all()), ['manual']))],
            'mode' => 'required|in:api,manual',
            'manual_courier' => 'required_if:courier,manual|nullable|string|max:100',
            'tracking_id' => 'required_if:mode,manual|nullable|string|max:100',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:255',
        ]);

        if ($data['mode'] === 'manual' || $data['courier'] === 'manual') {
            $courier = $data['courier'] === 'manual' ? $data['manual_courier'] : $data['courier'];
            $this->couriers->assign($order, $courier, $data['tracking_id'], null, null, auth()->id());

            return back()->with('success', 'Courier and tracking ID saved.');
        }

        try {
            $result = $this->couriers->send($order, $data['courier'], $data['options'] ?? [], auth()->id());
        } catch (CourierException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Courier request failed: '.$e->getMessage());
        }

        return back()->with('success', "Parcel booked. Tracking ID: {$result->trackingId}");
    }

    public function recordPayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => 'required|numeric|min:0.01',
            'trx_id' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $transaction = $this->orders->recordPayment($order, $data, auth()->id());
        me_change_log("Payment recorded for {$order->order_number}", 'ecom.payment.record')->subject($order)->record([], $transaction->only(['method', 'amount', 'trx_id', 'note']));

        return back()->with('success', 'Payment recorded.');
    }

    public function refund(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'trx_id' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $transaction = $this->orders->refund($order, $data, auth()->id());
        me_change_log("Refund for {$order->order_number}", 'ecom.payment.refund')->subject($order)->record([], $transaction->only(['method', 'amount', 'trx_id', 'note']));

        return back()->with('success', 'Refund recorded.');
    }

    public function invoice(Order $order): View
    {
        return view('ecom::orders.invoice', ['order' => $order->load('items'), 'pdf' => false]);
    }

    public function invoicePdf(Order $order): Response
    {
        return Pdf::loadView('ecom::orders.invoice', ['order' => $order->load('items'), 'pdf' => true])
            ->setPaper('a4')
            ->download("invoice-{$order->order_number}.pdf");
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = function () use ($request) {
            foreach (Order::withCount('items')->filter($request->only(self::FILTERS))->latest('id')->lazy() as $order) {
                yield [
                    $order->order_number, $order->created_at->format('Y-m-d H:i'), $order->customer_name, $order->customer_phone,
                    $order->city, $order->items_count, $order->subtotal, $order->discount, $order->shipping_charge, $order->total,
                    $order->status->label(), $order->payment_method->label(), $order->payment_status->label(),
                    $order->courier ? $this->couriers->labelFor($order->courier) : '', $order->tracking_id,
                ];
            }
        };

        return Csv::download('orders-'.now()->format('Y-m-d').'.csv', [
            'Order', 'Date', 'Customer', 'Phone', 'City', 'Items', 'Subtotal', 'Discount', 'Shipping', 'Total',
            'Status', 'Payment Method', 'Payment Status', 'Courier', 'Tracking ID',
        ], $rows());
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Models\Order;

/**
 * Public order tracking: the customer enters order number + phone and gets a signed tracking link.
 */
class TrackingController extends EcomController
{
    public function form(): View
    {
        return view('ecom::tracking.form');
    }

    public function submit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
        ]);

        $number = strtoupper(trim($data['order_number']));
        $order = Order::where('order_number', $number)
            ->when(ctype_digit($number), fn ($q) => $q->orWhere('id', (int) $number))
            ->get()
            ->first(fn (Order $order) => self::samePhone($order->customer_phone, $data['phone']));

        if (! $order) {
            return back()->withInput()->withErrors(['order_number' => 'No order found with this order number and phone number. Please check both and try again.']);
        }

        return redirect()->to($order->trackingPageUrl());
    }

    public function show(Order $order): View
    {
        $order->load(['items.product.primaryImage', 'shippingZone']);

        // Only status changes are public — admin comments and their texts stay internal
        $history = $order->notes()->whereNotNull('status')->oldest('id')->get(['status', 'created_at']);
        $reachedAt = $history->groupBy(fn ($note) => $note->status->value)->map(fn ($notes) => $notes->first()->created_at);

        return view('ecom::tracking.show', [
            'order' => $order,
            'history' => $history,
            'reachedAt' => $reachedAt,
            'steps' => [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered],
        ]);
    }

    /**
     * Compare Bangladeshi numbers ignoring +88, spaces and dashes (last 10 digits).
     */
    public static function samePhone(?string $a, ?string $b): bool
    {
        $digits = fn (?string $phone) => substr(preg_replace('/\D/', '', (string) $phone), -10);

        return $digits($a) !== '' && $digits($a) === $digits($b);
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Customer;

class CustomerController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_customer.view')->only(['index', 'show']);
        $this->middleware('authorization:ecom_customer.block')->only('toggleBlock');
    }

    public function index(Request $request): View
    {
        $sort = in_array($request->sort, ['lifetime_value', 'orders_count', 'last_order_at'], true) ? $request->sort : 'id';
        $customers = Customer::withLifetimeValue()
            ->filter($request->only(['search', 'status']))
            ->orderByDesc($sort)
            ->paginate($this->perPage())
            ->withQueryString();

        return view('ecom::customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $customer = Customer::withLifetimeValue()->findOrFail($customer->id);
        $orders = $customer->orders()->withCount('items')->latest('id')->paginate($this->perPage());
        $reviews = $customer->reviews()->with('product')->latest()->take(10)->get();

        return view('ecom::customers.show', compact('customer', 'orders', 'reviews'));
    }

    public function toggleBlock(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['block_reason' => 'nullable|string|max:255']);
        $blocking = ! $customer->is_blocked;

        me_change_log(($blocking ? 'Customer blocked: ' : 'Customer unblocked: ').$customer->name, 'ecom.customer.block')
            ->watch($customer)
            ->run(fn () => $customer->update([
                'is_blocked' => $blocking,
                'block_reason' => $blocking ? ($data['block_reason'] ?? null) : null,
            ]));

        return back()->with('success', $blocking ? 'Customer blocked.' : 'Customer unblocked.');
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\Coupon;

class CouponController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_coupon.view')->only('index');
        $this->middleware('authorization:ecom_coupon.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_coupon.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_coupon.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $coupons = Coupon::withCount('orders')
            ->when($request->search, fn ($q, $search) => $q->where('code', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('ecom::coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('ecom::coupons.form', ['coupon' => new Coupon(['is_active' => true, 'type' => 'percent'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('Coupon created: '.strtoupper($data['code']), 'ecom.coupon.create')->create(fn () => Coupon::create($data));

        return redirect()->route('ecom.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('ecom::coupons.form', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon);
        me_change_log('Coupon updated: '.$coupon->code, 'ecom.coupon.update')->watch($coupon)->run(fn () => $coupon->update($data));

        return redirect()->route('ecom.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        me_change_log('Coupon deleted: '.$coupon->code, 'ecom.coupon.delete')->watch($coupon)->delete(fn () => $coupon->delete());

        return redirect()->route('ecom.coupons.index')->with('success', 'Coupon deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->code))]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('ecom_coupons', 'code')->ignore($coupon?->id)],
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:percent,fixed',
            'value' => ['required', 'numeric', 'min:0.01', $request->type === 'percent' ? 'max:100' : 'max:9999999'],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

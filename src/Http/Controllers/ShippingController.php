<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\ShippingDiscount;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Support\EcomSettings;

class ShippingController extends EcomController
{
    private const SETTING_KEYS = ['low_stock_threshold'];

    public function __construct(private EcomSettings $settings)
    {
        $this->middleware('authorization:ecom_setting.shipping');
    }

    public function index(): View
    {
        return view('ecom::shipping.index', [
            'zones' => ShippingZone::withCount('orders')->orderBy('sort_order')->orderBy('id')->get(),
            'discounts' => ShippingDiscount::with('zone')->orderBy('min_order_amount')->get(),
            'settings' => $this->settings,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $before = $this->settings->snapshot(self::SETTING_KEYS);
        $this->settings->set($data);
        me_change_log('Shipping settings updated', 'ecom.settings.shipping')->record($before, $this->settings->snapshot(self::SETTING_KEYS));

        return back()->with('success', 'Shipping settings saved.');
    }

    public function storeDiscount(Request $request): RedirectResponse
    {
        $data = $this->validatedDiscount($request);
        me_change_log('Delivery discount created', 'ecom.shipping.discount')->create(fn () => ShippingDiscount::create($data));

        return back()->with('success', 'Delivery discount added.');
    }

    public function updateDiscount(Request $request, ShippingDiscount $discount): RedirectResponse
    {
        $data = $this->validatedDiscount($request);
        me_change_log('Delivery discount updated', 'ecom.shipping.discount')->watch($discount)->run(fn () => $discount->update($data));

        return back()->with('success', 'Delivery discount updated.');
    }

    public function destroyDiscount(ShippingDiscount $discount): RedirectResponse
    {
        me_change_log('Delivery discount deleted', 'ecom.shipping.discount')->watch($discount)->delete(fn () => $discount->delete());

        return back()->with('success', 'Delivery discount deleted.');
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('Delivery zone created: '.$data['name'], 'ecom.shipping.zone')->create(fn () => ShippingZone::create($data));

        return back()->with('success', 'Delivery zone added.');
    }

    public function updateZone(Request $request, ShippingZone $zone): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('Delivery zone updated: '.$zone->name, 'ecom.shipping.zone')->watch($zone)->run(fn () => $zone->update($data));

        return back()->with('success', 'Delivery zone updated.');
    }

    public function destroyZone(ShippingZone $zone): RedirectResponse
    {
        me_change_log('Delivery zone deleted: '.$zone->name, 'ecom.shipping.zone')->watch($zone)->delete(fn () => $zone->delete());

        return back()->with('success', 'Delivery zone deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDiscount(Request $request): array
    {
        $data = $request->validate([
            'shipping_zone_id' => 'nullable|integer|exists:ecom_shipping_zones,id',
            'min_order_amount' => 'required|numeric|min:0',
            'type' => ['required', Rule::in(array_keys(ShippingDiscount::TYPES))],
            'value' => ['nullable', 'numeric', 'min:0', Rule::requiredIf(fn () => $request->input('type') !== 'free'), Rule::when($request->input('type') === 'percent', 'max:100')],
        ], ['value.required' => 'Enter the discount amount or percent.']);

        return [
            ...$data,
            'shipping_zone_id' => $data['shipping_zone_id'] ?? null,
            'value' => $data['type'] === 'free' ? 0 : (float) $data['value'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'charge' => 'required|numeric|min:0',
            'delivery_time' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Ecom\Models\ShippingDiscount;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Support\EcomSettings;
use ME\Models\GeoLocation;

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
            'zones' => ShippingZone::withCount('orders')->with('areas:id,name,type')->orderBy('sort_order')->orderBy('id')->get(),
            'areaOptions' => $this->areaOptions(),
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
        [$data, $areas] = $this->validated($request);
        me_change_log('Delivery zone created: '.$data['name'], 'ecom.shipping.zone')->create(fn () => $this->saveZone(new ShippingZone, $data, $areas));

        return back()->with('success', 'Delivery zone added.');
    }

    public function updateZone(Request $request, ShippingZone $zone): RedirectResponse
    {
        [$data, $areas] = $this->validated($request, $zone);
        me_change_log('Delivery zone updated: '.$zone->name, 'ecom.shipping.zone')->watch($zone)->run(fn () => $this->saveZone($zone, $data, $areas));

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
     * Zone fields and its area ids. An area can belong to one zone only.
     *
     * @return array{0: array<string, mixed>, 1: array<int, int>}
     */
    private function validated(Request $request, ?ShippingZone $zone = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'charge' => 'required|numeric|min:0',
            'delivery_time' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'areas' => 'nullable|array',
            'areas.*' => ['integer', Rule::exists('geo_locations', 'id')->whereIn('type', ['division', 'district', 'upazila'])],
        ]);
        $areas = array_values(array_unique(array_map('intval', $data['areas'] ?? [])));

        $taken = GeoLocation::whereIn('geo_locations.id', $areas)
            ->join('ecom_shipping_zone_areas as zone_area', 'zone_area.geo_location_id', '=', 'geo_locations.id')
            ->join('ecom_shipping_zones as zone', 'zone.id', '=', 'zone_area.shipping_zone_id')
            ->when($zone, fn ($q) => $q->where('zone.id', '!=', $zone->id))
            ->get(['geo_locations.name', 'zone.name as zone_name']);
        if ($taken->isNotEmpty()) {
            throw ValidationException::withMessages(['areas' => $taken->map(fn ($area) => "{$area->name} is already in \"{$area->zone_name}\"")->implode(', ').'.']);
        }

        unset($data['areas']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');

        return [$data, $areas];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $areas
     */
    private function saveZone(ShippingZone $zone, array $data, array $areas): ShippingZone
    {
        return DB::transaction(function () use ($zone, $data, $areas) {
            if ($data['is_default']) {
                // One default zone: the one for areas no zone lists
                ShippingZone::where('is_default', true)->when($zone->exists, fn ($q) => $q->whereKeyNot($zone->id))->update(['is_default' => false]);
            }

            $zone->fill($data)->save();
            $zone->areas()->sync($areas);

            return $zone;
        });
    }

    /**
     * Every division, district and upazila for the zone's area picker, grouped by division.
     *
     * @return array<string, array<int, array{id: int, label: string}>>
     */
    private function areaOptions(): array
    {
        $places = GeoLocation::whereIn('type', ['division', 'district', 'upazila'])->orderBy('name')->get(['id', 'parent_id', 'type', 'name'])->keyBy('id');
        $groups = [];

        foreach ($places->where('type', 'division') as $division) {
            $groups[$division->name][] = ['id' => $division->id, 'label' => $division->name.' — whole division'];

            foreach ($places->where('parent_id', $division->id) as $district) {
                $groups[$division->name][] = ['id' => $district->id, 'label' => $district->name.' district (all thanas)'];

                foreach ($places->where('parent_id', $district->id) as $upazila) {
                    $groups[$division->name][] = ['id' => $upazila->id, 'label' => $upazila->name.', '.$district->name];
                }
            }
        }
        ksort($groups);

        return $groups;
    }
}

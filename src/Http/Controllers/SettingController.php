<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Support\EcomSettings;

/**
 * Store info, payment gateways and courier credentials (settings table, "ecom_" keys).
 */
class SettingController extends EcomController
{
    public const STORE_FIELDS = [
        'store_name' => 'Store Name',
        'store_tagline' => 'Tagline',
        'store_phone' => 'Phone',
        'store_email' => 'Email',
        'store_address' => 'Address',
        'store_hotline_hours' => 'Support Hours',
        'social_facebook' => 'Facebook URL',
        'social_instagram' => 'Instagram URL',
        'social_youtube' => 'YouTube URL',
        'social_tiktok' => 'TikTok URL',
        'social_whatsapp' => 'WhatsApp Number',
        'order_prefix' => 'Order Number Prefix',
        'invoice_footer' => 'Invoice Footer Note',
    ];

    public function __construct(private EcomSettings $settings, private CourierManager $couriers)
    {
        $this->middleware('authorization:ecom_setting.store')->only(['store', 'updateStore']);
        $this->middleware('authorization:ecom_setting.payment')->only(['payment', 'updatePayment']);
        $this->middleware('authorization:ecom_setting.courier')->only(['courier', 'updateCourier']);
    }

    public function store(): View
    {
        return view('ecom::settings.store', ['settings' => $this->settings, 'fields' => self::STORE_FIELDS]);
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $rules = array_fill_keys(array_keys(self::STORE_FIELDS), 'nullable|string|max:500');
        $rules['store_email'] = 'nullable|email|max:255';
        $rules['order_prefix'] = 'nullable|string|max:10|alpha_dash';
        $rules['store_logo'] = 'nullable|image|max:2048';
        $rules['store_favicon'] = 'nullable|image|max:512';
        $data = $request->validate($rules);

        foreach (['store_logo', 'store_favicon'] as $field) {
            $data[$field] = $this->replaceImage($request, $field, $this->settings->get($field), 'store');
        }

        $keys = array_keys($data);
        $before = $this->settings->snapshot($keys);
        $this->settings->set($data);
        me_change_log('Store info updated', 'ecom.settings.store')->record($before, $this->settings->snapshot($keys));

        return back()->with('success', 'Store info saved.');
    }

    public function payment(): View
    {
        return view('ecom::settings.payment', ['settings' => $this->settings, 'methods' => PaymentMethod::cases()]);
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $values = [];
        $secretKeys = [];

        foreach (PaymentMethod::cases() as $method) {
            $prefix = "payment_{$method->value}_";
            $values[$prefix.'enabled'] = $request->boolean($prefix.'enabled') ? '1' : '0';
            $values[$prefix.'instructions'] = $request->input($prefix.'instructions');

            if ($method->hasSandbox()) {
                $values[$prefix.'mode'] = $request->input($prefix.'mode') === 'live' ? 'live' : 'sandbox';
            }

            foreach ($method->credentialFields() as $field => $meta) {
                $values[$prefix.$field] = $request->input($prefix.$field);

                if ($meta['secret']) {
                    $secretKeys[] = $prefix.$field;
                }
            }
        }

        $request->validate(array_fill_keys(array_keys($values), 'nullable|string|max:5000'));

        if (! collect(PaymentMethod::cases())->contains(fn ($method) => $values["payment_{$method->value}_enabled"] === '1')) {
            return back()->withInput()->with('error', 'Keep at least one payment method enabled.');
        }

        $keys = array_keys($values);
        $before = $this->settings->snapshot($keys);
        $this->settings->set($values, $secretKeys);
        me_change_log('Payment settings updated', 'ecom.settings.payment')->record($before, $this->settings->snapshot($keys));

        return back()->with('success', 'Payment settings saved.');
    }

    public function courier(): View
    {
        return view('ecom::settings.courier', ['settings' => $this->settings, 'couriers' => $this->couriers->all()]);
    }

    public function updateCourier(Request $request): RedirectResponse
    {
        $values = [];
        $secretKeys = [];

        foreach ($this->couriers->all() as $key => $courier) {
            $prefix = "courier_{$key}_";
            $values[$prefix.'enabled'] = $request->boolean($prefix.'enabled') ? '1' : '0';
            $values[$prefix.'mode'] = $request->input($prefix.'mode') === 'sandbox' ? 'sandbox' : 'live';

            foreach ($courier->credentialFields() as $field => $meta) {
                $values[$prefix.$field] = $request->input($prefix.$field);

                if ($meta['secret']) {
                    $secretKeys[] = $prefix.$field;
                }
            }
        }

        $request->validate(array_fill_keys(array_keys($values), 'nullable|string|max:2000'));

        $keys = array_keys($values);
        $before = $this->settings->snapshot($keys);
        $this->settings->set($values, $secretKeys);
        me_change_log('Courier settings updated', 'ecom.settings.courier')->record($before, $this->settings->snapshot($keys));

        return back()->with('success', 'Courier settings saved.');
    }
}

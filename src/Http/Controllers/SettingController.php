<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Services\InvoiceService;
use ME\Ecom\Support\EcomSettings;
use ME\Models\Setting;

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
        'invoice_prefix' => 'Invoice Number Prefix',
        'invoice_accent' => 'Accent Colour',
        'invoice_paper' => 'Paper Size',
        'invoice_tax_label' => 'Tax ID Label',
        'invoice_tax_number' => 'Tax ID Number',
        'invoice_signature' => 'Signature Line',
        'invoice_notes' => 'Notes',
        'invoice_terms' => 'Terms & Conditions',
        'invoice_show_sku' => 'Show SKU Column',
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
        $rules['invoice_prefix'] = 'nullable|string|max:10|alpha_dash';
        $rules['invoice_accent'] = ['nullable', 'regex:~^#[0-9a-fA-F]{6}$~'];
        $rules['invoice_paper'] = ['nullable', Rule::in(array_keys(InvoiceService::PAPERS))];
        $rules['invoice_tax_label'] = 'nullable|string|max:30';
        $rules['invoice_tax_number'] = 'nullable|string|max:50';
        $rules['invoice_signature'] = 'nullable|string|max:60';
        $rules['invoice_notes'] = 'nullable|string|max:1000';
        $rules['invoice_terms'] = 'nullable|string|max:2000';
        $rules['invoice_show_sku'] = 'boolean';
        $data = Arr::except($request->validate($rules), ['store_logo', 'store_favicon']);
        $data['invoice_show_sku'] = $request->boolean('invoice_show_sku') ? '1' : '0';

        $keys = array_merge(array_keys($data), ['store_logo', 'store_favicon']);
        $before = $this->settings->snapshot($keys);
        $this->settings->set($data);

        // Logo and favicon are image settings in me_media (metheme) — read them with get_image('ecom_store_logo')
        foreach (['store_logo', 'store_favicon'] as $field) {
            if ($request->hasFile($field)) {
                Setting::setImage("ecom_{$field}", $request->file($field));
            } elseif ($request->input("{$field}_remove")) {
                Setting::removeImage("ecom_{$field}");
            }
        }
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

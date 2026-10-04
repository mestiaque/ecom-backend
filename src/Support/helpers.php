<?php

use ME\Ecom\Support\EcomSettings;

if (! function_exists('ecom_setting')) {
    /**
     * Shop setting (settings table, key "ecom_{$key}").
     */
    function ecom_setting(string $key, mixed $default = null): mixed
    {
        return app(EcomSettings::class)->get($key, $default);
    }
}

if (! function_exists('ecom_money')) {
    /**
     * Format an amount with the shop currency, e.g. "৳1,250.00".
     */
    function ecom_money(float|int|string|null $amount, bool $plain = false): string
    {
        $formatted = number_format((float) $amount, 2);

        return $plain
            ? config('ecom.currency_code').' '.$formatted
            : config('ecom.currency_symbol').$formatted;
    }
}

if (! function_exists('ecom_image')) {
    /**
     * Public URL of an image stored on the "public" disk, or a placeholder.
     */
    function ecom_image(?string $path, ?string $placeholder = null): ?string
    {
        if (blank($path)) {
            return $placeholder;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.ltrim($path, '/'));
    }
}

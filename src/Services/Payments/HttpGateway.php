<?php

namespace ME\Ecom\Services\Payments;

use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Support\EcomSettings;

/**
 * Shared settings helpers. Settings keys: ecom_payment_{key}_{field}; base URLs in config('ecom.payments.{key}').
 */
abstract class HttpGateway implements PaymentGateway
{
    public function __construct(protected EcomSettings $settings) {}

    public function isConfigured(): bool
    {
        if (! $this->settings->bool("payment_{$this->key()}_enabled")) {
            return false;
        }

        foreach (array_keys(PaymentMethod::from($this->key())->credentialFields()) as $field) {
            if (blank($this->credential($field))) {
                return false;
            }
        }

        return true;
    }

    public function isSandbox(): bool
    {
        return $this->settings->get("payment_{$this->key()}_mode", 'sandbox') !== 'live';
    }

    protected function credential(string $field): ?string
    {
        $secret = PaymentMethod::from($this->key())->credentialFields()[$field]['secret'] ?? false;
        $key = "payment_{$this->key()}_{$field}";

        return $secret ? $this->settings->secret($key) : $this->settings->get($key);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config("ecom.payments.{$this->key()}.".($this->isSandbox() ? 'sandbox' : 'live')), '/');
    }

    protected function amount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}

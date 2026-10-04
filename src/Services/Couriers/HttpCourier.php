<?php

namespace ME\Ecom\Services\Couriers;

use Illuminate\Http\Client\Response;
use ME\Ecom\Models\Order;
use ME\Ecom\Support\EcomSettings;

/**
 * Shared settings / HTTP helpers for API couriers. Settings keys: ecom_courier_{key}_{field}.
 */
abstract class HttpCourier implements CourierDriver
{
    public function __construct(protected EcomSettings $settings) {}

    public function label(): string
    {
        return config("ecom.couriers.{$this->key()}.label", ucfirst($this->key()));
    }

    public function orderFields(): array
    {
        return [];
    }

    public function isConfigured(): bool
    {
        foreach ($this->credentialFields() as $field => $meta) {
            if (blank($this->credential($field))) {
                return false;
            }
        }

        return $this->settings->bool("courier_{$this->key()}_enabled");
    }

    protected function credential(string $field): ?string
    {
        $secret = $this->credentialFields()[$field]['secret'] ?? false;
        $key = "courier_{$this->key()}_{$field}";

        return $secret ? $this->settings->secret($key) : $this->settings->get($key);
    }

    protected function baseUrl(): string
    {
        $mode = $this->settings->get("courier_{$this->key()}_mode", 'live');

        return rtrim(config("ecom.couriers.{$this->key()}.{$mode}"), '/');
    }

    /**
     * Amount the courier must collect from the customer.
     */
    protected function codAmount(Order $order): float
    {
        return max(0, round((float) $order->total - $order->paidAmount(), 2));
    }

    /**
     * @throws CourierException
     */
    protected function failIfError(Response $response, ?string $message = null): void
    {
        if ($response->failed() || $message) {
            $error = $message ?? $response->json('message') ?? $response->json('errors') ?? $response->body();
            throw new CourierException($this->label().': '.(is_array($error) ? json_encode($error) : $error));
        }
    }
}

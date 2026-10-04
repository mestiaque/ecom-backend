<?php

namespace ME\Ecom\Services\Couriers;

use ME\Ecom\Models\Order;

interface CourierDriver
{
    public function key(): string;

    public function label(): string;

    /**
     * Credential fields for the Couriers settings page.
     *
     * @return array<string, array{label: string, secret: bool}>
     */
    public function credentialFields(): array;

    /**
     * Extra per-order inputs shown in the "Send to courier" form.
     *
     * @return array<string, array{label: string, required: bool, help?: string}>
     */
    public function orderFields(): array;

    public function isConfigured(): bool;

    /**
     * Create the parcel at the courier.
     *
     * @param  array<string, mixed>  $options  values of orderFields()
     *
     * @throws CourierException
     */
    public function createParcel(Order $order, array $options = []): CourierResult;
}

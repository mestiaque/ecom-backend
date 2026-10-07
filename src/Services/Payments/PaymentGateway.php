<?php

namespace ME\Ecom\Services\Payments;

use Illuminate\Http\Request;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Transaction;

/**
 * An online payment gateway: send the customer to the gateway, then check the result when they come back.
 */
interface PaymentGateway
{
    /** Same value as the PaymentMethod enum (bkash, sslcommerz …) */
    public function key(): string;

    public function isConfigured(): bool;

    public function isSandbox(): bool;

    /**
     * Create the payment at the gateway and return the URL to send the customer to.
     *
     * @throws PaymentException
     */
    public function start(Order $order, Transaction $transaction, string $callbackUrl): string;

    /**
     * The customer is back from the gateway: confirm the payment with the gateway (never trust the request alone).
     */
    public function complete(Transaction $transaction, Request $request): PaymentResult;
}

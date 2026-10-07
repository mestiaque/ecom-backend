<?php

namespace ME\Ecom\Services\Payments;

/**
 * Outcome of a gateway callback, after the payment was checked with the gateway itself.
 */
final readonly class PaymentResult
{
    /**
     * @param  'success'|'failed'|'cancelled'  $status
     * @param  array<string, mixed>  $response  raw gateway answer (saved on the transaction)
     */
    public function __construct(
        public string $status,
        public ?string $trxId = null,
        public ?float $amount = null,
        public ?string $message = null,
        public array $response = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->status === 'success';
    }
}

<?php

namespace ME\Ecom\Services\Couriers;

class CourierResult
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public string $trackingId,
        public ?string $consignmentId = null,
        public array $response = [],
    ) {}
}

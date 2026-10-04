<?php

namespace ME\Ecom\Services\Couriers;

use Illuminate\Support\Facades\Http;
use ME\Ecom\Models\Order;

class SteadfastCourier extends HttpCourier
{
    public function key(): string
    {
        return 'steadfast';
    }

    public function credentialFields(): array
    {
        return [
            'api_key' => ['label' => 'API Key', 'secret' => false],
            'secret_key' => ['label' => 'Secret Key', 'secret' => true],
        ];
    }

    public function createParcel(Order $order, array $options = []): CourierResult
    {
        $response = Http::acceptJson()->timeout(30)
            ->withHeaders([
                'Api-Key' => $this->credential('api_key'),
                'Secret-Key' => $this->credential('secret_key'),
            ])
            ->post($this->baseUrl().'/create_order', [
                'invoice' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->customer_phone,
                'recipient_address' => trim($order->shipping_address.', '.$order->city, ', '),
                'cod_amount' => $this->codAmount($order),
                'note' => $options['note'] ?? $order->customer_note,
            ]);

        $consignment = $response->json('consignment');
        $this->failIfError($response, empty($consignment['tracking_code']) ? ($response->json('message') ?? 'No tracking code returned.') : null);

        return new CourierResult(
            trackingId: (string) $consignment['tracking_code'],
            consignmentId: isset($consignment['consignment_id']) ? (string) $consignment['consignment_id'] : null,
            response: $response->json() ?? [],
        );
    }
}

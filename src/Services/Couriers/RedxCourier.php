<?php

namespace ME\Ecom\Services\Couriers;

use Illuminate\Support\Facades\Http;
use ME\Ecom\Models\Order;

class RedxCourier extends HttpCourier
{
    public function key(): string
    {
        return 'redx';
    }

    public function credentialFields(): array
    {
        return [
            'api_token' => ['label' => 'API Access Token', 'secret' => true],
        ];
    }

    public function orderFields(): array
    {
        return [
            'delivery_area' => ['label' => 'RedX Area Name', 'required' => true],
            'delivery_area_id' => ['label' => 'RedX Area ID', 'required' => true],
            'parcel_weight' => ['label' => 'Weight (grams)', 'required' => false],
        ];
    }

    public function createParcel(Order $order, array $options = []): CourierResult
    {
        $response = Http::acceptJson()->timeout(30)
            ->withHeaders(['API-ACCESS-TOKEN' => 'Bearer '.$this->credential('api_token')])
            ->post($this->baseUrl().'/parcel', [
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'delivery_area' => $options['delivery_area'] ?? '',
                'delivery_area_id' => (int) ($options['delivery_area_id'] ?? 0),
                'customer_address' => trim($order->shipping_address.', '.$order->city, ', '),
                'merchant_invoice_id' => $order->order_number,
                'cash_collection_amount' => (string) $this->codAmount($order),
                'parcel_weight' => (int) ($options['parcel_weight'] ?? 500) ?: 500,
                'instruction' => $options['note'] ?? $order->customer_note,
                'value' => (int) round((float) $order->total),
            ]);

        $trackingId = $response->json('tracking_id');
        $this->failIfError($response, $trackingId ? null : ($response->json('message') ?? 'No tracking id returned.'));

        return new CourierResult((string) $trackingId, null, $response->json() ?? []);
    }
}

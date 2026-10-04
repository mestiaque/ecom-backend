<?php

namespace ME\Ecom\Services\Couriers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ME\Ecom\Models\Order;

class PathaoCourier extends HttpCourier
{
    public function key(): string
    {
        return 'pathao';
    }

    public function credentialFields(): array
    {
        return [
            'client_id' => ['label' => 'Client ID', 'secret' => false],
            'client_secret' => ['label' => 'Client Secret', 'secret' => true],
            'username' => ['label' => 'Merchant Email', 'secret' => false],
            'password' => ['label' => 'Merchant Password', 'secret' => true],
            'store_id' => ['label' => 'Store ID', 'secret' => false],
        ];
    }

    public function orderFields(): array
    {
        return [
            'recipient_city' => ['label' => 'Pathao City ID', 'required' => false, 'help' => 'Optional — Pathao detects it from the address when empty'],
            'recipient_zone' => ['label' => 'Pathao Zone ID', 'required' => false],
            'item_weight' => ['label' => 'Weight (kg)', 'required' => false],
        ];
    }

    public function createParcel(Order $order, array $options = []): CourierResult
    {
        $payload = array_filter([
            'store_id' => (int) $this->credential('store_id'),
            'merchant_order_id' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => trim($order->shipping_address.', '.$order->city, ', '),
            'recipient_city' => filled($options['recipient_city'] ?? null) ? (int) $options['recipient_city'] : null,
            'recipient_zone' => filled($options['recipient_zone'] ?? null) ? (int) $options['recipient_zone'] : null,
            'delivery_type' => 48, // normal delivery
            'item_type' => 2,      // parcel
            'special_instruction' => $options['note'] ?? $order->customer_note,
            'item_quantity' => (int) $order->items->sum('quantity'),
            'item_weight' => (float) ($options['item_weight'] ?? 0.5) ?: 0.5,
            'amount_to_collect' => (int) round($this->codAmount($order)),
        ], fn ($value) => $value !== null);

        $response = Http::acceptJson()->timeout(30)->withToken($this->token())
            ->post($this->baseUrl().'/aladdin/api/v1/orders', $payload);

        $consignmentId = $response->json('data.consignment_id');
        $this->failIfError($response, $consignmentId ? null : ($response->json('message') ?? 'No consignment id returned.'));

        return new CourierResult((string) $consignmentId, (string) $consignmentId, $response->json() ?? []);
    }

    /**
     * @throws CourierException
     */
    private function token(): string
    {
        $cacheKey = 'ecom_pathao_token_'.md5($this->baseUrl().$this->credential('client_id'));

        if ($token = Cache::get($cacheKey)) {
            return $token;
        }

        $response = Http::acceptJson()->timeout(30)->post($this->baseUrl().'/aladdin/api/v1/issue-token', [
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'grant_type' => 'password',
            'username' => $this->credential('username'),
            'password' => $this->credential('password'),
        ]);

        $token = $response->json('access_token');
        $this->failIfError($response, $token ? null : 'Could not get access token. Check the credentials.');

        Cache::put($cacheKey, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 300)));

        return $token;
    }
}

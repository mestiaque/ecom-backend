<?php

namespace ME\Ecom\Services\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Transaction;

/**
 * SSLCommerz (cards, mobile banking, internet banking): init session → customer pays → gateway posts back → validate by val_id.
 */
class SslcommerzGateway extends HttpGateway
{
    public function key(): string
    {
        return 'sslcommerz';
    }

    public function start(Order $order, Transaction $transaction, string $callbackUrl): string
    {
        $order->loadMissing('items');

        $response = Http::asForm()->acceptJson()->connectTimeout(20)->timeout(40)->post($this->baseUrl().'/gwprocess/v4/api.php', [
            'store_id' => $this->credential('store_id'),
            'store_passwd' => $this->credential('store_password'),
            'total_amount' => $this->amount((float) $transaction->amount),
            'currency' => 'BDT',
            'tran_id' => $this->tranId($order, $transaction),
            'success_url' => $callbackUrl.'/success',
            'fail_url' => $callbackUrl.'/fail',
            'cancel_url' => $callbackUrl.'/cancel',
            'cus_name' => $order->customer_name,
            'cus_email' => $order->customer_email ?: 'customer@example.com',
            'cus_phone' => $order->customer_phone,
            'cus_add1' => Str::limit((string) $order->shipping_address, 100, ''),
            'cus_city' => Str::limit((string) ($order->city ?: 'Dhaka'), 50, ''),
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'num_of_item' => $order->items->sum('quantity'),
            'product_name' => Str::limit($order->items->pluck('product_name')->implode(', ') ?: 'Order '.$order->order_number, 250, ''),
            'product_category' => 'General',
            'product_profile' => 'general',
            'value_a' => $order->order_number,
        ])->json() ?? [];

        if (($response['status'] ?? null) !== 'SUCCESS' || empty($response['GatewayPageURL'])) {
            throw new PaymentException('SSLCommerz: '.($response['failedreason'] ?? 'the payment could not be started.'));
        }

        $transaction->forceFill(['gateway_response' => [...(array) $transaction->gateway_response, 'session_key' => $response['sessionkey'] ?? null]])->save();

        return $response['GatewayPageURL'];
    }

    public function complete(Transaction $transaction, Request $request): PaymentResult
    {
        $result = $request->route('result');

        if ($result === 'cancel') {
            return new PaymentResult('cancelled', message: 'You cancelled the card payment.', response: $request->except(['store_passwd']));
        }

        $valId = (string) $request->input('val_id');

        if ($result !== 'success' || $valId === '') {
            return new PaymentResult('failed', message: 'SSLCommerz: '.($request->input('error') ?: 'the payment failed.'), response: $request->except(['store_passwd']));
        }

        // Never trust the posted fields: ask SSLCommerz about this val_id
        $response = Http::acceptJson()->connectTimeout(20)->timeout(40)->get($this->baseUrl().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => $this->credential('store_id'),
            'store_passwd' => $this->credential('store_password'),
            'format' => 'json',
        ])->json() ?? [];

        $order = $transaction->order;
        $valid = in_array($response['status'] ?? null, ['VALID', 'VALIDATED'], true)
            && ($response['tran_id'] ?? null) === $this->tranId($order, $transaction)
            && abs((float) ($response['amount'] ?? 0) - (float) $transaction->amount) < 0.01;

        if (! $valid) {
            return new PaymentResult('failed', message: 'SSLCommerz could not confirm this payment.', response: $response);
        }

        return new PaymentResult('success', $response['bank_tran_id'] ?? $valId, (float) $response['amount'], response: $response);
    }

    private function tranId(Order $order, Transaction $transaction): string
    {
        return $order->order_number.'-'.$transaction->id;
    }
}

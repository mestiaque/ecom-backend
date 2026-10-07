<?php

namespace ME\Ecom\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Transaction;
use Throwable;

/**
 * bKash Tokenized Checkout (v1.2.0-beta): grant token → create payment → customer pays on bKash → execute payment.
 */
class BkashGateway extends HttpGateway
{
    public function key(): string
    {
        return 'bkash';
    }

    public function start(Order $order, Transaction $transaction, string $callbackUrl): string
    {
        $response = $this->api()->post($this->baseUrl().'/tokenized/checkout/create', [
            'mode' => '0011',
            'payerReference' => $order->customer_phone ?: $order->order_number,
            'callbackURL' => $callbackUrl,
            'amount' => $this->amount((float) $transaction->amount),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $order->order_number.'-'.$transaction->id,
        ])->json() ?? [];

        if (($response['statusCode'] ?? null) !== '0000' || empty($response['bkashURL'])) {
            throw new PaymentException('bKash: '.($response['statusMessage'] ?? $response['errorMessage'] ?? 'the payment could not be started.'));
        }

        $transaction->forceFill(['gateway_response' => [...(array) $transaction->gateway_response, 'payment_id' => $response['paymentID']]])->save();

        return $response['bkashURL'];
    }

    public function complete(Transaction $transaction, Request $request): PaymentResult
    {
        $paymentId = (string) $request->query('paymentID');
        $status = (string) $request->query('status');

        if ($paymentId === '' || $paymentId !== ($transaction->gateway_response['payment_id'] ?? null)) {
            return new PaymentResult('failed', message: 'bKash returned an unknown payment.');
        }

        if ($status !== 'success') {
            return new PaymentResult($status === 'cancel' ? 'cancelled' : 'failed', message: $status === 'cancel' ? 'You cancelled the bKash payment.' : 'The bKash payment failed.', response: $request->query());
        }

        try {
            $response = $this->api()->post($this->baseUrl().'/tokenized/checkout/execute', ['paymentID' => $paymentId])->json() ?? [];
        } catch (Throwable) {
            $response = [];
        }

        // Execute timed out or was already done: ask bKash for the final state
        if (! isset($response['transactionStatus'])) {
            $response = $this->api()->post($this->baseUrl().'/tokenized/checkout/payment/status', ['paymentID' => $paymentId])->json() ?? [];
        }

        if (($response['transactionStatus'] ?? null) === 'Completed' && ! empty($response['trxID'])) {
            return new PaymentResult('success', $response['trxID'], (float) $response['amount'], response: $response);
        }

        return new PaymentResult('failed', message: 'bKash: '.($response['statusMessage'] ?? 'the payment was not completed.'), response: $response);
    }

    private function api(): PendingRequest
    {
        return Http::acceptJson()->asJson()->connectTimeout(20)->timeout(40)->withHeaders([
            'Authorization' => $this->token(),
            'X-APP-Key' => (string) $this->credential('app_key'),
        ]);
    }

    /**
     * id_token is valid for one hour; keep it for 50 minutes.
     *
     * @throws PaymentException
     */
    private function token(): string
    {
        $cacheKey = 'ecom_bkash_token_'.md5($this->baseUrl().$this->credential('app_key'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::acceptJson()->asJson()->connectTimeout(20)->timeout(40)->withHeaders([
                'username' => (string) $this->credential('username'),
                'password' => (string) $this->credential('password'),
            ])->post($this->baseUrl().'/tokenized/checkout/token/grant', [
                'app_key' => $this->credential('app_key'),
                'app_secret' => $this->credential('app_secret'),
            ])->json() ?? [];

            return $response['id_token'] ?? throw new PaymentException('bKash: '.($response['statusMessage'] ?? $response['msg'] ?? 'login failed, check the credentials.'));
        });
    }
}

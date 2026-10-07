<?php

namespace ME\Ecom\Services\Payments;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Enums\PaymentStatus;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Transaction;
use ME\Ecom\Services\OrderService;
use Throwable;

/**
 * Online payments. Each try is a "pending" row in ecom_transactions; it becomes "success" only after
 * the gateway itself confirmed the payment, and then the order's payment status is refreshed.
 */
class PaymentManager
{
    /** @var array<int, class-string<PaymentGateway>> */
    private array $gateways = [
        BkashGateway::class,
        SslcommerzGateway::class,
    ];

    public function __construct(private OrderService $orders) {}

    /**
     * The gateway of a payment method when it is switched on and has credentials, otherwise null
     * (cash on delivery, Nagad without API keys … are paid / confirmed by hand).
     */
    public function gateway(PaymentMethod|string $method): ?PaymentGateway
    {
        $key = $method instanceof PaymentMethod ? $method->value : $method;

        foreach ($this->gateways as $class) {
            $gateway = app($class);

            if ($gateway->key() === $key) {
                return $gateway->isConfigured() ? $gateway : null;
            }
        }

        return null;
    }

    public function canPayOnline(Order $order): bool
    {
        return $order->payment_status === PaymentStatus::Unpaid
            && ! in_array($order->status->value, ['cancelled', 'returned'], true)
            && $this->dueAmount($order) > 0
            && $this->gateway($order->payment_method) !== null;
    }

    public function dueAmount(Order $order): float
    {
        return max(0, round((float) $order->total - $order->paidAmount(), 2));
    }

    /**
     * Start a payment and return the gateway URL for the customer.
     *
     * @param  Closure(Transaction, string): string  $callbackUrl  builds the return URL from the transaction and its secret token
     *
     * @throws PaymentException
     */
    public function start(Order $order, Closure $callbackUrl): string
    {
        $gateway = $this->gateway($order->payment_method);

        if (! $gateway || ! $this->canPayOnline($order)) {
            throw new PaymentException('This order cannot be paid online.');
        }

        // An older try that was never finished (customer closed the gateway page) is replaced by this one
        $order->transactions()->where('status', 'pending')->update(['status' => 'failed', 'note' => 'Replaced by a new payment try']);

        $token = Str::random(40);
        $transaction = $order->transactions()->create([
            'type' => 'payment',
            'method' => $gateway->key(),
            'amount' => $this->dueAmount($order),
            'status' => 'pending',
            'gateway_response' => ['token' => $token, 'sandbox' => $gateway->isSandbox()],
            'note' => 'Online payment started',
        ]);

        try {
            return $gateway->start($order, $transaction, $callbackUrl($transaction, $token));
        } catch (Throwable $e) {
            $transaction->forceFill(['status' => 'failed', 'note' => Str::limit('Could not start: '.$e->getMessage(), 250)])->save();

            throw $e instanceof PaymentException ? $e : new PaymentException('The payment gateway is not reachable. Please try again.', previous: $e);
        }
    }

    public function tokenMatches(Transaction $transaction, string $token): bool
    {
        return hash_equals((string) ($transaction->gateway_response['token'] ?? ''), $token);
    }

    /**
     * The customer is back from the gateway. Safe to call twice: a finished transaction is not checked again.
     */
    public function complete(Transaction $transaction, Request $request): PaymentResult
    {
        return DB::transaction(function () use ($transaction, $request) {
            $transaction = Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($transaction->status !== 'pending') {
                return new PaymentResult($transaction->status === 'success' ? 'success' : 'failed', $transaction->trx_id, (float) $transaction->amount);
            }

            $gateway = $this->gateway($transaction->method);
            $result = $gateway
                ? $gateway->complete($transaction, $request)
                : new PaymentResult('failed', message: 'This payment method is switched off.');

            $transaction->forceFill([
                'status' => $result->isPaid() ? 'success' : 'failed',
                'trx_id' => $result->trxId,
                'amount' => $result->amount ?? $transaction->amount,
                'gateway_response' => [...(array) $transaction->gateway_response, 'result' => $result->response],
                'note' => $result->isPaid() ? 'Paid online' : Str::limit(($result->status === 'cancelled' ? 'Cancelled: ' : 'Failed: ').$result->message, 250),
            ])->save();

            $order = $transaction->order;

            if ($result->isPaid()) {
                $this->orders->refreshPaymentStatus($order);
                $this->orders->addNote($order, PaymentMethod::from($transaction->method)->label()." payment received: {$transaction->amount} BDT (TrxID {$result->trxId})"
                    .(($transaction->gateway_response['sandbox'] ?? false) ? ' — sandbox test payment' : ''));
            }

            return $result;
        });
    }
}

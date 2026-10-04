<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Transaction;
use ME\Ecom\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_payment.view');
    }

    public function index(Request $request): View|StreamedResponse
    {
        $filters = $request->only(['search', 'method', 'type', 'status', 'from', 'to']);
        $query = Transaction::with(['order', 'user'])->filter($filters)->latest('id');

        if ($request->query('export') === 'csv') {
            $rows = function () use ($query) {
                foreach ($query->lazy() as $trx) {
                    yield [$trx->created_at->format('Y-m-d H:i'), $trx->order?->order_number, ucfirst($trx->type), $trx->method, $trx->amount, $trx->trx_id, $trx->status, $trx->note, $trx->user?->name];
                }
            };

            return Csv::download('transactions-'.now()->format('Y-m-d').'.csv', ['Date', 'Order', 'Type', 'Method', 'Amount', 'Trx ID', 'Status', 'Note', 'By'], $rows());
        }

        $totals = Transaction::filter($filters)->where('status', 'success')
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as received, COALESCE(SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END), 0) as refunded")
            ->first();

        return view('ecom::transactions.index', [
            'transactions' => $query->paginate($this->perPage())->withQueryString(),
            'totals' => $totals,
        ]);
    }
}

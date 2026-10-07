<?php

namespace ME\Ecom\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use ME\Ecom\Models\Order;
use ME\Ecom\Support\EcomSettings;
use ME\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

/**
 * Professional invoices for one or many orders — printable HTML and A4 / Letter / A5 PDF.
 * Look and texts come from Shop Settings → Store Info → Invoice (settings ecom_invoice_*).
 */
class InvoiceService
{
    /** Bulk printing limit, keeps one PDF reasonable */
    public const MAX_ORDERS = 100;

    public const PAPERS = ['a4' => 'A4', 'letter' => 'Letter', 'a5' => 'A5'];

    public function __construct(private EcomSettings $settings) {}

    /**
     * "INV-000123" from order "ORD-000123" (the number part of the order number).
     */
    public function number(Order $order): string
    {
        $digits = preg_replace('~^\D+~', '', (string) $order->order_number) ?: (string) $order->id;

        return $this->settings->get('invoice_prefix', 'INV-').$digits;
    }

    /**
     * @return array{accent: string, paper: string, tax_label: ?string, tax_number: ?string, notes: ?string, terms: ?string, signature: ?string, footer: string, show_sku: bool}
     */
    public function options(): array
    {
        $accent = (string) $this->settings->get('invoice_accent', '');

        return [
            'accent' => preg_match('~^#[0-9a-fA-F]{6}$~', $accent) ? $accent : '#1f3a5f',
            'paper' => array_key_exists($paper = (string) $this->settings->get('invoice_paper', 'a4'), self::PAPERS) ? $paper : 'a4',
            'tax_label' => $this->settings->get('invoice_tax_label'),
            'tax_number' => $this->settings->get('invoice_tax_number'),
            'notes' => $this->settings->get('invoice_notes'),
            'terms' => $this->settings->get('invoice_terms'),
            'signature' => $this->settings->get('invoice_signature', 'Authorized Signature'),
            'footer' => (string) $this->settings->get('invoice_footer', 'Thank you for shopping with us!'),
            'show_sku' => $this->settings->bool('invoice_show_sku', true),
        ];
    }

    /**
     * Printable page (browser). $actions = buttons shown above the invoice, e.g. ['pdf' => url].
     *
     * @param  Collection<int, Order>|Order  $orders
     * @param  array<string, string>  $actions
     */
    public function html(Collection|Order $orders, array $actions = []): string
    {
        return view('ecom::invoices.document', $this->data($orders, pdf: false) + ['actions' => $actions])->render();
    }

    /**
     * @param  Collection<int, Order>|Order  $orders
     */
    public function download(Collection|Order $orders, ?string $filename = null): Response
    {
        $data = $this->data($orders, pdf: true);

        return Pdf::loadView('ecom::invoices.document', $data + ['actions' => []])
            ->setPaper($data['options']['paper'])
            ->setOption('isFontSubsettingEnabled', true) // embed only the used characters: ~60 KB instead of ~900 KB
            ->download($filename ?? $this->filename($data['orders']));
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    public function filename(Collection $orders): string
    {
        return $orders->count() === 1
            ? 'invoice-'.$this->number($orders->first()).'.pdf'
            : 'invoices-'.now()->format('Y-m-d-His').'.pdf';
    }

    /**
     * @param  Collection<int, Order>|Order  $orders
     * @return array<string, mixed>
     */
    private function data(Collection|Order $orders, bool $pdf): array
    {
        $orders = ($orders instanceof Order ? collect([$orders]) : $orders)
            ->each->loadMissing(['items', 'shippingZone', 'transactions']);

        return [
            'orders' => $orders,
            'pdf' => $pdf,
            'options' => $this->options(),
            'invoices' => $this,
            'store' => [
                'name' => $this->settings->get('store_name', get_setting('app_name', config('app.name'))),
                'tagline' => $this->settings->get('store_tagline'),
                'address' => $this->settings->get('store_address'),
                'phone' => $this->settings->get('store_phone'),
                'email' => $this->settings->get('store_email'),
                'logo' => $this->logo($pdf),
            ],
        ];
    }

    /**
     * dompdf reads a local file (no HTTP); the browser needs a URL.
     */
    private function logo(bool $pdf): ?string
    {
        $logo = Setting::image('ecom_store_logo');

        if (! $logo) {
            return null;
        }

        if (! $pdf) {
            return $logo->url();
        }

        $path = $logo->absolutePath();

        return is_file($path) ? $path : null;
    }
}

# Ecom Package

E-commerce admin panel for Laravel, built on **mestiaque/metheme** (layout, login, roles & permissions, activity log).
All tables are prefixed `ecom_`; settings are stored in metheme's `settings` table with keys prefixed `ecom_`.

## Installation

```bash
composer require mestiaque/ecom
php artisan migrate --path=vendor/mestiaque/ecom/src/database/migrations
php artisan storage:link
php artisan db:seed --class="ME\Ecom\database\seeders\EcomSeeder"        # delivery zones, warranties, policy pages, defaults
php artisan db:seed --class="ME\Ecom\database\seeders\EcomDemoSeeder"    # optional demo shop (see below)
php artisan metheme:media-import                                        # only when upgrading: move old image columns into me_media
```

The admin panel lives under metheme's prefix (`METHEME_ROUTE_PREFIX`, default `admin`): the **Dashboard** is the admin home at `/admin` (route `ecom.dashboard`, chart data `ecom.dashboard.chart` = `/admin/sales-chart`).
metheme has no dashboard page of its own. ecom's Dashboard owns `/admin` (metheme then skips its own `/admin` redirect),
and login goes there too (`me_settings.home_route` = `ecom.dashboard`, set in `EcomServiceProvider` unless `METHEME_HOME_ROUTE` is set).
To show metheme's user/login/activity numbers on it, add `@include('me::widgets.system-overview')` (optionally with
`['sections' => ['cards', 'chart', 'roles', 'activity', 'links']]`).
Route names start with `ecom.` (`ecom.orders.index`, `ecom.products.edit`, …).

## Modules

| Menu | What it does |
|---|---|
| Shop Dashboard | Today / month sales, status counts, daily/weekly/monthly chart, top sellers, low stock, new customers |
| Orders | Filters, status flow (Pending → Confirmed → Processing → Shipped → Delivered / Cancelled / Returned; **Shipped needs a courier + tracking ID first**), comments, invoice print + PDF, courier booking, payments & refunds |
| Transactions | All payments and refunds, CSV export |
| Customers | List, profile, order history, lifetime value, block/unblock |
| Products | Variants from attributes (own SKU, price, stock, image), multiple images (sortable, main image) with background thumbnails, active/hidden, low-stock alert, CSV import/export |
| Categories / Brands | Nested categories with image & banner, brands with logo |
| Attributes | Variant attributes (Color with swatches, Size, Storage, Material, …) and their values |
| Warranties | Warranty master data (number + day/month/year, stored in days), picked on each product |
| Order tracking | Public `/track-order` page: customer enters order number + phone and sees the order's progress |
| Reviews | Approve / hide / delete |
| Coupons / Flash Sale | Percent or fixed coupons with min order, expiry, usage limits; time-boxed campaigns |
| Website | Homepage slider/promo banners, static pages, store info, logo, social links |
| Shop Settings | Delivery zones & charges, free-shipping limit, payment gateways (COD, bKash, Nagad, SSLCommerz), couriers (Steadfast, Pathao, RedX) |
| Shop Reports | Sales, product-wise and customer reports with CSV export |

Stock is taken out when an order is placed (`OrderService::place()`) and returned automatically when it is cancelled or returned.
COD orders are marked paid when delivered.

## Product variants (attributes)

Variants are built from **attributes** instead of fixed size/colour columns, so any option can be added later.

1. **Catalog → Attributes** (`/admin/attributes`): create an attribute (`Color`, `Size`, `Storage`, `Material`, …) and its values.
   Display type *Color swatch* gives each value a colour (`#RRGGBB`). Drag values to set their order; the attribute's
   *Sort Order* decides the order in variant names (Color before Size).
2. On a product, turn on **This product has variants**, pick values per attribute and click **Generate variants** —
   one row per combination. Each row has its own SKU (auto-filled from the product SKU), price, discount price,
   stock, image (after the product's images are saved) and active switch. *Set for all rows* fills price/stock at once.
3. Product stock = total of its variants. Order items keep the variant name (`Color: Black / Size: M`).

Rules (checked on save): every variant has one value of each attribute, all variants use the same attributes,
no combination repeats. A value used by a variant cannot be deleted.

Tables: `ecom_attributes`, `ecom_attribute_values`, `ecom_product_variants` (`media_id` = one of the product's gallery images),
`ecom_product_variant_values` (variant ↔ value).

```php
$variant->label;            // "Color: Red / Size: M"
$variant->orderedValues();  // AttributeValue models in attribute order
$variant->selling_price;    // own discount/price, else the product's
$product->finalPrice($variant); // after a running campaign
```

## Warranty

**Catalog → Warranties** (`/admin/warranties`) holds the warranty master data. Enter a number and pick
**Day / Month / Year**; the days are filled in automatically (1 month = 30, 1 year = 365) and stored in
`ecom_warranties.days`. The days box can be changed by hand (e.g. 366). Pick a warranty on the product form.

- Each order item keeps the warranty it was sold with (`ecom_order_items.warranty_label`, `warranty_days`),
  so changing a warranty later does not change old orders.
- The warranty runs from the **delivery date**: the admin order page, invoice and tracking page show
  "valid until 10 Jan 2027" (or "starts on delivery").
- A warranty used by products cannot be deleted.

```php
$product->warranty;          // Warranty model (name, duration, duration_unit, days)
$warranty->period;           // "6 Months"
Warranty::daysFor(6, 'month'); // 180
$item->warrantyEndsAt();     // Carbon date, null until delivered
```

## Order tracking (customers)

- `/track-order` (no login): the customer enters the **order number** and the **phone number** they ordered with
  (`+880`, spaces and dashes are ignored). Max 10 tries per minute.
- On a match they are sent to a **signed link** valid for 30 days (`/track-order/ORD-000123?signature=…`),
  which shows the status progress (Placed → Confirmed → Packed → On the way → Delivered), status history,
  courier + tracking ID with a link to the courier's site, items with warranty, totals and payment status.
- Admin comments and status-note texts are never shown; the phone number is masked.
- The admin order page has a **Customer Tracking Link** box with copy / open buttons, to send by SMS / WhatsApp.

```php
$order->trackingPageUrl();   // signed customer tracking link (30 days)
$order->trackingUrl();       // courier's own tracking page
```

Routes: `ecom.track.form`, `ecom.track.submit`, `ecom.track.show` (outside the admin prefix).

## Images (metheme media library)

Every image — product gallery, category image/banner, brand logo, banner, campaign banner, customer photo,
store logo/favicon — is a row in metheme's **`me_media`** table, attached with metheme's `HasMedia` trait.
There are no image columns in the `ecom_*` tables. See metheme's readme for the full API.

| Model | Collection | Accessor |
|---|---|---|
| `Product` | `gallery` (many, sortable, first = main) | `$product->images`, `$product->primaryImage`, `$product->thumbnail` |
| `ProductVariant` | — (`media_id` points at a gallery image) | `$variant->image` |
| `Category` | `image`, `banner` | `$category->image_url`, `$category->banner_url` |
| `Brand` | `logo` | `$brand->logo_url` |
| `Banner` | `image` | `$banner->image_url` |
| `Campaign` | `banner` | `$campaign->banner_url` |
| `Customer` | `avatar` | `$customer->avatar_url` |
| Store settings | settings `ecom_store_logo`, `ecom_store_favicon` | `get_image('ecom_store_logo')` |

- Forms use `@include('me::components.media-input', [...])`; controllers call `$model->syncMediaFromRequest($request, 'collection')`.
- Thumbnails (webp, `thumb` conversion) are made in the background by metheme's `GenerateMediaConversions` job.
  With `QUEUE_CONNECTION=sync` it runs after the response is sent; otherwise run `php artisan queue:work`.
  Until it has run, the full image is shown.
- Eager load `->with('media')` in lists (`Product` lists use `primaryImage`).
- Deleting a record moves its files to the Media Library trash.

```bash
php artisan metheme:media-conversions --force   # remake thumbnails, e.g. after changing a size
```

## Invoices

One professional design (`ecom::invoices.document`, built by `ME\Ecom\Services\InvoiceService`) for print and PDF:
brand bar in the accent colour, logo and store details with the tax ID, invoice / order number, Bill To / Ship To / Delivery
(courier + tracking), items with SKU, variant and warranty, payment information with gateway TrxIDs, totals with Paid and
Balance Due, notes, terms, a PAID / UNPAID / REFUNDED / CANCELLED stamp and a signature line. One A4 page for a normal
order; the PDF footer is repeated on every page; fonts are subset (~40 KB per PDF).

| Where | What |
|---|---|
| Order page / order list | **Print Invoice** and **PDF** (permission `ecom_order.invoice`) |
| Order list | Tick orders → **Print Invoices** / **PDF**: all of them in one document, one invoice per page (max 100) |
| Storefront | Order result page, My Orders → order, tracking page: **Invoice** / **Download PDF** (signed link, valid 30 days) |

Settings: Shop Settings → Store Info → **Invoice** — number prefix (`INV-` + the number part of the order number:
ORD-000123 → INV-000123), accent colour, paper (A4 / Letter / A5), tax ID label + number (e.g. BIN), signature line,
notes, terms & conditions, SKU column on/off. The footer note and logo come from the same page. **Preview** opens the
latest order's invoice.

```php
$invoices = app(InvoiceService::class);
$invoices->number($order);                    // INV-000123
$invoices->html($orders, ['Download PDF' => $url]); // printable page, toolbar buttons
$invoices->download($orderOrOrders);          // PDF download response
```

## Online payments (bKash, SSLCommerz)

`ME\Ecom\Services\Payments\PaymentManager` sends the customer to the gateway and confirms the result with the
gateway itself — a payment is never trusted from the browser alone.

| Method | How it is paid |
|---|---|
| Cash on Delivery | Rider collects the cash (marked paid on delivery) |
| bKash | Tokenized Checkout: create payment → customer pays on the bKash page → **execute** confirms it (`BkashGateway`) |
| Card (SSLCommerz) | Hosted payment page → gateway posts back → **validation API** checks `val_id`, `tran_id` and amount (`SslcommerzGateway`) |
| Nagad | No public sandbox keys yet: the customer types the transaction ID, the admin checks it |

- A gateway is used only when the method is enabled **and** all its credentials are filled (Shop Settings → Payment).
  Mode `sandbox` = test payments, no real money; base URLs in `config('ecom.payments')`.
- Each try is a `pending` row in `ecom_transactions` (`gateway_response` keeps the gateway ids, a secret return token and
  the gateway answer). Confirmed → `success` + gateway TrxID, the order's payment status becomes **Paid** and an order
  note is added ("… sandbox test payment"). Failed / cancelled → `failed`; the order stays **Unpaid**. Starting a new try
  marks an unfinished one as failed. Calling the result twice does not check the gateway twice.
- Sandbox credentials in the demo data (public test accounts):
  - bKash: app key `4f6o0cjiki2rfm34kfdadl1eqq`, username `sandboxTokenizedUser02` — test wallet `01929918378`, OTP `123456`, PIN `12121`
    (the often quoted wallet `01770618575` is locked on the sandbox).
  - SSLCommerz: store `testbox` / `qwerty` — card `4111 1111 1111 1111`, any future expiry, CVV `111`, then **Success** on the test bank page.
- Locally over plain `http`, Chrome warns before the SSLCommerz (https) page posts back ("The information you're about to
  submit is not secure") — click **Send anyway**. On a site with https this does not happen.

```php
$payments = app(PaymentManager::class);
$payments->gateway('bkash');           // configured gateway or null
$payments->canPayOnline($order);       // unpaid, open, has a gateway
$payments->start($order, fn ($transaction, $token) => route('my.callback', [$transaction, $token])); // gateway URL
$payments->complete($transaction, $request); // PaymentResult (status, trxId, amount, message)
```

## Delivery charges

Set in the admin panel; worked out by `ME\Ecom\Services\ShippingCalculator` (checkout and `OrderService` both use it):

1. **Zone charge** — Shop Settings → Shipping → Delivery Zones.
2. **+ product extra / less charge × quantity** — Product → Delivery (`delivery_charge_adjustment`, negative = less).
3. **Free** when every product in the order has **Free delivery** (`free_delivery`); a free item in a mixed order adds nothing.
4. **− order-amount discount** — Shipping → Delivery Discounts: "subtotal at least X → free / % off / amount off", for all zones or one zone. The highest rule reached is used (a zone's own rule wins a tie). Never below zero.

The order keeps the final `shipping_charge` and the `shipping_discount` that was taken off; both show on the order page and invoice. The old "free shipping over X" setting was moved into a discount rule by migration `2026_10_06_000001_add_delivery_rules`.

```php
app(ShippingCalculator::class)->quote($zone, [['product' => $product, 'quantity' => 2]], $subtotal);
// ['base', 'adjustment', 'before_discount', 'discount', 'charge', 'all_free', 'rule']
```

## FAQ

**Website → FAQ** (`ecom.faqs.*`, permission `ecom_content.faq`): question, rich-text answer, category (empty = "General"), order and published switch. The storefront shows published entries on `/faq`, grouped by category in "Order" sequence. Demo entries: `app(\ME\Ecom\database\seeders\EcomDemoSeeder::class)->faqs()` (safe to run again).

## Storefront fields (efront)

Filled by the `mestiaque/efront` storefront (migration `2026_10_04_000001_add_storefront_columns_to_ecom_tables`):

| Column | Meaning |
|---|---|
| Customer photo | Stored in `me_media` (collection `avatar`) — `$customer->avatar_url`, `$customer->initial` (first letter) |
| `ecom_customers.phone_verified_at`, `email_verified_at` | Set when the customer confirmed the registration OTP; cleared when they change the phone / email |
| `ecom_orders.billing_address` | Billing address when it differs from shipping (`null` = same as shipping); shown on the order page and invoice |

Customers log in only to the storefront (`customer` guard, `ecom_customers`); admin users (`users`) are separate.

## Demo data

`EcomDemoSeeder` builds a presentation-ready shop ("ShopNest BD"): 157 products with real photos
(`database/seeders/data/demo-products.json`, images downloaded once from dummyjson.com), attributes and ~350 variants,
nested categories, brands with logos, customers, ~4 months of orders with payments, couriers and comments, reviews,
coupons, campaigns, banners and full policy pages. It is skipped when products already exist — empty the `ecom_*`
tables (or `php artisan migrate:refresh --path=vendor/mestiaque/ecom/src/database/migrations` on a local database) to reseed.

## Permissions

Declared in `src/Config/permission.php` (`ecom_order.status`, `ecom_product.import`, `ecom_attribute.edit`, `ecom_warranty.edit`, …) — assign them to roles on metheme's Roles page.

## Download links in admin views

File downloads (CSV export, invoice PDF, import template …) must use `<a download href="…" class="no-loader …">`. Without them metheme's page loader starts on click and never stops, because a download does not load a new page.

## Helpers

```php
ecom_setting('free_shipping_min');   // shop setting
ecom_money(1250);                    // "৳1,250.00"
ecom_image($path);                   // public URL of an uploaded image
```

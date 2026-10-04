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
php artisan ecom:thumbnails                                             # thumbnails for images that have none
```

The admin panel lives under metheme's prefix (`METHEME_ROUTE_PREFIX`, default `admin`): shop dashboard at `/admin/shop`.
Route names start with `ecom.` (`ecom.orders.index`, `ecom.products.edit`, …).

## Modules

| Menu | What it does |
|---|---|
| Shop Dashboard | Today / month sales, status counts, daily/weekly/monthly chart, top sellers, low stock, new customers |
| Orders | Filters, status flow (Pending → Confirmed → Processing → Shipped → Delivered / Cancelled / Returned), comments, invoice print + PDF, courier booking, payments & refunds |
| Transactions | All payments and refunds, CSV export |
| Customers | List, profile, order history, lifetime value, block/unblock |
| Products | Variants from attributes (own SKU, price, stock, image), multiple images with background thumbnails, active/hidden, low-stock alert, CSV import/export |
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

Tables: `ecom_attributes`, `ecom_attribute_values`, `ecom_product_variants` (`product_image_id`),
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

## Product image thumbnails

Every product image gets a small webp copy (longest side 400px) made by the queued job
`ME\Ecom\Jobs\GenerateProductThumbnail`; its path is saved in `ecom_product_images.thumbnail`.
Lists (products, dashboard, orders, product form) load the thumbnail instead of the full photo.

- Dispatched automatically when a `ProductImage` is created (admin upload, seeder, …).
- With `QUEUE_CONNECTION=sync` it runs **after the response is sent**, so uploads stay fast without a worker.
  With `database`/`redis` it goes to the queue — run `php artisan queue:work`.
- Until it has run, the full image is shown. Deleting an image deletes its thumbnail.
- Files: `ecom/products/thumbs/{name}.webp` on the `public` disk.
- Size / quality: `thumbnail.size` and `thumbnail.quality` in `src/Config/config.php`.

```bash
php artisan ecom:thumbnails          # images without a thumbnail (old uploads, restored backups)
php artisan ecom:thumbnails --force  # remake all, e.g. after changing the size
```

```php
$product->thumbnail;   // URL of the main image's thumbnail (full image until it exists)
$image->thumb_url;     // thumbnail URL of one image
$image->url;           // full image URL
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
| `ecom_customers.avatar` | Profile photo path on the `public` disk — `$customer->avatar_url`, `$customer->initial` (first letter) |
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

## Helpers

```php
ecom_setting('free_shipping_min');   // shop setting
ecom_money(1250);                    // "৳1,250.00"
ecom_image($path);                   // public URL of an uploaded image
```

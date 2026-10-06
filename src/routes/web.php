<?php

use Illuminate\Support\Facades\Route;
use ME\Ecom\Http\Controllers\AttributeController;
use ME\Ecom\Http\Controllers\BannerController;
use ME\Ecom\Http\Controllers\BrandController;
use ME\Ecom\Http\Controllers\CampaignController;
use ME\Ecom\Http\Controllers\CategoryController;
use ME\Ecom\Http\Controllers\CouponController;
use ME\Ecom\Http\Controllers\CustomerController;
use ME\Ecom\Http\Controllers\DashboardController;
use ME\Ecom\Http\Controllers\FaqController;
use ME\Ecom\Http\Controllers\OrderController;
use ME\Ecom\Http\Controllers\PageController;
use ME\Ecom\Http\Controllers\ProductController;
use ME\Ecom\Http\Controllers\ReportController;
use ME\Ecom\Http\Controllers\ReviewController;
use ME\Ecom\Http\Controllers\SettingController;
use ME\Ecom\Http\Controllers\ShippingController;
use ME\Ecom\Http\Controllers\TrackingController;
use ME\Ecom\Http\Controllers\TransactionController;
use ME\Ecom\Http\Controllers\WarrantyController;
use ME\Http\Middleware\LocaleMiddleware;

// Public order tracking (no login). The result page needs a signed link (made after order number + phone match).
Route::middleware('web')->name('ecom.track.')->group(function () {
    Route::get('/track-order', [TrackingController::class, 'form'])->name('form');
    Route::post('/track-order', [TrackingController::class, 'submit'])->middleware('throttle:ecom-track')->name('submit');
    Route::get('/track-order/{order:order_number}', [TrackingController::class, 'show'])->middleware('signed')->name('show');
});

// Admin panel. URL prefix from metheme (me_prefix(), .env METHEME_ROUTE_PREFIX); route names start with "ecom.".
Route::group([
    'prefix' => me_prefix(),
    'as' => 'ecom.',
    'middleware' => ['web', 'auth', LocaleMiddleware::class, 'activityLog'],
], function () {
    // The admin home (/{prefix}); metheme then skips its own home redirect
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sales-chart', [DashboardController::class, 'chart'])->name('dashboard.chart');

    // Catalog
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/import-template', [ProductController::class, 'importTemplate'])->name('products.import-template');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::patch('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('attributes', AttributeController::class)->except('show');
    Route::resource('warranties', WarrantyController::class)->except('show');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Orders & payments
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/notes', [OrderController::class, 'addNote'])->name('orders.notes');
    Route::post('/orders/{order}/courier', [OrderController::class, 'sendToCourier'])->name('orders.courier');
    Route::post('/orders/{order}/payments', [OrderController::class, 'recordPayment'])->name('orders.payments');
    Route::post('/orders/{order}/refunds', [OrderController::class, 'refund'])->name('orders.refunds');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{order}/invoice.pdf', [OrderController::class, 'invoicePdf'])->name('orders.invoice-pdf');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::patch('/customers/{customer}/block', [CustomerController::class, 'toggleBlock'])->name('customers.block');

    // Marketing
    Route::resource('coupons', CouponController::class)->except('show');
    Route::resource('campaigns', CampaignController::class)->except('show');

    // Website content
    Route::resource('banners', BannerController::class)->except('show');
    Route::resource('pages', PageController::class)->except('show');
    Route::resource('faqs', FaqController::class)->except('show');

    // Settings
    Route::get('/shipping', [ShippingController::class, 'index'])->name('shipping.index');
    Route::put('/shipping/settings', [ShippingController::class, 'updateSettings'])->name('shipping.settings');
    Route::post('/shipping/zones', [ShippingController::class, 'storeZone'])->name('shipping.zones.store');
    Route::put('/shipping/zones/{zone}', [ShippingController::class, 'updateZone'])->name('shipping.zones.update');
    Route::delete('/shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('shipping.zones.destroy');
    Route::post('/shipping/discounts', [ShippingController::class, 'storeDiscount'])->name('shipping.discounts.store');
    Route::put('/shipping/discounts/{discount}', [ShippingController::class, 'updateDiscount'])->name('shipping.discounts.update');
    Route::delete('/shipping/discounts/{discount}', [ShippingController::class, 'destroyDiscount'])->name('shipping.discounts.destroy');

    Route::get('/shop-settings/store', [SettingController::class, 'store'])->name('settings.store');
    Route::put('/shop-settings/store', [SettingController::class, 'updateStore'])->name('settings.store.update');
    Route::get('/shop-settings/payment', [SettingController::class, 'payment'])->name('settings.payment');
    Route::put('/shop-settings/payment', [SettingController::class, 'updatePayment'])->name('settings.payment.update');
    Route::get('/shop-settings/courier', [SettingController::class, 'courier'])->name('settings.courier');
    Route::put('/shop-settings/courier', [SettingController::class, 'updateCourier'])->name('settings.courier.update');

    // Reports (?export=csv downloads the same data)
    Route::get('/shop-reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/shop-reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('/shop-reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
});

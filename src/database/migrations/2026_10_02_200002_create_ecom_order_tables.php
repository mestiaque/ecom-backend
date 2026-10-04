<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecom_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('ecom_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('charge', 10, 2)->default(0);
            $table->string('delivery_time')->nullable()->comment('e.g. 1-2 days');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('type')->default('percent')->comment('percent|fixed');
            $table->decimal('value', 12, 2);
            $table->decimal('min_order_amount', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable()->comment('cap for percent coupons');
            $table->unsignedInteger('usage_limit')->nullable()->comment('total uses, null = unlimited');
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained('ecom_customers')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('shipping_address');
            $table->string('city')->nullable();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('ecom_shipping_zones')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained('ecom_coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('payment_method')->default('cod')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('courier')->nullable();
            $table->string('tracking_id')->nullable();
            $table->string('consignment_id')->nullable();
            $table->dateTime('sent_to_courier_at')->nullable();
            $table->text('customer_note')->nullable();
            $table->boolean('stock_restored')->default(false)->comment('stock returned on cancel/return');
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('ecom_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecom_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('ecom_products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('ecom_product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->string('warranty_label')->nullable()->comment('warranty at the time of order, e.g. "1 Year Official Warranty"');
            $table->unsignedInteger('warranty_days')->nullable();
            $table->timestamps();
        });

        // Status history + admin comments (status is null for a plain comment)
        Schema::create('ecom_order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecom_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('ecom_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('ecom_orders')->cascadeOnDelete();
            $table->string('type')->default('payment')->comment('payment|refund');
            $table->string('method');
            $table->decimal('amount', 12, 2);
            $table->string('trx_id')->nullable()->index();
            $table->string('status')->default('success')->comment('pending|success|failed');
            $table->json('gateway_response')->nullable();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_transactions');
        Schema::dropIfExists('ecom_order_notes');
        Schema::dropIfExists('ecom_order_items');
        Schema::dropIfExists('ecom_orders');
        Schema::dropIfExists('ecom_coupons');
        Schema::dropIfExists('ecom_shipping_zones');
        Schema::dropIfExists('ecom_customers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery charge rules: per-product free delivery / extra charge, and order-amount discounts.
     * The old "free shipping over X" setting becomes the first discount rule.
     */
    public function up(): void
    {
        Schema::table('ecom_products', function (Blueprint $table) {
            $table->boolean('free_delivery')->default(false)->after('weight');
            $table->decimal('delivery_charge_adjustment', 10, 2)->nullable()->after('free_delivery')
                ->comment('per unit, added to the zone charge (negative = less)');
        });

        Schema::create('ecom_shipping_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('ecom_shipping_zones')->cascadeOnDelete()->comment('null = every zone');
            $table->decimal('min_order_amount', 12, 2);
            $table->string('type', 10)->default('free')->comment('free|percent|fixed');
            $table->decimal('value', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->decimal('shipping_discount', 12, 2)->default(0)->after('shipping_charge')->comment('already taken off shipping_charge');
        });

        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')->whereIn('key', ['ecom_free_shipping_enabled', 'ecom_free_shipping_min'])->pluck('value', 'key');

            if (($settings['ecom_free_shipping_enabled'] ?? null) === '1' && is_numeric($settings['ecom_free_shipping_min'] ?? null)) {
                DB::table('ecom_shipping_discounts')->insert([
                    'min_order_amount' => $settings['ecom_free_shipping_min'],
                    'type' => 'free',
                    'value' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn('shipping_discount');
        });

        Schema::dropIfExists('ecom_shipping_discounts');

        Schema::table('ecom_products', function (Blueprint $table) {
            $table->dropColumn(['free_delivery', 'delivery_charge_adjustment']);
        });
    }
};

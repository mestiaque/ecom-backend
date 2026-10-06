<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields filled by the storefront (efront): verified phone / e-mail, billing address.
     */
    public function up(): void
    {
        Schema::table('ecom_customers', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
        });

        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->text('billing_address')->nullable()->after('city')->comment('null = same as shipping');
        });
    }

    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $table) {
            $table->dropColumn('billing_address');
        });

        Schema::table('ecom_customers', function (Blueprint $table) {
            $table->dropColumn(['phone_verified_at', 'email_verified_at']);
        });
    }
};

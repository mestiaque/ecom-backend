<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Frequently asked questions shown on the storefront FAQ page, grouped by category
        Schema::create('ecom_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->nullable()->comment('Orders, Payment, Delivery, ... (null = General)');
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_faqs');
    }
};

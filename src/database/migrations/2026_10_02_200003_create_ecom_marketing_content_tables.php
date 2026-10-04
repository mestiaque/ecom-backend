<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecom_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner')->nullable();
            $table->string('discount_type')->default('percent')->comment('percent|fixed');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_campaign_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('ecom_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('ecom_products')->cascadeOnDelete();
            $table->unique(['campaign_id', 'product_id']);
        });

        Schema::create('ecom_banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image');
            $table->string('link')->nullable();
            $table->string('button_text')->nullable();
            $table->string('position')->default('slider')->comment('slider|promo');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_pages');
        Schema::dropIfExists('ecom_banners');
        Schema::dropIfExists('ecom_campaign_product');
        Schema::dropIfExists('ecom_campaigns');
    }
};

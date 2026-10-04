<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecom_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('ecom_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('banner')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Warranty master data: entered as number + unit (7 days, 6 months, 1 year), stored in days
        Schema::create('ecom_warranties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration');
            $table->string('duration_unit')->default('month')->comment('day|month|year');
            $table->unsignedInteger('days')->comment('warranty length in days (used for calculations)');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ecom_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('ecom_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('ecom_brands')->nullOnDelete();
            $table->foreignId('warranty_id')->nullable()->constrained('ecom_warranties')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->decimal('weight', 8, 2)->nullable()->comment('kg, used for courier');
            $table->timestamps();

            $table->index(['is_active', 'stock']);
        });

        Schema::create('ecom_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ecom_products')->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail')->nullable()->comment('small webp made in the background (GenerateProductThumbnail)');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Variant attributes: Color, Size, Storage, Material, ... and their values (Red, M, 128GB, ...)
        Schema::create('ecom_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('select')->comment('select|color (color shows swatches)');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ecom_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('ecom_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->string('color_code', 7)->nullable()->comment('#RRGGBB for color attributes');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('ecom_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ecom_products')->cascadeOnDelete();
            $table->foreignId('product_image_id')->nullable()->constrained('ecom_product_images')->nullOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 12, 2)->nullable()->comment('null = product price');
            $table->decimal('discount_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Which attribute values make up each variant (e.g. Color: Red + Size: M)
        Schema::create('ecom_product_variant_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('ecom_product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('ecom_attribute_values')->restrictOnDelete();

            $table->primary(['variant_id', 'attribute_value_id']);
        });

        Schema::create('ecom_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('ecom_products')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_reviews');
        Schema::dropIfExists('ecom_product_variant_values');
        Schema::dropIfExists('ecom_product_variants');
        Schema::dropIfExists('ecom_attribute_values');
        Schema::dropIfExists('ecom_attributes');
        Schema::dropIfExists('ecom_product_images');
        Schema::dropIfExists('ecom_products');
        Schema::dropIfExists('ecom_brands');
        Schema::dropIfExists('ecom_warranties');
        Schema::dropIfExists('ecom_categories');
    }
};

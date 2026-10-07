<?php

namespace ME\Ecom\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ME\Ecom\Enums\OrderStatus;
use ME\Ecom\Enums\PaymentMethod;
use ME\Ecom\Models\Attribute;
use ME\Ecom\Models\AttributeValue;
use ME\Ecom\Models\Banner;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Campaign;
use ME\Ecom\Models\Category;
use ME\Ecom\Models\Coupon;
use ME\Ecom\Models\Customer;
use ME\Ecom\Models\Faq;
use ME\Ecom\Models\Order;
use ME\Ecom\Models\Page;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\Review;
use ME\Ecom\Models\ShippingDiscount;
use ME\Ecom\Models\ShippingZone;
use ME\Ecom\Models\Warranty;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Services\OrderService;
use ME\Ecom\Support\EcomSettings;
use ME\Models\Setting;
use ME\Models\User;

/**
 * Presentation-ready demo shop: store info & logo, 157 products with real photos (data/demo-products.json,
 * images downloaded from dummyjson.com), nested categories, brands, customers, ~4 months of orders with
 * payments / couriers / comments, reviews, coupons, campaigns, banners and full policy pages.
 * Skipped when products already exist.
 *
 * php artisan db:seed --class="ME\Ecom\database\seeders\EcomDemoSeeder"
 */
class EcomDemoSeeder extends Seeder
{
    private const DAYS_OF_HISTORY = 120;

    /**
     * Top-level category => [colour, [demo category key => sub-category name]].
     */
    private const CATEGORIES = [
        'Men' => [[37, 99, 235], ['mens-shirts' => 'Shirts', 'mens-shoes' => 'Shoes', 'mens-watches' => 'Watches']],
        'Women' => [[219, 39, 119], ['womens-dresses' => 'Dresses', 'tops' => 'Tops', 'womens-bags' => 'Bags', 'womens-shoes' => 'Shoes', 'womens-jewellery' => 'Jewellery', 'womens-watches' => 'Watches']],
        'Electronics' => [[8, 145, 178], ['smartphones' => 'Smartphones', 'laptops' => 'Laptops', 'tablets' => 'Tablets', 'mobile-accessories' => 'Mobile Accessories']],
        'Beauty & Health' => [[190, 24, 93], ['fragrances' => 'Fragrances', 'beauty' => 'Makeup', 'skin-care' => 'Skin Care']],
        'Home & Living' => [[22, 163, 74], ['home-decoration' => 'Home Decor', 'kitchen-accessories' => 'Kitchen', 'furniture' => 'Furniture']],
        'Sports & Outdoor' => [[234, 88, 12], ['sports-accessories' => 'Sports Gear']],
        'Accessories' => [[71, 85, 105], ['sunglasses' => 'Sunglasses']],
    ];

    private const NAMES = [
        'Rahim Uddin', 'Karim Ahmed', 'Fatema Begum', 'Nusrat Jahan', 'Tanvir Hasan', 'Sadia Islam', 'Arif Hossain', 'Mim Akter',
        'Sabbir Rahman', 'Jannatul Ferdous', 'Rakib Hasan', 'Farhana Yasmin', 'Mehedi Hasan', 'Sumaiya Akter', 'Imran Khan', 'Tasnim Rahman',
        'Shakil Ahmed', 'Nadia Sultana', 'Fahim Shahriar', 'Afsana Mimi', 'Rashedul Islam', 'Tahmina Khatun', 'Ashraful Alam', 'Lamia Chowdhury',
        'Nayeem Hossain', 'Riya Das', 'Zahid Hasan', 'Sharmin Sultana', 'Mahfuz Rahman', 'Anika Tabassum', 'Shahriar Kabir', 'Mou Roy',
        'Habibur Rahman', 'Israt Jahan', 'Tariqul Islam', 'Puja Saha', 'Jubayer Ahmed', 'Rumana Afroz', 'Saiful Islam', 'Tania Akter',
        'Minhaz Uddin', 'Sanjida Islam', 'Ridwan Hossain', 'Moumita Paul', 'Asif Iqbal', 'Ayesha Siddika', 'Nazmul Huda', 'Kaniz Fatema',
    ];

    /**
     * Shipping zone => [city => [areas]].
     */
    private const ADDRESSES = [
        'Inside Dhaka' => ['Dhaka' => ['Dhanmondi 27', 'Mirpur 10', 'Uttara Sector 7', 'Gulshan 1', 'Mohammadpur', 'Banani', 'Bashundhara R/A', 'Motijheel', 'Badda', 'Khilgaon', 'Shyamoli', 'Farmgate', 'Malibagh', 'Lalbagh']],
        'Dhaka Suburbs' => ['Gazipur' => ['Tongi', 'Board Bazar', 'Chowrasta'], 'Narayanganj' => ['Chashara', 'Fatullah'], 'Savar' => ['Hemayetpur', 'Radio Colony']],
        'Outside Dhaka' => [
            'Chattogram' => ['Agrabad', 'GEC Circle', 'Halishahar'], 'Sylhet' => ['Zindabazar', 'Amberkhana'], 'Rajshahi' => ['Shaheb Bazar', 'Uposhohor'],
            'Khulna' => ['Sonadanga', 'Boyra'], 'Cumilla' => ['Kandirpar'], 'Bogura' => ['Satmatha'], 'Rangpur' => ['Jahaj Company Mor'],
            'Barishal' => ['Nathullabad'], 'Mymensingh' => ['Ganginarpar'], "Cox's Bazar" => ['Kolatoli'],
        ],
    ];

    private const REVIEW_COMMENTS = [
        5 => ['Excellent quality, exactly as shown in the picture!', 'Khub valo product, delivery o fast chilo.', 'Original product. Highly recommended!', 'Packaging was very good. Will buy again.', 'Daam onujayi onek valo. Thanks ShopNest!', 'Super fast delivery inside Dhaka.'],
        4 => ['Good product, delivery took a day extra.', 'Quality is good, size was a bit big.', 'Valo, kintu color ektu different.', 'Worth the price.', 'Nice product, recommended.'],
        3 => ['Average quality.', 'Okay for the price.', 'Product thik ache, kintu packaging better hote parto.'],
        2 => ['Not as expected.', 'Size mile nai, exchange korte hoyeche.'],
        1 => ['Product damaged chilo, return korechi.'],
    ];

    /**
     * Demo attributes: name => [type, [value => swatch colour|null]].
     */
    private const ATTRIBUTES = [
        'Color' => ['color', ['Black' => '#111827', 'White' => '#F9FAFB', 'Navy' => '#1E3A8A', 'Blue' => '#2563EB', 'Red' => '#DC2626', 'Maroon' => '#7F1D1D', 'Pink' => '#EC4899', 'Green' => '#16A34A', 'Beige' => '#D6C3A5', 'Brown' => '#7C4A2D', 'Gold' => '#D4AF37', 'Silver' => '#C0C0C0']],
        'Size' => ['select', ['S' => null, 'M' => null, 'L' => null, 'XL' => null, 'XXL' => null]],
        'Shoe Size' => ['select', ['36' => null, '37' => null, '38' => null, '39' => null, '40' => null, '41' => null, '42' => null, '43' => null, '44' => null]],
        'Storage' => ['select', ['64GB' => null, '128GB' => null, '256GB' => null, '512GB' => null]],
        'Material' => ['select', ['Cotton' => null, 'Linen' => null, 'Silk' => null, 'Leather' => null, 'Stainless Steel' => null]],
    ];

    /**
     * Variants per demo category: attribute => values. ['pick' => n, 'from' => [...]] picks n random values per product.
     */
    private const VARIANT_PLANS = [
        'mens-shirts' => ['Color' => ['pick' => 2, 'from' => ['Navy', 'White', 'Black', 'Blue']], 'Size' => ['S', 'M', 'L', 'XL']],
        'womens-dresses' => ['Color' => ['pick' => 2, 'from' => ['Red', 'Pink', 'Black', 'Beige', 'Maroon']], 'Size' => ['S', 'M', 'L', 'XL']],
        'tops' => ['Color' => ['pick' => 2, 'from' => ['White', 'Pink', 'Black', 'Beige']], 'Size' => ['S', 'M', 'L']],
        'mens-shoes' => ['Color' => ['pick' => 2, 'from' => ['Black', 'Brown', 'White']], 'Shoe Size' => ['40', '41', '42', '43', '44']],
        'womens-shoes' => ['Color' => ['Beige', 'Black'], 'Shoe Size' => ['36', '37', '38', '39', '40']],
        'smartphones' => ['Color' => ['pick' => 3, 'from' => ['Black', 'Blue', 'Silver', 'Gold', 'White']], 'Storage' => ['128GB', '256GB']],
        'tablets' => ['Color' => ['Silver', 'Black'], 'Storage' => ['64GB', '128GB', '256GB']],
        'mens-watches' => ['Material' => ['Leather', 'Stainless Steel']],
        'womens-bags' => ['Color' => ['pick' => 2, 'from' => ['Black', 'Brown', 'Beige', 'Red']]],
        'sunglasses' => ['Color' => ['Black', 'Brown']],
    ];

    /**
     * Price increase per step of these attributes (256GB costs more than 128GB, steel more than leather).
     */
    private const PRICE_STEPS = ['Storage' => 12, 'Material' => 8];

    /**
     * Demo category key => warranty name (from EcomSeeder). Categories not listed get no warranty.
     */
    public const WARRANTY_BY_CATEGORY = [
        'smartphones' => '1 Year Official Warranty', 'tablets' => '1 Year Official Warranty', 'laptops' => '2 Years Brand Warranty',
        'mobile-accessories' => '6 Months Warranty', 'sunglasses' => '6 Months Warranty',
        'mens-watches' => '2 Years Brand Warranty', 'womens-watches' => '1 Year Official Warranty',
        'kitchen-accessories' => '3 Months Service Warranty', 'sports-accessories' => '3 Months Service Warranty', 'furniture' => '1 Year Official Warranty',
        'mens-shirts' => '7 Days Replacement', 'mens-shoes' => '7 Days Replacement', 'womens-dresses' => '7 Days Replacement', 'tops' => '7 Days Replacement',
        'womens-bags' => '7 Days Replacement', 'womens-shoes' => '7 Days Replacement', 'womens-jewellery' => '7 Days Replacement', 'home-decoration' => '7 Days Replacement',
    ];

    /** @var array<string, int> warranty name => id */
    private array $warrantyIds = [];

    /** @var array<string, array<string, AttributeValue>> attribute name => value => model */
    private array $attributeValues = [];

    private DemoImages $images;

    private OrderService $orders;

    private ?int $adminId;

    /** @var array<int, int> product id => order weight */
    private array $popularity = [];

    /** @var array<int, string> customer id => shipping zone name */
    private array $customerZones = [];

    public function run(OrderService $orders, EcomSettings $settings, CourierManager $couriers): void
    {
        $this->call(EcomSeeder::class);

        if (Product::exists()) {
            $this->command?->warn('Products already exist — demo data skipped. Empty the ecom_* tables first to reseed.');

            return;
        }

        $this->images = new DemoImages;
        $this->orders = $orders;
        $this->adminId = User::where('email', 'admin@mestiaque.com')->value('id') ?? User::value('id');
        Storage::disk('public')->makeDirectory(config('ecom.upload_dir'));

        $this->command?->info('Store settings & logo…');
        $this->storeSettings($settings);
        ShippingZone::firstOrCreate(['name' => 'Chattogram City'], ['charge' => 100, 'delivery_time' => '2-3 days', 'sort_order' => 4, 'is_active' => false]);

        $this->command?->info('Downloading product photos (first run only)…');
        $data = collect(json_decode(file_get_contents(__DIR__.'/data/demo-products.json'), true));
        $this->downloadPhotos($data);

        $this->command?->info('Catalog…');
        $this->warrantyIds = Warranty::pluck('id', 'name')->all();
        $this->attributes();
        $categories = $this->categories($data);
        $brands = $this->brands($data);
        $products = $this->products($data, $categories, $brands);

        $this->command?->info('Coupons, campaigns, customers…');
        $this->coupons();
        $this->campaigns($products, $categories);
        $customers = $this->customers();

        $this->command?->info('Orders (this takes a minute)…');
        $this->orderHistory($products, $customers, $couriers);

        $this->command?->info('Reviews, banners, pages…');
        $this->reviews($data, $products, $customers);
        $this->finishStock($products);
        $this->banners($categories);
        $this->pages();
        $this->faqs();
        ShippingDiscount::firstOrCreate(['min_order_amount' => 2000, 'type' => 'free', 'shipping_zone_id' => null]);
        ShippingDiscount::firstOrCreate(['min_order_amount' => 1000, 'type' => 'percent', 'shipping_zone_id' => null], ['value' => 50]);

        $this->command?->info(sprintf(
            'Demo shop ready: %d products, %d customers, %d orders, %d reviews.',
            Product::count(), Customer::count(), Order::count(), Review::count()
        ));
    }

    private function storeSettings(EcomSettings $settings): void
    {
        $this->images->logo($logo = $this->path('store/logo.png'), 'ShopNest', [234, 88, 12]);

        $settings->set([
            'store_name' => 'ShopNest BD',
            'store_tagline' => 'Trusted online shopping in Bangladesh',
            'store_phone' => '09612-345678',
            'store_email' => 'support@shopnest.com.bd',
            'store_address' => 'House 12, Road 7, Dhanmondi, Dhaka 1205',
            'store_hotline_hours' => '10 AM – 10 PM (Saturday – Thursday)',
            'social_facebook' => 'https://facebook.com/shopnestbd',
            'social_instagram' => 'https://instagram.com/shopnestbd',
            'social_youtube' => 'https://youtube.com/@shopnestbd',
            'social_tiktok' => 'https://tiktok.com/@shopnestbd',
            'social_whatsapp' => '+8801711-000000',
            'order_prefix' => 'ORD-',
            'invoice_footer' => 'Thank you for shopping with ShopNest BD! Hotline 09612-345678 · 7 days easy return.',
            'invoice_prefix' => 'INV-',
            'invoice_accent' => '#1f3a5f',
            'invoice_paper' => 'a4',
            'invoice_tax_label' => 'BIN',
            'invoice_tax_number' => '004567891-0101',
            'invoice_signature' => 'Authorized Signature',
            'invoice_show_sku' => '1',
            'invoice_notes' => 'Keep this invoice for warranty claims. Check the parcel in front of the rider before paying.',
            'invoice_terms' => "Products can be returned within 7 days in the original box with all accessories.\nWarranty covers manufacturing defects only, not physical or liquid damage.",
            'low_stock_threshold' => '5',
            'payment_cod_enabled' => '1',
            'payment_cod_instructions' => 'Pay the delivery rider in cash when you receive your parcel.',
            'payment_bkash_enabled' => '1',
            'payment_bkash_mode' => 'sandbox',
            'payment_bkash_app_key' => '4f6o0cjiki2rfm34kfdadl1eqq', // bKash's public tokenized sandbox account
            'payment_bkash_username' => 'sandboxTokenizedUser02',
            'payment_bkash_instructions' => 'Pay with your bKash account on the secure bKash page.',
            'payment_nagad_enabled' => '1',
            'payment_nagad_mode' => 'sandbox',
            'payment_nagad_merchant_id' => '683002007104225',
            'payment_nagad_merchant_number' => '01711000000',
            'payment_nagad_instructions' => 'Send the amount to our Nagad merchant number 01711000000 and enter the transaction ID below.',
            'payment_sslcommerz_enabled' => '1',
            'payment_sslcommerz_mode' => 'sandbox',
            'payment_sslcommerz_store_id' => 'testbox', // SSLCommerz's public sandbox store
            'payment_sslcommerz_instructions' => 'Pay with Visa, Mastercard, Amex or internet banking.',
        ]);
        Setting::setImage('ecom_store_logo', Storage::disk('public')->path($logo));
        $settings->set([
            'payment_bkash_app_secret' => '2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b',
            'payment_bkash_password' => 'sandboxTokenizedUser02@12345',
            'payment_sslcommerz_store_password' => 'qwerty',
        ], ['payment_bkash_app_secret', 'payment_bkash_password', 'payment_sslcommerz_store_password']);
    }

    private function downloadPhotos(Collection $data): void
    {
        $targets = [];
        foreach ($data as $item) {
            foreach ($item['images'] as $i => $url) {
                $targets[$this->photoPath($item, $i)] = $url;
            }
        }

        $byPath = $data->flatMap(fn ($item) => collect($item['images'])->keys()->mapWithKeys(fn ($i) => [$this->photoPath($item, $i) => $item]));
        $this->images->download($targets, fn ($path) => $this->images->placeholder($path, $byPath[$path]['title'], $this->colorFor($byPath[$path]['category'])));
    }

    /**
     * @return array<string, Category> keyed by demo category key and by top-level name
     */
    private function categories(Collection $data): array
    {
        $categories = [];
        $sort = 1;

        foreach (self::CATEGORIES as $name => [$color, $children]) {
            $slug = Str::slug($name);
            $parent = Category::create([
                'name' => $name,
                'slug' => $slug,
                'description' => "Shop the latest {$name} products at the best prices in Bangladesh.",
                'sort_order' => $sort++,
            ]);

            $photos = [];
            $childSort = 1;
            foreach ($children as $key => $childName) {
                $first = $data->firstWhere('category', $key);
                $image = $this->path("categories/{$key}.webp");
                Storage::disk('public')->copy($this->photoPath($first, 0), $image);
                $photos[] = $this->photoPath($first, 0);

                $categories[$key] = Category::create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => $key,
                    'description' => "{$childName} for every style and budget.",
                    'sort_order' => $childSort++,
                ]);
                $categories[$key]->addMediaFromDisk($image, 'image');
            }

            $parentImage = $this->path("categories/{$slug}.webp");
            Storage::disk('public')->copy($photos[0], $parentImage);
            $banner = $this->path("categories/{$slug}-banner.jpg");
            $this->images->banner($banner, 1200, 320, 'ShopNest '.$name, $name, 'Best prices · Cash on delivery · Easy return', null, $photos, $color, $this->images->shade($color, -70));
            $parent->addMediaFromDisk($parentImage, 'image');
            $parent->addMediaFromDisk($banner, 'banner');
            $categories[$name] = $parent;
        }

        // An inactive category to show the "hidden" state
        Category::create(['name' => 'Groceries', 'slug' => 'groceries', 'description' => 'Coming soon', 'sort_order' => $sort, 'is_active' => false]);

        return $categories;
    }

    /**
     * @return array<string, Brand>
     */
    private function brands(Collection $data): array
    {
        $palette = [[37, 99, 235], [219, 39, 119], [22, 163, 74], [234, 88, 12], [124, 58, 237], [8, 145, 178], [190, 18, 60], [71, 85, 105]];
        $brands = [];

        foreach ($data->pluck('brand')->filter()->push('ShopNest Basics')->unique()->sort()->values() as $i => $name) {
            $this->images->brandLogo($logo = $this->path('brands/'.Str::slug($name).'.png'), $name, $palette[$i % count($palette)]);
            $brands[$name] = Brand::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Genuine {$name} products, sourced from authorised distributors.",
            ]);
            $brands[$name]->addMediaFromDisk($logo, 'logo');
        }

        return $brands;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Brand>  $brands
     * @return Collection<int, Product>
     */
    private function products(Collection $data, array $categories, array $brands): Collection
    {
        return $data->values()->map(function (array $item, int $i) use ($categories, $brands) {
            $price = $this->bdt($item['price']);
            $discount = $item['discountPercentage'] >= 9 ? $this->bdt($item['price'] * (1 - $item['discountPercentage'] / 100)) : null;
            $discount = $discount !== null && $discount < $price ? $discount : null;
            $plan = self::VARIANT_PLANS[$item['category']] ?? null;
            $brand = $item['brand'] ?? 'ShopNest Basics';

            $product = Product::create([
                'category_id' => $categories[$item['category']]->id,
                'brand_id' => $brands[$brand]->id,
                'warranty_id' => $this->warrantyIds[self::WARRANTY_BY_CATEGORY[$item['category']] ?? ''] ?? null,
                'title' => $item['title'],
                'slug' => $this->uniqueSlug($item['title']),
                'sku' => $item['sku'],
                'short_description' => Str::before($item['description'], '. ').'.',
                'description' => $this->description($item, $brand),
                'price' => $price,
                'discount_price' => $discount,
                'cost_price' => round(($discount ?? $price) * random_int(62, 74) / 100),
                'stock' => $plan ? 0 : $item['stock'] + random_int(25, 60),
                'low_stock_threshold' => $item['price'] > 500 ? 3 : null,
                'has_variants' => (bool) $plan,
                'is_featured' => $item['rating'] >= 4.5 || $i % 11 === 0,
                'weight' => round(max(0.1, $item['weight'] / 10), 2),
                'created_at' => now()->subDays(self::DAYS_OF_HISTORY + random_int(5, 40)),
            ]);

            $images = collect(array_keys($item['images']))->map(fn ($n) => $product->addMediaFromDisk($this->photoPath($item, $n), 'gallery', attributes: ['sort_order' => $n]));

            if ($plan) {
                $this->variants($product, $plan, $price, $discount, $images->pluck('id')->all());
            }

            $priceWeight = match (true) {
                $price <= 5000 => 4,
                $price <= 25000 => 2,
                $price <= 120000 => 1,
                default => 0,
            };
            $this->popularity[$product->id] = $priceWeight * ((int) round(($item['rating'] ** 2) * random_int(1, 4) / 8) + 1);

            return $product;
        });
    }

    private function attributes(): void
    {
        $sort = 1;

        foreach (self::ATTRIBUTES as $name => [$type, $values]) {
            $attribute = Attribute::create(['name' => $name, 'slug' => Str::slug($name), 'type' => $type, 'sort_order' => $sort++]);
            $valueSort = 0;

            foreach ($values as $value => $color) {
                $this->attributeValues[$name][$value] = $attribute->values()->create(['value' => $value, 'color_code' => $color, 'sort_order' => $valueSort++]);
            }
        }
    }

    /**
     * One variant per combination of the plan's values, with its own SKU, stock, price step and colour photo.
     *
     * @param  array<string, array<mixed>>  $plan
     * @param  array<int, int>  $imageIds  gallery me_media ids, in order
     */
    private function variants(Product $product, array $plan, float $price, ?float $discount, array $imageIds): void
    {
        $groups = [];
        foreach ($plan as $attribute => $values) {
            if (isset($values['pick'])) {
                $values = collect($values['from'])->shuffle()->take($values['pick'])->sortBy(fn ($v) => array_search($v, $values['from'], true))->values()->all();
            }
            $groups[$attribute] = $values;
        }

        $combinations = [[]];
        foreach ($groups as $attribute => $values) {
            $combinations = collect($combinations)->flatMap(fn ($combo) => collect($values)->map(fn ($value) => $combo + [$attribute => $value]))->all();
        }

        foreach ($combinations as $combo) {
            $step = 0;
            foreach (self::PRICE_STEPS as $attribute => $percent) {
                if (isset($combo[$attribute])) {
                    $step += array_search($combo[$attribute], $groups[$attribute], true) * $percent;
                }
            }
            $colorIndex = isset($combo['Color']) ? array_search($combo['Color'], $groups['Color'], true) : null;

            $variant = $product->variants()->create([
                'sku' => $product->sku.'-'.collect($combo)->map(fn ($value, $attribute) => $attribute === 'Color' ? strtoupper(substr($value, 0, 3)) : strtoupper(str_replace(' ', '', $value)))->implode('-'),
                'price' => $step ? $this->roundTaka($price * (1 + $step / 100)) : null,
                'discount_price' => $step && $discount ? $this->roundTaka($discount * (1 + $step / 100)) : null,
                'stock' => random_int(3, 14),
                'media_id' => $colorIndex !== null ? ($imageIds[$colorIndex] ?? null) : null,
            ]);
            $variant->values()->sync(collect($combo)->map(fn ($value, $attribute) => $this->attributeValues[$attribute][$value]->id)->values()->all());
        }

        $product->syncStockFromVariants();
    }

    private function coupons(): void
    {
        $coupons = [
            ['code' => 'WELCOME10', 'description' => '10% off your first order (max ৳300)', 'type' => 'percent', 'value' => 10, 'max_discount' => 300, 'min_order_amount' => 1000, 'usage_limit_per_customer' => 1],
            ['code' => 'FLAT200', 'description' => '৳200 off on orders above ৳2,500', 'type' => 'fixed', 'value' => 200, 'min_order_amount' => 2500],
            ['code' => 'SAVE500', 'description' => '৳500 off on orders above ৳10,000', 'type' => 'fixed', 'value' => 500, 'min_order_amount' => 10000, 'usage_limit' => 100],
            ['code' => 'EID2026', 'description' => 'Eid special 15% off (max ৳1,000)', 'type' => 'percent', 'value' => 15, 'max_discount' => 1000, 'min_order_amount' => 3000, 'usage_limit' => 500, 'starts_at' => now()->subDays(25), 'expires_at' => now()->addDays(20)],
            ['code' => 'VICTORY25', 'description' => 'Victory Day 25% off', 'type' => 'percent', 'value' => 25, 'max_discount' => 1500, 'min_order_amount' => 2000, 'starts_at' => now()->subDays(65), 'expires_at' => now()->subDays(55)],
            ['code' => 'BOISHAKH', 'description' => 'Pohela Boishakh offer — coming soon', 'type' => 'percent', 'value' => 20, 'max_discount' => 800, 'starts_at' => now()->addDays(30), 'expires_at' => now()->addDays(37)],
            ['code' => 'STAFF50', 'description' => 'Staff discount (disabled)', 'type' => 'percent', 'value' => 50, 'is_active' => false],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon + ['created_at' => now()->subDays(self::DAYS_OF_HISTORY)]);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  array<string, Category>  $categories
     */
    private function campaigns(Collection $products, array $categories): void
    {
        $inCategories = fn (array $keys) => $products->whereIn('category_id', collect($keys)->map(fn ($key) => $categories[$key]->id));
        $campaigns = [
            ['Mega Flash Sale', 'Up to 12% extra off on phones & gadgets', 'percent', 12, now()->subDays(2), now()->addDays(3), ['smartphones', 'mobile-accessories', 'tablets'], [8, 145, 178]],
            ['Puja Festival Offer', 'Festive collection for her', 'percent', 15, now()->addDays(10), now()->addDays(17), ['womens-dresses', 'tops', 'womens-jewellery'], [219, 39, 119]],
            ['Victory Day Sale', '৳200 off on men\'s fashion', 'fixed', 200, now()->subDays(65), now()->subDays(55), ['mens-shirts', 'mens-shoes'], [22, 163, 74]],
        ];

        foreach ($campaigns as [$title, $subtitle, $type, $value, $start, $end, $keys, $color]) {
            $items = $inCategories($keys)->shuffle()->take(10);
            $banner = $this->path('campaigns/'.Str::slug($title).'.jpg');
            $this->images->banner($banner, 1600, 640, $start->isFuture() ? 'Coming soon' : 'Limited time', $title, $subtitle, 'Shop Now',
                $items->take(3)->map(fn ($product) => $product->images()->value('path'))->all(), $color, $this->images->shade($color, -80));

            $campaign = Campaign::create([
                'title' => $title, 'slug' => Str::slug($title), 'description' => $subtitle,
                'discount_type' => $type, 'discount_value' => $value, 'starts_at' => $start, 'ends_at' => $end,
            ]);
            $campaign->addMediaFromDisk($banner, 'banner');
            $campaign->products()->sync($items->pluck('id'));
        }
    }

    /**
     * @return Collection<int, Customer>
     */
    private function customers(): Collection
    {
        $phones = [];

        return collect(self::NAMES)->map(function (string $name, int $i) use (&$phones) {
            do {
                $phone = '01'.[3, 4, 5, 6, 7, 8, 9][random_int(0, 6)].str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while (isset($phones[$phone]));
            $phones[$phone] = true;

            [$zone, $city, $area] = $this->randomAddress($i);
            $joined = match (true) {
                $i < 2 => now()->subHours(random_int(1, 6)),
                $i < 8 => now()->subDays(random_int(1, 25)),
                default => now()->subDays(random_int(self::DAYS_OF_HISTORY, self::DAYS_OF_HISTORY + 90)),
            };

            $customer = Customer::create([
                'name' => $name,
                'phone' => $phone,
                'email' => $i % 4 === 3 ? null : Str::slug($name, '.').random_int(10, 99).'@gmail.com',
                'password' => 'password',
                'address' => 'House '.random_int(1, 150).', Road '.random_int(1, 30).', '.$area,
                'city' => $city,
            ]);
            $customer->forceFill(['created_at' => $joined, 'updated_at' => $joined])->saveQuietly();
            $this->customerZones[$customer->id] = $zone;

            return $customer;
        });
    }

    /**
     * Orders day by day, growing over time, each moved through a realistic status path for its age.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Customer>  $customers
     */
    private function orderHistory(Collection $products, Collection $customers, CourierManager $couriers): void
    {
        $zones = ShippingZone::pluck('id', 'name');
        $weighted = collect(array_filter($this->popularity))->flatMap(fn ($weight, $id) => array_fill(0, $weight, $id));
        $notes = [null, null, null, null, 'Please call before delivery.', 'Deliver after 5 PM please.', 'Office address — weekdays only.', 'Gift item, please pack nicely.'];
        $codes = [null, null, null, null, null, null, null, 'WELCOME10', 'FLAT200', 'SAVE500', 'EID2026', 'VICTORY25'];
        $methods = ['cod', 'cod', 'cod', 'cod', 'cod', 'bkash', 'bkash', 'nagad', 'sslcommerz'];
        $now = now();

        for ($day = self::DAYS_OF_HISTORY; $day >= 0; $day--) {
            $count = match (true) {
                $day === 0 => 9,
                $day <= 3 => random_int(5, 7),
                default => random_int(0, 2) + (int) round(4 * (self::DAYS_OF_HISTORY - $day) / self::DAYS_OF_HISTORY),
            };

            for ($n = 0; $n < $count; $n++) {
                $placedAt = $now->copy()->subDays($day)->setTime(random_int(9, 23), random_int(0, 59));
                if ($placedAt->isFuture()) {
                    $placedAt = $now->copy()->subMinutes(random_int(1, max(1, (int) $now->copy()->startOfDay()->diffInMinutes($now) - 1)));
                }

                $eligible = $customers->filter(fn ($customer) => $customer->created_at <= $placedAt);
                $customer = $eligible->isNotEmpty() && random_int(1, 10) > 1 ? $eligible->random() : null;
                [$zone, $city, $area] = $customer ? [$this->customerZones[$customer->id], $customer->city, null] : $this->randomAddress(random_int(0, 99));

                $items = $weighted->random(random_int(1, 3))->unique()->map(function ($productId) use ($products) {
                    $product = $products->firstWhere('id', $productId)->fresh();
                    $variant = $product->has_variants ? $product->variants()->where('stock', '>', 0)->inRandomOrder()->first() : null;
                    $available = $variant ? $variant->stock : $product->stock;

                    return $available > 0 ? ['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $available > 3 && $product->price < 3000 && random_int(1, 4) === 1 ? 2 : 1] : null;
                })->filter()->values()->all();

                if (! $items) {
                    continue;
                }

                $guestName = self::NAMES[random_int(0, count(self::NAMES) - 1)];
                $data = [
                    'customer_id' => $customer?->id,
                    'customer_name' => $customer?->name ?? $guestName,
                    'customer_phone' => $customer?->phone ?? '01'.random_int(3, 9).random_int(10000000, 99999999),
                    'customer_email' => $customer?->email,
                    'shipping_address' => $customer?->address ?? 'House '.random_int(1, 99).', '.$area,
                    'city' => $city,
                    'shipping_zone_id' => $zones[$zone] ?? null,
                    'payment_method' => $methods[array_rand($methods)],
                    'customer_note' => $notes[array_rand($notes)],
                    'coupon_code' => $codes[array_rand($codes)],
                ];

                Carbon::setTestNow($placedAt);

                try {
                    $order = $this->placeOrder($data, $items);
                    $this->advance($order, $placedAt, $couriers);
                } catch (ValidationException) {
                    // out of stock — skip
                } finally {
                    Carbon::setTestNow();
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    private function placeOrder(array $data, array $items): Order
    {
        try {
            $order = $this->orders->place($data, $items);
        } catch (ValidationException $e) {
            if (! $data['coupon_code'] || ! isset($e->errors()['coupon_code'])) {
                throw $e;
            }
            $order = $this->orders->place(['coupon_code' => null] + $data, $items);
        }

        if ($order->payment_method !== PaymentMethod::CashOnDelivery) {
            $this->orders->recordPayment($order, [
                'method' => $order->payment_method->value,
                'amount' => $order->total,
                'trx_id' => strtoupper(Str::random(random_int(0, 1) ? 10 : 12)),
                'note' => 'Paid online at checkout',
            ]);
        }

        return $order;
    }

    /**
     * Move the order along the status flow according to how old it is.
     */
    private function advance(Order $order, Carbon $placedAt, CourierManager $couriers): void
    {
        Carbon::setTestNow();
        $hours = $placedAt->diffInHours(now(), true);
        $roll = random_int(1, 100);

        $target = match (true) {
            $hours < 6 => $roll <= 75 ? OrderStatus::Pending : OrderStatus::Confirmed,
            $hours < 24 => $roll <= 30 ? OrderStatus::Pending : ($roll <= 70 ? OrderStatus::Confirmed : OrderStatus::Processing),
            $hours < 72 => $roll <= 15 ? OrderStatus::Confirmed : ($roll <= 45 ? OrderStatus::Processing : ($roll <= 92 ? OrderStatus::Shipped : OrderStatus::Cancelled)),
            $hours < 168 => $roll <= 40 ? OrderStatus::Shipped : ($roll <= 90 ? OrderStatus::Delivered : ($roll <= 96 ? OrderStatus::Cancelled : OrderStatus::Returned)),
            default => $roll <= 80 ? OrderStatus::Delivered : ($roll <= 89 ? OrderStatus::Cancelled : ($roll <= 96 ? OrderStatus::Returned : OrderStatus::Shipped)),
        };

        $flow = [OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        $path = match ($target) {
            OrderStatus::Pending => [],
            OrderStatus::Cancelled => array_slice($flow, 0, random_int(0, 2)),
            OrderStatus::Returned => $flow,
            default => array_slice($flow, 0, array_search($target, $flow, true) + 1),
        };
        if (in_array($target, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
            $path[] = $target;
        }

        $notes = [
            'confirmed' => ['Called customer, order confirmed.', null, null],
            'processing' => ['Packed and ready for pickup.', null],
            'shipped' => [null],
            'delivered' => ['Delivered successfully.', null],
            'cancelled' => ['Customer did not pick up the phone (3 calls).', 'Customer ordered by mistake.', 'Duplicate order.', 'Customer wants a different colour, will reorder.'],
            'returned' => ['Wrong size — customer returned.', 'Product damaged in transit.', 'Customer refused at doorstep.'],
        ];
        $comments = ['Customer asked for delivery after 5 PM.', 'Gift wrap requested.', 'Customer wants to change size to L.', 'Address landmark: opposite the mosque.', 'VIP customer — handle with care.'];

        $at = $placedAt->copy();
        $limit = now();

        if (random_int(1, 7) === 1) {
            Carbon::setTestNow($placedAt->copy()->addMinutes(random_int(10, 90))->min($limit));
            $this->orders->addNote($order, $comments[array_rand($comments)], $this->adminId);
        }

        foreach ($path as $status) {
            $at = $at->copy()->addHours(match ($status) {
                OrderStatus::Confirmed => random_int(1, 4),
                OrderStatus::Delivered => random_int(24, 72),
                default => random_int(3, 20),
            })->min($limit->copy()->subMinutes(1));
            Carbon::setTestNow($at);

            if ($status === OrderStatus::Shipped) {
                $courier = ['steadfast', 'steadfast', 'pathao', 'redx'][random_int(0, 3)];
                $tracking = match ($courier) {
                    'steadfast' => strtoupper(Str::random(8)),
                    'pathao' => 'DL'.$at->format('dmy').strtoupper(Str::random(6)),
                    default => $at->format('y').strtoupper(Str::random(8)),
                };
                $couriers->assign($order->fresh(), $courier, $tracking, null, null, $this->adminId);
            }

            $order = $order->fresh();
            $this->orders->changeStatus($order, $status, $notes[$status->value][array_rand($notes[$status->value])], $this->adminId);

            $paid = $order->fresh()->paidAmount();
            if ($status->releasesStock() && $paid > 0) {
                $this->orders->refund($order->fresh(), ['amount' => $paid, 'method' => $order->payment_method->value, 'trx_id' => strtoupper(Str::random(10)), 'note' => 'Refunded — order '.$status->value], $this->adminId);
            }
        }

        Carbon::setTestNow();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Customer>  $customers
     */
    private function reviews(Collection $data, Collection $products, Collection $customers): void
    {
        $ratings = $data->mapWithKeys(fn ($item) => [$item['sku'] => collect($item['reviews'])->pluck('rating')->all()]);

        Order::where('status', OrderStatus::Delivered)->whereNotNull('customer_id')->with('items', 'customer')->get()
            ->each(function (Order $order) use ($ratings, $products) {
                foreach ($order->items as $item) {
                    if (random_int(1, 100) > 40 || ! $item->product_id) {
                        continue;
                    }

                    $product = $products->firstWhere('id', $item->product_id);
                    $pool = $ratings[$product->sku] ?? [5, 4];
                    $rating = $pool ? $pool[array_rand($pool)] : 5;
                    $rating = max(1, min(5, random_int(1, 4) === 1 ? $rating : max($rating, 4)));
                    $at = $order->delivered_at->copy()->addDays(random_int(0, 6))->min(now());

                    $review = Review::create([
                        'product_id' => $product->id,
                        'customer_id' => $order->customer_id,
                        'name' => $order->customer->name,
                        'rating' => $rating,
                        'comment' => self::REVIEW_COMMENTS[$rating][array_rand(self::REVIEW_COMMENTS[$rating])],
                        'is_approved' => $at->diffInDays(now()) > 3 ? random_int(1, 10) > 1 : random_int(0, 1) === 1,
                    ]);
                    $review->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                }
            });

        // Block two customers with a history of refused parcels
        $customers->filter(fn ($customer) => $customer->orders()->exists())->shuffle()->take(2)->each(fn ($customer, $i) => $customer->update([
            'is_blocked' => true,
            'block_reason' => ['Refused delivery 3 times (fake orders).', 'Abusive behaviour with the delivery rider.'][$i],
        ]));
    }

    /**
     * Make the low-stock / out-of-stock alerts and the hidden state visible in the demo.
     *
     * @param  Collection<int, Product>  $products
     */
    private function finishStock(Collection $products): void
    {
        $simple = $products->where('has_variants', false)->shuffle();

        $simple->take(6)->each(fn ($product) => $product->update(['stock' => random_int(1, 3)]));
        $simple->slice(6, 2)->each(fn ($product) => $product->update(['stock' => 0]));
        $simple->slice(8, 3)->each(fn ($product) => $product->update(['is_active' => false]));

        $products->where('has_variants', true)->shuffle()->take(3)->each(function ($product) {
            $product->variants()->inRandomOrder()->take(2)->update(['stock' => 0]);
            $product->fresh()->syncStockFromVariants();
        });
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function banners(array $categories): void
    {
        $photo = fn (string $key, int $nth = 0) => $this->photoPath(['category' => $key, 'title' => Product::where('category_id', $categories[$key]->id)->orderBy('id')->skip($nth)->value('title')], 0);

        $sliders = [
            ['New Arrivals', 'Eid Collection 2026', 'Up to 40% off on dresses, shirts & shoes', 'Shop Now', ['womens-dresses', 'mens-shirts', 'womens-shoes'], [124, 58, 237], [219, 39, 119], '/category/women'],
            ['Flash Sale', 'Mega Electronics Sale', 'Smartphones, laptops & gadgets at best prices', 'Grab the Deal', ['smartphones', 'laptops', 'tablets'], [14, 116, 144], [37, 99, 235], '/campaign/mega-flash-sale'],
            ['Beauty & Health', 'Glow Up Beauty Week', '100% original fragrances & skin care', 'Shop Beauty', ['fragrances', 'skin-care', 'beauty'], [190, 24, 93], [251, 113, 133], '/category/beauty-health'],
            ['Home & Living', 'Upgrade Your Home', 'Decor, kitchen & furniture essentials', 'Explore', ['home-decoration', 'furniture', 'kitchen-accessories'], [21, 128, 61], [101, 163, 13], '/category/home-living'],
        ];
        $promos = [
            ['Limited offer', 'Free Delivery', 'On all orders above BDT 2,000', 'Order Now', ['mens-watches'], [234, 88, 12], [245, 158, 11], '/page/shipping-delivery', true],
            ['Timeless', 'Watch Collection', 'Classic & smart watches', 'Explore', ['womens-watches', 'mens-watches'], [30, 41, 59], [71, 85, 105], '/category/mens-watches', true],
            ['Stay Active', 'Sports Gear', 'Everything for your game', 'Shop Now', ['sports-accessories'], [194, 65, 12], [220, 38, 38], '/category/sports-outdoor', true],
            ['Pohela Boishakh', 'Boishakhi Offer', 'Coming soon — 20% off', null, ['tops', 'womens-jewellery'], [185, 28, 28], [234, 88, 12], null, false],
        ];

        foreach ([['slider', $sliders, 1600, 640], ['promo', $promos, 800, 400]] as [$position, $items, $width, $height]) {
            foreach ($items as $sort => $row) {
                [$eyebrow, $title, $subtitle, $button, $keys, $from, $to, $link] = $row;
                $image = $this->path('banners/'.$position.'-'.Str::slug($title).'.jpg');
                $photos = collect($keys)->map(fn ($key, $i) => $photo($key, $i))->all();
                $this->images->banner($image, $width, $height, $eyebrow, $title, $subtitle, $button, $photos, $from, $to);

                Banner::create([
                    'title' => $title, 'subtitle' => $subtitle, 'link' => $link, 'button_text' => $button,
                    'position' => $position, 'sort_order' => $sort + 1, 'is_active' => $row[8] ?? true,
                ])->addMediaFromDisk($image, 'image');
            }
        }
    }

    /**
     * Storefront FAQ entries (safe to run again: matched by question).
     */
    public function faqs(): void
    {
        $faqs = [
            'Orders' => [
                ['How do I place an order?', '<p>Add products to your cart, go to checkout, choose your delivery address and payment method, then press <b>Place Order</b>. You will get an SMS with your order number.</p>'],
                ['Can I order without creating an account?', '<p>Yes. Guest checkout is available. With an account you can save addresses, keep a wishlist and follow every order from your dashboard.</p>'],
                ['Can I cancel my order?', '<p>You can cancel it yourself from <b>My Orders</b> while it is still <b>Pending</b>. After it is confirmed, please call our hotline.</p>'],
            ],
            'Payment' => [
                ['Is cash on delivery available?', '<p>Yes, everywhere in Bangladesh. You pay the rider when you receive the parcel.</p>'],
                ['Which other payment methods do you accept?', '<p>bKash, Nagad and cards (Visa, Mastercard, Amex) through SSLCommerz.</p>'],
                ['Can I open the parcel before paying?', '<p>Yes, you can check the product in front of the rider before paying.</p>'],
            ],
            'Delivery' => [
                ['How long does delivery take?', '<p>1-2 days inside Dhaka, 2-3 days in the suburbs and 3-5 days outside Dhaka.</p>'],
                ['How can I track my order?', '<p>Use <b>Track Order</b> with your order number and phone, or open the order in your account. You will also get the courier tracking ID by SMS.</p>'],
                ['Is delivery free?', '<p>Delivery is free on orders of ৳2,000 or more.</p>'],
            ],
            'Returns & Warranty' => [
                ['What is your return policy?', '<p>You can return a damaged, defective or wrong product within <b>7 days</b> of delivery. See the Return &amp; Refund Policy page for details.</p>'],
                ['Are the products original?', '<p>Yes. We only sell genuine products from brands and authorised distributors, many with official warranty.</p>'],
            ],
        ];

        $sort = 0;
        foreach ($faqs as $category => $items) {
            foreach ($items as [$question, $answer]) {
                Faq::updateOrCreate(['question' => $question], ['category' => $category, 'answer' => $answer, 'sort_order' => ++$sort, 'is_active' => true]);
            }
        }
    }

    private function pages(): void
    {
        $store = 'ShopNest BD';
        $pages = [
            'about-us' => ['About Us', "<h3>Welcome to {$store}</h3><p>{$store} is a Dhaka-based online shop that brings genuine fashion, electronics, beauty and home products to customers all over Bangladesh. Since 2021 we have delivered more than <b>50,000 orders</b> to all 64 districts.</p><h4>Why shop with us?</h4><ul><li><b>100% original products</b> — sourced from brands and authorised distributors.</li><li><b>Cash on delivery</b> everywhere in Bangladesh, plus bKash, Nagad and card payment.</li><li><b>Fast delivery</b> — 1-2 days inside Dhaka, 3-5 days outside.</li><li><b>7 days easy return</b> if anything is wrong with your order.</li></ul><h4>Our promise</h4><p>Every order is checked by our quality team before it is packed. If you are not happy, our support team is ready to help from 10 AM to 10 PM, Saturday to Thursday.</p>", 'About ShopNest BD — Genuine products, delivered across Bangladesh'],
            'privacy-policy' => ['Privacy Policy', "<p>This policy explains how {$store} collects and uses your information.</p><h4>Information we collect</h4><ul><li>Name, phone number, email and delivery address you give us when you order.</li><li>Order history and the products you view, to improve our service.</li><li>Payment confirmations from bKash, Nagad or SSLCommerz. <b>We never see or store your card number or PIN.</b></li></ul><h4>How we use it</h4><ul><li>To deliver your order and keep you updated by SMS and email.</li><li>To share your name, phone and address with our courier partners (Steadfast, Pathao, RedX) for delivery only.</li><li>To send offers — you can opt out any time.</li></ul><h4>Your rights</h4><p>You can ask us to update or delete your data by emailing support@shopnest.com.bd.</p>", null],
            'return-policy' => ['Return & Refund Policy', '<h4>7 days easy return</h4><p>You can return a product within <b>7 days of delivery</b> if it is:</p><ul><li>Damaged or defective</li><li>Different from what you ordered (wrong size, colour or item)</li><li>Unused, with original tags and packaging</li></ul><h4>Not returnable</h4><ul><li>Innerwear, cosmetics and fragrances once opened</li><li>Products damaged by misuse</li></ul><h4>How to return</h4><ol><li>Call 09612-345678 or message us on Facebook with your order number.</li><li>Our courier will collect the product from your address.</li><li>After a quality check, we send a replacement or refund.</li></ol><h4>Refunds</h4><p>Refunds go to your bKash / Nagad number or bank account within <b>5-7 working days</b>. Delivery charges are not refundable unless the mistake was ours.</p>', null],
            'terms-and-conditions' => ['Terms & Conditions', "<p>By using this website you agree to these terms.</p><ul><li>Prices and stock can change without notice. The price at the time of order confirmation applies.</li><li>We may cancel an order if a product is out of stock or the price was shown wrongly; any payment will be refunded in full.</li><li>Customers who repeatedly refuse cash-on-delivery parcels may be blocked from ordering.</li><li>Coupons cannot be combined and have no cash value.</li><li>All content on this site belongs to {$store}.</li></ul>", null],
            'shipping-delivery' => ['Shipping & Delivery', '<table class="table table-bordered"><thead><tr><th>Area</th><th>Charge</th><th>Time</th></tr></thead><tbody><tr><td>Inside Dhaka</td><td>৳60</td><td>1-2 days</td></tr><tr><td>Dhaka Suburbs (Gazipur, Narayanganj, Savar)</td><td>৳100</td><td>2-3 days</td></tr><tr><td>Outside Dhaka</td><td>৳120</td><td>3-5 days</td></tr></tbody></table><p><b>Free delivery</b> on orders of ৳2,000 or more.</p><p>We ship with Steadfast, Pathao and RedX. You will get an SMS with the tracking number as soon as your parcel is handed over.</p>', null],
            'faq' => ['FAQ', '<h5>How do I place an order?</h5><p>Add products to your cart, enter your address and choose a payment method. You will get a confirmation call or SMS.</p><h5>Is cash on delivery available?</h5><p>Yes, everywhere in Bangladesh.</p><h5>Can I open the parcel before paying?</h5><p>Yes, you can check the product in front of the rider before paying.</p><h5>How can I track my order?</h5><p>Use the tracking number in your SMS on the courier website, or call our hotline.</p><h5>Are the products original?</h5><p>Yes. We only sell genuine products from brands and authorised distributors.</p>', null],
            'contact-us' => ['Contact Us', '<p><b>Hotline:</b> 09612-345678 (10 AM – 10 PM, Sat – Thu)<br><b>WhatsApp:</b> +8801711-000000<br><b>Email:</b> support@shopnest.com.bd</p><p><b>Office:</b> House 12, Road 7, Dhanmondi, Dhaka 1205</p><p>Facebook: <a href="https://facebook.com/shopnestbd">facebook.com/shopnestbd</a></p>', null],
            'career' => ['Career', '<p>We are hiring! Send your CV to jobs@shopnest.com.bd.</p>', null, false],
        ];

        foreach ($pages as $slug => $page) {
            [$title, $content, $meta] = $page;
            Page::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => $content,
                'meta_title' => $meta ?? "{$title} | {$store}",
                'meta_description' => Str::limit(strip_tags($content), 155),
                'is_active' => $page[3] ?? true,
            ]);
        }
    }

    /**
     * @param  array{category: string, title: string}  $item
     */
    private function photoPath(array $item, int $index): string
    {
        return $this->path('products/'.$item['category'].'/'.Str::slug($item['title']).'-'.($index + 1).'.webp');
    }

    private function path(string $relative): string
    {
        return config('ecom.upload_dir').'/demo/'.$relative;
    }

    /**
     * USD demo price → a believable taka price (৳1,190 / ৳24,990).
     */
    private function bdt(float $usd): float
    {
        return $this->roundTaka($usd * 118);
    }

    private function roundTaka(float $taka): float
    {
        return $taka < 1000 ? max(99, round($taka / 10) * 10) : round($taka / 50) * 50 - 10;
    }

    private function uniqueSlug(string $title): string
    {
        $slug = $base = Str::slug($title);
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function description(array $item, string $brand): string
    {
        $points = collect([
            'Brand' => $brand,
            'Warranty' => $item['warrantyInformation'] ?? null,
            'Delivery' => 'Inside Dhaka 1-2 days, outside Dhaka 3-5 days',
            'Return' => '7 days easy return',
            'Payment' => 'Cash on delivery, bKash, Nagad, card',
        ])->filter()->map(fn ($value, $label) => "<li><b>{$label}:</b> ".e($value).'</li>')->implode('');

        return '<p>'.e($item['description']).'</p><ul>'.$points.'</ul>';
    }

    /**
     * @return array{0: string, 1: string, 2: string} zone, city, area
     */
    private function randomAddress(int $seed): array
    {
        $zone = match (true) {
            $seed % 10 < 5 => 'Inside Dhaka',
            $seed % 10 < 7 => 'Dhaka Suburbs',
            default => 'Outside Dhaka',
        };
        $city = array_rand(self::ADDRESSES[$zone]);
        $areas = self::ADDRESSES[$zone][$city];

        return [$zone, $city, $areas[array_rand($areas)]];
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function colorFor(string $category): array
    {
        foreach (self::CATEGORIES as [$color, $children]) {
            if (isset($children[$category])) {
                return $color;
            }
        }

        return [71, 85, 105];
    }
}

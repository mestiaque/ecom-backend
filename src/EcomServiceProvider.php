<?php

namespace ME\Ecom;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Support\EcomSettings;
use ME\Services\MediaRegistry;

class EcomServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Routes use metheme's me_prefix() helper, which metheme loads in its own boot().
        // Packages boot alphabetically (ecom before metheme), so wait until every provider has booted.
        $this->app->booted(function (): void {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });

        // Own counter for the public tracking form (a plain "throttle:10,1" shares one counter per visitor across all throttled routes)
        RateLimiter::for('ecom-track', fn (Request $request) => Limit::perMinute(10)->by($request->ip())
            ->response(fn () => back()->withInput()->withErrors(['order_number' => 'Too many tries. Please wait a minute and try again.'])));

        $this->loadViewsFrom(__DIR__.'/resources/views', 'ecom');
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'ecom');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([__DIR__.'/Config/config.php' => config_path('ecom.php')], 'ecom-config');

        $this->registerMedia();

        // metheme has no dashboard page: the shop dashboard is the admin home (unless METHEME_HOME_ROUTE says otherwise)
        if (! config('me_settings.home_route')) {
            Config::set('me_settings.home_route', 'ecom.dashboard');
        }

        // Sidebar entries are a numeric array — must array_merge, not mergeConfigFrom
        if (file_exists($sidebar = __DIR__.'/Config/sidebar.php')) {
            Config::set('sidebar', array_merge(
                config('sidebar', []),
                require $sidebar
            ));
        }
    }

    public function register(): void
    {
        require_once __DIR__.'/Support/helpers.php';

        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'ecom');
        $this->mergeConfigFrom(__DIR__.'/Config/permission.php', 'permissions');

        $this->app->singleton(EcomSettings::class);
        $this->app->singleton(CourierManager::class);

    }

    /**
     * Files are kept in metheme's me_media table (HasMedia trait). Tell metheme the owner names
     * for the Media Library and where the old image columns were, for "php artisan metheme:media-import".
     */
    private function registerMedia(): void
    {
        foreach ([
            Models\Product::class => 'Product', Models\Category::class => 'Category', Models\Brand::class => 'Brand',
            Models\Banner::class => 'Banner', Models\Campaign::class => 'Campaign', Models\Customer::class => 'Customer',
        ] as $class => $name) {
            MediaRegistry::owner($class, $name);
        }

        MediaRegistry::import(['type' => 'table', 'table' => 'ecom_product_images', 'model' => Models\Product::class, 'foreign_key' => 'product_id',
            'path_column' => 'path', 'collection' => 'gallery', 'order_column' => 'sort_order', 'conversions' => ['thumb' => 'thumbnail']]);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Category::class, 'column' => 'image', 'collection' => 'image']);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Category::class, 'column' => 'banner', 'collection' => 'banner']);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Brand::class, 'column' => 'logo', 'collection' => 'logo']);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Banner::class, 'column' => 'image', 'collection' => 'image']);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Campaign::class, 'column' => 'banner', 'collection' => 'banner']);
        MediaRegistry::import(['type' => 'column', 'model' => Models\Customer::class, 'column' => 'avatar', 'collection' => 'avatar']);
        MediaRegistry::import(['type' => 'setting', 'key' => 'ecom_store_logo']);
        MediaRegistry::import(['type' => 'setting', 'key' => 'ecom_store_favicon']);

        // Variant photos pointed at ecom_product_images rows; point them at the imported media instead
        MediaRegistry::afterImport(function (callable $mediaIdFor) {
            if (! Schema::hasColumn('ecom_product_variants', 'product_image_id') || ! Schema::hasColumn('ecom_product_variants', 'media_id')) {
                return;
            }
            DB::table('ecom_product_variants')->whereNotNull('product_image_id')->orderBy('id')->each(function ($variant) use ($mediaIdFor) {
                DB::table('ecom_product_variants')->where('id', $variant->id)
                    ->update(['media_id' => $mediaIdFor('ecom_product_images:'.$variant->product_image_id)]);
            });
        });
    }
}

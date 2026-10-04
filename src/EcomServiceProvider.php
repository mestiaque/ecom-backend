<?php

namespace ME\Ecom;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use ME\Ecom\Console\GenerateThumbnails;
use ME\Ecom\Services\Couriers\CourierManager;
use ME\Ecom\Support\EcomSettings;

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

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateThumbnails::class]);
        }
    }
}

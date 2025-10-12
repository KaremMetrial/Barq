<?php

namespace App\Providers;

use Modules\User\Models\User;
use Modules\Admin\Models\Admin;
use Modules\Store\Models\Store;
use Modules\Vendor\Models\Vendor;
use Modules\Product\Models\Product;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'store' => Store::class,
            'user' => User::class,
            'vendor' => Vendor::class,
            'admin' => Admin::class,
            'product' => Product::class,
        ]);
    }
}

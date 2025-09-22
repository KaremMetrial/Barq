<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Store\Models\Store;
use Modules\Vendor\Models\Vendor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'store' => Store::class,
            // 'user' => User::class,
            'vendor' => Vendor::class,
        ]);
    }
}

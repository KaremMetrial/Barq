<?php

namespace App\Providers;

use Modules\User\Models\User;
use Modules\Store\Models\Store;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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
            'user' => User::class,
            'vendor' => Vendor::class,
        ]);
    }
}

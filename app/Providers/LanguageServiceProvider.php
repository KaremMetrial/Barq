<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use App\Services\LanguageService;

class LanguageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LanguageService::class, function ($app) {
            return new LanguageService($app->make(\App\Repositories\LanguageRepository::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(LanguageService $languageService): void
    {
        if (Schema::hasTable('languages')) {
            $languages = $languageService->getAllCodes();
            Config::set('translatable.locales', $languages);
        }
    }
}

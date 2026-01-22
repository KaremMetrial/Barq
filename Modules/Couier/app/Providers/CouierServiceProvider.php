<?php

namespace Modules\Couier\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Couier\Services\SmartOrderAssignmentService;
use Modules\Couier\Services\RealTimeCourierService;
use Modules\Couier\Services\GeographicCourierService;

class CouierServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Couier';

    protected string $nameLower = 'couier';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Register services for dependency injection
        // This ensures that all courier-related services are properly resolved by Laravel's container

        /**
         * NOTE: The following services have been commented out due to the error:
         * "Target class [Modules\Couier\Services\SmartOrderAssignmentService] does not exist."
         *
         * This error occurs when Laravel's service container cannot resolve the SmartOrderAssignmentService
         * class, typically because:
         * 1. The service is not properly registered in the container
         * 2. There are missing dependencies (RealTimeCourierService, GeographicCourierService)
         * 3. Autoloading issues prevent the class from being found
         *
         * To fix this issue, you would need to:
         * 1. Uncomment the service registrations below
         * 2. Ensure all dependent services exist and are properly imported
         * 3. Clear Laravel's cache with: php artisan optimize:clear
         *
         * The services remain commented out to prevent application errors while
         * maintaining the code for future reference or when the dependencies are properly set up.
         */

        /**
         * Register SmartOrderAssignmentService with its dependencies
         * This service handles automatic courier assignment for orders
         * It requires RealTimeCourierService for real-time notifications
         * and GeographicCourierService for location-based courier selection
         */
        // $this->app->bind(SmartOrderAssignmentService::class, function ($app) {
        //     return new SmartOrderAssignmentService(
        //         $app->make(RealTimeCourierService::class),
        //         $app->make(GeographicCourierService::class)
        //     );
        // });

        /**
         * Register RealTimeCourierService
         * This service handles real-time communication with couriers
         * including order assignments, status updates, and location tracking
         */
        // $this->app->bind(RealTimeCourierService::class, function ($app) {
        //     return new RealTimeCourierService();
        // });

        /**
         * Register GeographicCourierService
         * This service handles geographic calculations and courier selection
         * based on proximity, availability, and other location-based factors
         */
        // $this->app->bind(GeographicCourierService::class, function ($app) {
        //     return new GeographicCourierService();
        // });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Couier\Console\CloseOverdueShifts::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            $schedule->command('couier:close-overdue-shifts')->everyFifteenMinutes();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }
}

<?php

namespace Modules\Order\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Order\Events\OrderStatusChanged::class => [
            \Modules\Order\Listeners\SaveOrderStatusHistory::class,
            \Modules\Order\Listeners\AutoAssignCourierListener::class,
            \Modules\Balance\Listeners\UpdateBalanceOnOrderDelivered::class,
        ],
        // \Modules\Order\Events\OrderNotAcceptedOnTime::class => [
            // \Modules\Order\Listeners\CancelOrderIfNotAccepted::class,
        // ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}

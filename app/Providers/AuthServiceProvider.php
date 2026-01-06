<?php

namespace App\Providers;

use Modules\Page\Models\Page;
use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Modules\Zone\Models\Zone;
use Modules\Admin\Models\Admin;
use Modules\Offer\Models\Offer;
use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Modules\Coupon\Models\Coupon;
use Modules\Review\Models\Review;
use Modules\Reward\Models\Reward;
use Modules\Vendor\Models\Vendor;
use Modules\Product\Models\Product;
use Modules\Section\Models\Section;
use Modules\Setting\Models\Setting;
use Modules\Vehicle\Models\Vehicle;
use Modules\Category\Models\Category;
use Modules\Page\Policies\PagePolicy;
use Modules\PosShift\Models\PosShift;
use Modules\Role\Policies\RolePolicy;
use Modules\User\Policies\UserPolicy;
use Modules\Zone\Policies\ZonePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Admin\Policies\AdminPolicy;
use Modules\Offer\Policies\OfferPolicy;
use Modules\Order\Policies\OrderPolicy;
use Modules\Store\Policies\StorePolicy;
use Modules\Coupon\Policies\CouponPolicy;
use Modules\Review\Policies\ReviewPolicy;
use Modules\Reward\Policies\RewardPolicy;
use Modules\Vendor\Policies\VendorPolicy;
use Modules\Withdrawal\Models\Withdrawal;
use Modules\WorkingDay\Models\WorkingDay;
use Modules\PosTerminal\Models\PosTerminal;
use Modules\Product\Policies\ProductPolicy;
use Modules\Section\Policies\SectionPolicy;
use Modules\Setting\Policies\SettingPolicy;
use Modules\Vehicle\Policies\VehiclePolicy;
use Modules\Category\Policies\CategoryPolicy;
use Modules\PosShift\Policies\PosShiftPolicy;
use Modules\ShippingPrice\Models\ShippingPrice;
use Modules\Withdrawal\Policies\WithdrawalPolicy;
use Modules\WorkingDay\Policies\WorkingDayPolicy;
use Modules\PosTerminal\Policies\PosTerminalPolicy;
use Modules\ShippingPrice\Policies\ShippingPricePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Admin & Core
        Admin::class => AdminPolicy::class,
        Role::class => RolePolicy::class,
        Setting::class => SettingPolicy::class,

        // User Management
        User::class => UserPolicy::class,
        Vendor::class => VendorPolicy::class,

        // Business Entities
        Store::class => StorePolicy::class,
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,

        // Orders & Transactions
        Order::class => OrderPolicy::class,
        Withdrawal::class => WithdrawalPolicy::class,
        Reward::class => RewardPolicy::class,

        // Reviews & Content
        Review::class => ReviewPolicy::class,
        Page::class => PagePolicy::class,
        Section::class => SectionPolicy::class,

        // Logistics
        Zone::class => ZonePolicy::class,
        ShippingPrice::class => ShippingPricePolicy::class,
        Vehicle::class => VehiclePolicy::class,
        WorkingDay::class => WorkingDayPolicy::class,

        // Operations
        PosShift::class => PosShiftPolicy::class,
        PosTerminal::class => PosTerminalPolicy::class,
        Coupon::class => CouponPolicy::class,
        Offer::class => OfferPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

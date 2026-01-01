<?php

use Modules\User\Models\User;
use Modules\Admin\Models\Admin;
use Modules\Couier\Models\Couier;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('order.{orderId}', function ($user, $orderId) {
    return match (true) {
        $user instanceof Admin => true,
        $user instanceof Vendor => $user->store->orders()->where('id', $orderId)->exists(),
        $user instanceof Couier => $user->assignments()->where('id', $orderId)->exists(),
        $user instanceof User => $user->orders()->where('id', $orderId)->exists(),
        default => false,
    };
});
Broadcast::channel('couriers',function ($user) {
    return $user instanceof Couier;
});

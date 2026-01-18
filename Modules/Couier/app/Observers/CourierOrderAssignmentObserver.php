<?php

namespace Modules\Couier\Observers;

use Modules\Couier\Models\CourierOrderAssignment;

class CourierOrderAssignmentObserver
{
    /**
     * Handle the CourierOrderAssignment "created" event.
     */
    public function created(CourierOrderAssignment $courierorderassignment): void {}

    /**
     * Handle the CourierOrderAssignment "updated" event.
     */
    public function updated(CourierOrderAssignment $courierorderassignment): void {
        $courierorderassignment->refresh();
            if($courierorderassignment->status == 'delivered') {
                $order = $courierorderassignment->order;
                if($order && $order->status != \App\Enums\OrderStatus::DELIVERED) {
                    $order->update(['status' => \App\Enums\OrderStatus::DELIVERED]);
                }
            }

            // When courier status changes to in_transit, update order status to on_the_way
            if($courierorderassignment->status == 'in_transit') {
                $order = $courierorderassignment->order;
                if($order && $order->status != \App\Enums\OrderStatus::ON_THE_WAY) {
                    $order->update(['status' => \App\Enums\OrderStatus::ON_THE_WAY]);
                }
            }

    }

    /**
     * Handle the CourierOrderAssignment "deleted" event.
     */
    public function deleted(CourierOrderAssignment $courierorderassignment): void {}

    /**
     * Handle the CourierOrderAssignment "restored" event.
     */
    public function restored(CourierOrderAssignment $courierorderassignment): void {}

    /**
     * Handle the CourierOrderAssignment "force deleted" event.
     */
    public function forceDeleted(CourierOrderAssignment $courierorderassignment): void {}
}

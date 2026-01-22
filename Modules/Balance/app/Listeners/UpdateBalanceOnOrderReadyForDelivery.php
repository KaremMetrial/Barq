<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Enums\TransactionStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;

class UpdateBalanceOnOrderReadyForDelivery implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order status changes to ready_for_delivery
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus != OrderStatus::READY_FOR_DELIVERY->value) {
            return;
        }

        // Logic removed to prevent double counting commission and balance updates.
        // Balance updates are now handled solely in UpdateBalanceOnOrderDelivered.
    }
}

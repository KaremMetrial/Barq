<?php

namespace Modules\Balance\Listeners;

use App\Enums\OrderStatus;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatusEnum;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;

class UpdateBalanceOnOrderOnTheWay implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     * When courier picks up order and pays store in cash.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only process when order status changes to on_the_way
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus != OrderStatus::ON_THE_WAY->value) {
            return;
        }

        // Logic removed to prevent double counting and inconsistent accounting.
        // All financial settlement is now centralized in UpdateBalanceOnOrderDelivered.
    }
}

<?php

namespace Modules\Compaign\Listeners;

use App\Enums\OrderStatus;
use Modules\Order\Events\OrderStatusChanged;
use Modules\Compaign\Models\Compaign;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateCompaignPoints implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderStatusChanged $event): void
    {
        // Ensure we are working with the Delivered status
        $newStatus = $event->newStatus;
        if ($newStatus instanceof OrderStatus) {
            $newStatus = $newStatus->value;
        }

        if ($newStatus !== OrderStatus::DELIVERED->value) {
            return;
        }

        $order = $event->order;
        $storeId = $order->store_id;

        // Calculate points (e.g., 1 point per 1 unit of currency, or just Order Total)
        $pointsToAdd = $order->total_amount;

        if ($pointsToAdd <= 0) {
            return;
        }

        // Find Active Campaigns
        $now = now()->toDateString();
        $activeCompaigns = Compaign::where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->get();

        foreach ($activeCompaigns as $compaign) {
            // Check if store is participating
            $participation = CompaignParicipation::where('compaign_id', $compaign->id)
                ->where('store_id', $storeId)
                ->first();

            if ($participation) {
                // Increment points
                $participation->increment('points', $pointsToAdd);

                Log::info("Campaign Points Updated: Campaign #{$compaign->id}, Store #{$storeId}, Added {$pointsToAdd} points.");
            }
        }
    }
}

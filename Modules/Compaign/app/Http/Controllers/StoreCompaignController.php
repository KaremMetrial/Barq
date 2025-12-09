<?php

namespace Modules\Compaign\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Compaign\Models\Compaign;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Modules\Store\Models\Store;

class StoreCompaignController extends Controller
{
    use ApiResponse;

    public function dashboard(int $id): JsonResponse
    {
        $compaign = Compaign::with(['reward'])->findOrFail($id);

        // Leaderboard: Top 50 participants by points
        $leaderboard = CompaignParicipation::where('compaign_id', $id)
            ->with(['store' => function ($q) {
                $q->select('id', 'logo');
                $q->withTranslation();
            }])
            ->orderByDesc('points')
            ->take(50)
            ->get()
            ->map(function ($participation, $index) {
                return [
                    'rank' => $index + 1,
                    'store_id' => $participation->store_id,
                    'store_name' => $participation->store->name ?? 'Unknown Store',
                    'store_logo' => $participation->store->logo,
                    'points' => $participation->points,
                ];
            });

        // Current User Stats
        // Assuming the authenticated user is a Vendor who owns a Store, or a Store User.
        // We need to identify the "Current Store".
        // For now, I will try to find the store associated with the authenticated vendor.

        $currentUserStats = null;
        $user = auth()->user();

        // Logic to resolve Store from User/Vendor
        // If user is Vendor, we get their store.
        // If user is generic User, we might need another logic, but design says "(You) ... 1300 points".

        $storeId = null;
        if ($user && method_exists($user, 'store')) {
            $storeId = $user->store_id; // Assuming simplistic relation
        } elseif ($user && property_exists($user, 'store_id')) {
            $storeId = $user->store_id;
        }

        // Fallback: If we can't easily resolve, we leave it null or try to find ONE store owned by vendor.
        if (!$storeId && $user) {
            // Try to find store where vendor is owner
            $store = Store::whereHas('owner', function ($q) use ($user) {
                $q->where('id', $user->id); // Assuming user is vendor
            })->first();
            $storeId = $store?->id;
        }

        if ($storeId) {
            $myParticipation = CompaignParicipation::where('compaign_id', $id)
                ->where('store_id', $storeId)
                ->first();

            if ($myParticipation) {
                // Calculate Rank efficiently
                // Count how many have MORE points than me
                $rank = CompaignParicipation::where('compaign_id', $id)
                    ->where('points', '>', $myParticipation->points)
                    ->count() + 1;

                $currentUserStats = [
                    'store_id' => $storeId,
                    'rank' => $rank,
                    'points' => $myParticipation->points,
                    'name' => $myParticipation->store->name ?? '',
                    'logo' => $myParticipation->store->logo ?? '',
                ];
            }
        }

        return $this->successResponse([
            'meta' => [
                'end_date' => $compaign->end_date,
                'is_active' => $compaign->is_active,
                'remaining_seconds' => $compaign->end_date ? now()->diffInSeconds($compaign->end_date, false) : null,
            ],
            'prize' => $compaign->reward ? [
                'title' => $compaign->reward->title,
                'description' => $compaign->reward->description,
                'image' => $compaign->reward->image ? asset('storage/' . $compaign->reward->image) : null,
            ] : null,
            'leaderboard' => $leaderboard,
            'my_stats' => $currentUserStats,
        ], __('message.success'));
    }
}

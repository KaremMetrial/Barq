<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use App\Enums\ProductStatusEnum;
use Illuminate\Support\Facades\Auth;
use Modules\Tag\Http\Resources\TagResource;
use Modules\Unit\Http\Resources\UnitResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"         => $this->id,
            "name"       => $this->name,
            "description" => $this->description,
            "is_active"  => (bool) $this->is_active,
            "max_cart_quantity" => (int) $this->max_cart_quantity,
            "status"     => $this->status?->value,
            "status_label" => ProductStatusEnum::label($this->status->value),
            "note"       => $this->note,
            "is_reviewed" => (bool) $this->is_reviewed,
            "is_vegetarian" => (bool) $this->is_vegetarian,
            "is_featured" => (bool) $this->is_featured,
            "is_favorite" => (bool) User::isFavorite($this->id, 'product'),
            "avg_rates" => $this->avg_rate,
            "barcode" => $this->barcode,
            "images" => ProductImageResource::collection($this->whenLoaded("images")),
            "price" => $this->whenLoaded('price', function () {
                return [
                    "price" => $this->price->price,
                    // "purchase_price" => $this->price->purchase_price,
                    "sale_price" => $this->price->sale?->sale_price,
                ];
            }),
            "store"      => $this->whenLoaded('store', function () {
                return [
                    "id"   => $this->store->id,
                    "name" => $this->store->name,
                    "logo" => $this->store->logo ? asset('storage/' . $this->store->logo) : null,
                    "delivery_time_max" => (int) $this->store->storeSetting?->delivery_time_max,
                ];
            }),
            "category"   => $this->whenLoaded('category', function () {
                return [
                    "id" => $this->category->id,
                    "name" => $this->category->name,
                ];
            }),
            "availability" => new ProductAvailabilityResource($this->whenLoaded("availability")),
            "tags" => TagResource::collection($this->whenLoaded("tags")),
            'units' => $this->whenLoaded('units', function () {
                return $this->units->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'unit_value' => $unit->pivot?->unit_value,
                    ];
                });
            }),
            "productNutrition" => new ProductNutritionResource($this->whenLoaded("ProductNutrition")),
            "productAllergen" => ProductAllergenResource::collection($this->whenLoaded("productAllergen")),
            "pharmacyInfo" => PharmacyInfoResource::collection($this->whenLoaded("pharmacyInfo")),
            "watermarks" => new ProductWatermarksResource($this->whenLoaded("watermark")),
            "cart_quantity" => $this->getCartQuantity(),
        ];
    }
    protected function getCartQuantity(): int
    {
        $token = request()->bearerToken();

        if ($token) {
            [, $tokenHash] = explode('|', $token, 2);

            $userId = \DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $tokenHash))
                ->value('tokenable_id');

            if ($userId) {
                $cart = \Modules\Cart\Models\Cart::with('items')
                    ->where('user_id', $userId)
                    ->first();

                if ($cart) {
                    $item = $cart->items->firstWhere('product_id', $this->id);
                    return $item?->quantity ?? 0;
                }
            }
        }

        $cartKey = request()->header('Cart-Key') ?? request('cart_key');
        if ($cartKey) {
            $cart = \Modules\Cart\Models\Cart::with('items')
                ->where('cart_key', $cartKey)
                ->first();

            if ($cart) {
                $item = $cart->items->firstWhere('product_id', $this->id);
                return $item?->quantity ?? 0;
            }
        }

        return 0;
    }
}

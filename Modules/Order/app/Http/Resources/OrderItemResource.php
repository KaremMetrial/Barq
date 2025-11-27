<?php
namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\ProductOptionValue;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'total_price' => (float) $this->total_price,
            'unit_price' => (float) ($this->total_price / $this->quantity),
            'symbol_currency' => $this->order?->store?->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP',
            'note' => $this->note,
            "selected_options" => $this->getSelectedOptionsString(),

            'product' => $this->when($this->relationLoaded('product'), function() {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->translations->first()?->name ?? 'N/A',
                    'description' => $this->product->translations->first()?->description,
                    'image' => $this->product->images->first()?->image_path ? asset('storage/'.$this->product->images->first()?->image_path) : null,
                    'price' => (float) ($this->product->price?->price ?? 0),
                ];
            }),
            'options' => $this->getOptionsData(),

            // Keep backward compatibility for single option
            'option' => $this->when($this->relationLoaded('productOptionValue'), function() {
                if (!$this->productOptionValue) {
                    return null;
                }

                return [
                    'id' => $this->productOptionValue->id,
                    'name' => $this->productOptionValue->productValue?->translations->first()?->name ?? 'N/A',
                    'price' => (float) $this->productOptionValue->price,
                ];
            }),

            'add_ons' => $this->getAddOnsData(),
        ];
    }

    /**
     * Get options data safely
     */
    private function getOptionsData(): array
    {
        $optionIds = $this->product_option_value_id;

        // Ensure it's always an array
        if (!is_array($optionIds)) {
            $optionIds = $optionIds ? [$optionIds] : [];
        }

        // Filter out null/empty values
        $optionIds = array_filter($optionIds);

        if (empty($optionIds)) {
            return [];
        }

        return collect($optionIds)->map(function($optionId) {
            $option = ProductOptionValue::find($optionId);
            if (!$option) {
                return null;
            }

            return [
                'id' => $option->id,
                'name' => $option->productValue?->translations->first()?->name ?? 'N/A',
                'price' => (float) $option->price,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Get add-ons data safely
     */
    private function getAddOnsData(): array
    {
        if (!$this->relationLoaded('addOns')) {
            return [];
        }

        return $this->addOns->map(function($addOn) {
            return [
                'id' => $addOn->id,
                'name' => $addOn->translations->first()?->name ?? 'N/A',
                'quantity' => $addOn->pivot->quantity,
                'price_modifier' => (float) $addOn->pivot->price_modifier,
                'unit_price' => (float) ($addOn->pivot->price_modifier / $addOn->pivot->quantity),
            ];
        })->toArray();
    }
    private function getSelectedOptionsString(): string
    {
        $parts = [];

        // Add Options
        if (is_array($this->product_option_value_id)) {
            $options = \Modules\Product\Models\ProductOptionValue::whereIn('id', $this->product_option_value_id)
                ->with('productValue.translations')
                ->get();

            foreach ($options as $option) {
                if ($name = $option->productValue?->name) {
                    $parts[] = $name;
                }
            }
        }

        // Add Add-ons
        if ($this->relationLoaded('addOns')) {
            foreach ($this->addOns as $addOn) {
                if ($name = $addOn?->name) {
                    $parts[] = $name;
                }
            }
        }

        return implode(', ', $parts);
    }
}


<?php

namespace Modules\Zone\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Query\Expression;
use Modules\City\Http\Resources\CityResource;
use ReflectionClass;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "area" => $this->parsePolygon($this->area),
            "is_active" => (bool) $this->is_active,
            "city" => new CityResource($this->whenLoaded("city")),
        ];
    }

    protected function parsePolygon($polygonValue): ?array
    {
        if (!$polygonValue) {
            return null;
        }

        // 🔹 Handle DB::raw() Expression object
        if ($polygonValue instanceof Expression) {
            $reflection = new ReflectionClass($polygonValue);
            $property = $reflection->getProperty('value');
            $property->setAccessible(true);
            $polygonValue = $property->getValue($polygonValue);
        }

        // 🔹 Make sure it's now a string
        if (!is_string($polygonValue)) {
            return null;
        }

        // 🔹 Extract coordinates from POLYGON string
        if (preg_match('/POLYGON\(\((.+)\)\)/', $polygonValue, $matches)) {
            $points = explode(',', $matches[1]);
            $coordinates = [];

            foreach ($points as $point) {
                [$lng, $lat] = array_map('floatval', preg_split('/\s+/', trim($point)));
                $coordinates[] = [$lng, $lat];
            }

            return [
                'type' => 'Polygon',
                'coordinates' => [$coordinates],
            ];
        }

        return null;
    }
}

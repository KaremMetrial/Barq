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
            // "area" => $this->parsePolygon($this->area),
            "area" => $this->area,
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
            $point = trim($point);  // Clean up any extra spaces
            $parts = preg_split('/\s+/', $point);

            // Debugging: Log the parts to see what we got
            \Log::debug('Parsed Point:', ['point' => $point, 'parts' => $parts]);

            // Ensure the point has exactly two values: longitude and latitude
            if (count($parts) === 2) {
                $lng = floatval($parts[0]);
                $lat = floatval($parts[1]);
                $coordinates[] = [$lng, $lat];
            } else {
                // If the point is invalid, you can log it
                \Log::error('Invalid point format', ['point' => $point]);
            }
        }

        // Debugging: Log the final coordinates to see if we got the expected results
        \Log::debug('Final Coordinates:', ['coordinates' => $coordinates]);

        // Return the valid coordinates
        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates], // Return the coordinates array inside another array (GeoJSON format)
        ];
    }

    return null;
}
}

<?php

use Modules\Cart\Services\CartService;
use Modules\AddOn\Models\AddOn;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class CartServiceMinorUnitsTest extends TestCase
{
    public function testPrepareAddOnPivotDataProducesMinorUnits()
    {
        // Create fake AddOn model-like object
        $addOnModel = new class {
            public $id = 1;
            public $price = 12.34;
        };

        $addOns = [ ['id' => 1, 'quantity' => 2] ];
        $addOnModels = new Collection([1 => $addOnModel]);

        // Instantiate without constructor to avoid dependencies
        $refClass = new ReflectionClass(\Modules\Cart\Services\CartService::class);
        $service = $refClass->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(\Modules\Cart\Services\CartService::class, 'prepareAddOnPivotData');
        $method->setAccessible(true);

        $result = $method->invoke($service, $addOns, $addOnModels, 100);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals(2468, $result[1]['price_modifier']); // 12.34 * 2 => 24.68 => 2468 cents
        $this->assertEquals(2, $result[1]['quantity']);
    }
}

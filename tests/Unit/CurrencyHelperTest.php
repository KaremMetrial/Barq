<?php

use App\Helpers\CurrencyHelper;
use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    public function test_to_minor_units_rounding()
    {
        $this->assertEquals(1235, CurrencyHelper::toMinorUnits(12.345, 100));
        $this->assertEquals(1234, CurrencyHelper::toMinorUnits(12.344, 100));
    }

    public function test_from_minor_units()
    {
        $this->assertEquals(12.34, CurrencyHelper::fromMinorUnits(1234, 100, 2));
        $this->assertEquals(12.345, CurrencyHelper::fromMinorUnits(12345, 1000, 3));
    }

    public function test_convert_minor_between_currencies()
    {
        $this->assertEquals(12340, CurrencyHelper::convertMinorBetweenCurrencies(1234, 100, 1000));
        $this->assertEquals(1234, CurrencyHelper::convertMinorBetweenCurrencies(1234, 100, 100));
    }
}

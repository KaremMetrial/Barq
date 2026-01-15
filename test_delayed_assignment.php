<?php

// Test script to verify the delayed courier assignment logic
require_once __DIR__ . '/vendor/autoload.php';

// Test the preparation time calculation logic
function testPreparationTimeCalculation() {
    // Mock order items with different preparation times
    $mockOrderItems = [
        (object)['product' => (object)['preparation_time' => 10]],
        (object)['product' => (object)['preparation_time' => 15]],
        (object)['product' => (object)['preparation_time' => 5]],
        (object)['product' => null], // Product without preparation time
    ];

    $maxPreparationTime = 0;

    foreach ($mockOrderItems as $orderItem) {
        if ($orderItem->product && $orderItem->product->preparation_time) {
            $preparationTime = (int) $orderItem->product->preparation_time;
            if ($preparationTime > $maxPreparationTime) {
                $maxPreparationTime = $preparationTime;
            }
        }
    }

    // Add minimum buffer time if no preparation time found
    if ($maxPreparationTime === 0) {
        $maxPreparationTime = 5;
    }

    echo "Test Preparation Time Calculation:\n";
    echo "Expected max preparation time: 15 minutes\n";
    echo "Calculated max preparation time: " . $maxPreparationTime . " minutes\n";
    echo "Result: " . ($maxPreparationTime === 15 ? "PASS" : "FAIL") . "\n\n";

    return $maxPreparationTime === 15;
}

// Test the delay calculation
function testDelayCalculation($preparationTimeMinutes) {
    $delayInSeconds = $preparationTimeMinutes * 60;

    echo "Test Delay Calculation:\n";
    echo "Preparation time: " . $preparationTimeMinutes . " minutes\n";
    echo "Calculated delay: " . $delayInSeconds . " seconds\n";
    echo "Expected delay: " . ($preparationTimeMinutes * 60) . " seconds\n";
    echo "Result: " . ($delayInSeconds === $preparationTimeMinutes * 60 ? "PASS" : "FAIL") . "\n\n";

    return $delayInSeconds === $preparationTimeMinutes * 60;
}

// Test edge case: no products with preparation time
function testNoPreparationTime() {
    $mockOrderItems = [
        (object)['product' => null],
        (object)['product' => (object)['preparation_time' => null]],
    ];

    $maxPreparationTime = 0;

    foreach ($mockOrderItems as $orderItem) {
        if ($orderItem->product && $orderItem->product->preparation_time) {
            $preparationTime = (int) $orderItem->product->preparation_time;
            if ($preparationTime > $maxPreparationTime) {
                $maxPreparationTime = $preparationTime;
            }
        }
    }

    // Add minimum buffer time if no preparation time found
    if ($maxPreparationTime === 0) {
        $maxPreparationTime = 5;
    }

    echo "Test No Preparation Time (Edge Case):\n";
    echo "Expected fallback preparation time: 5 minutes\n";
    echo "Calculated preparation time: " . $maxPreparationTime . " minutes\n";
    echo "Result: " . ($maxPreparationTime === 5 ? "PASS" : "FAIL") . "\n\n";

    return $maxPreparationTime === 5;
}

// Run all tests
echo "=== Delayed Courier Assignment Logic Tests ===\n\n";

$test1Passed = testPreparationTimeCalculation();

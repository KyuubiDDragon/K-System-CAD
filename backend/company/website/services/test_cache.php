#!/usr/bin/env php
<?php
/**
 * Cache Service Test Suite
 *
 * Run comprehensive tests on the CacheService to verify functionality.
 *
 * Usage: php test_cache.php
 */

require_once __DIR__ . '/CacheService.php';

// Color output for terminal
function color($text, $color)
{
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function test($name, $callback)
{
    echo "\nTest: " . color($name, 'blue') . "\n";

    try {
        $result = $callback();
        if ($result) {
            echo color("✓ PASSED", 'green') . "\n";
            return true;
        } else {
            echo color("✗ FAILED", 'red') . "\n";
            return false;
        }
    } catch (Exception $e) {
        echo color("✗ ERROR: " . $e->getMessage(), 'red') . "\n";
        return false;
    }
}

// Test results tracker
$tests_passed = 0;
$tests_failed = 0;

echo color("=== CacheService Test Suite ===\n", 'yellow');

// Test 1: Basic set and get
if (test("Basic set and get", function () {
    $cache = new CacheService();
    $cache->set('test_key', 'test_value', 60);
    $value = $cache->get('test_key');
    return $value === 'test_value';
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 2: Complex data structures
if (test("Complex data structures", function () {
    $cache = new CacheService();
    $data = [
        'string' => 'test',
        'number' => 123,
        'array' => [1, 2, 3],
        'nested' => ['key' => 'value']
    ];
    $cache->set('complex_data', $data, 60);
    $retrieved = $cache->get('complex_data');
    return $retrieved === $data;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 3: Cache expiration
if (test("Cache expiration", function () {
    $cache = new CacheService();
    $cache->set('expiring_key', 'value', 1); // 1 second TTL
    sleep(2);
    $value = $cache->get('expiring_key');
    return $value === null; // Should be expired
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 4: Cache has() method
if (test("Cache has() method", function () {
    $cache = new CacheService();
    $cache->set('exists_key', 'value', 60);
    $exists = $cache->has('exists_key');
    $notExists = $cache->has('nonexistent_key');
    return $exists === true && $notExists === false;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 5: Cache delete
if (test("Cache delete", function () {
    $cache = new CacheService();
    $cache->set('delete_key', 'value', 60);
    $cache->delete('delete_key');
    $value = $cache->get('delete_key');
    return $value === null;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 6: Pattern-based clearing
if (test("Pattern-based clearing", function () {
    $cache = new CacheService();

    // Create multiple cache entries
    $cache->set('website_1_config', 'data1', 60);
    $cache->set('website_1_pages', 'data2', 60);
    $cache->set('website_2_config', 'data3', 60);
    $cache->set('user_123', 'data4', 60);

    // Clear website_1_* entries
    $cleared = $cache->clear('website_1_*');

    // Check results
    $w1_config = $cache->has('website_1_config');
    $w1_pages = $cache->has('website_1_pages');
    $w2_config = $cache->has('website_2_config');
    $user = $cache->has('user_123');

    return $cleared === 2 && !$w1_config && !$w1_pages && $w2_config && $user;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 7: Cached helper function
if (test("Cached helper function", function () {
    $callCount = 0;

    $result1 = cached('helper_test', function () use (&$callCount) {
        $callCount++;
        return 'computed_value';
    }, 60);

    $result2 = cached('helper_test', function () use (&$callCount) {
        $callCount++;
        return 'computed_value';
    }, 60);

    // Should only be called once due to caching
    return $result1 === 'computed_value' && $result2 === 'computed_value' && $callCount === 1;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 8: Cache statistics
if (test("Cache statistics", function () {
    $cache = new CacheService();

    // Create some cache entries
    $cache->set('stats_test_1', 'value1', 60);
    $cache->set('stats_test_2', 'value2', 60);

    $stats = $cache->getStats();

    return isset($stats['total_entries']) &&
        isset($stats['total_size']) &&
        isset($stats['expired_entries']) &&
        $stats['total_entries'] >= 2;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 9: Clean expired entries
if (test("Clean expired entries", function () {
    $cache = new CacheService();

    // Create entries with short TTL
    $cache->set('expire_1', 'value1', 1);
    $cache->set('expire_2', 'value2', 1);
    $cache->set('keep_1', 'value3', 3600); // Long TTL

    sleep(2); // Wait for expiration

    $cleaned = $cache->cleanExpired();

    // Should clean at least the expired entries
    return $cleaned >= 2 && $cache->has('keep_1');
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 10: Thread safety (concurrent writes)
if (test("Thread safety simulation", function () {
    $cache = new CacheService();

    // Simulate multiple writes to same key
    for ($i = 0; $i < 10; $i++) {
        $cache->set('concurrent_key', "value_{$i}", 60);
    }

    $value = $cache->get('concurrent_key');

    // Should have one of the values, not corrupted
    return strpos($value, 'value_') === 0;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 11: Key sanitization
if (test("Key sanitization", function () {
    $cache = new CacheService();

    // Try keys with special characters
    $cache->set('key/with/slashes', 'value1', 60);
    $cache->set('key.with.dots', 'value2', 60);
    $cache->set('key with spaces', 'value3', 60);

    $v1 = $cache->get('key/with/slashes');
    $v2 = $cache->get('key.with.dots');
    $v3 = $cache->get('key with spaces');

    return $v1 === 'value1' && $v2 === 'value2' && $v3 === 'value3';
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 12: Large data storage
if (test("Large data storage", function () {
    $cache = new CacheService();

    // Create large array
    $largeData = [];
    for ($i = 0; $i < 1000; $i++) {
        $largeData[] = [
            'id' => $i,
            'name' => "Item {$i}",
            'data' => str_repeat('x', 100)
        ];
    }

    $cache->set('large_data', $largeData, 60);
    $retrieved = $cache->get('large_data');

    return count($retrieved) === 1000 && $retrieved[0]['id'] === 0;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 13: Performance test
if (test("Performance benchmark", function () {
    $cache = new CacheService();

    // Test write performance
    $start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $cache->set("perf_key_{$i}", "value_{$i}", 60);
    }
    $writeTime = microtime(true) - $start;

    // Test read performance
    $start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $cache->get("perf_key_{$i}");
    }
    $readTime = microtime(true) - $start;

    echo "  Write: " . round($writeTime, 4) . "s for 100 items\n";
    echo "  Read:  " . round($readTime, 4) . "s for 100 items\n";

    // Should complete in reasonable time (< 1 second each)
    return $writeTime < 1.0 && $readTime < 1.0;
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 14: Cache directory creation
if (test("Cache directory auto-creation", function () {
    $tempDir = sys_get_temp_dir() . '/cache_test_' . uniqid();
    $cache = new CacheService($tempDir . '/subfolder/');

    $cache->set('test_key', 'test_value', 60);
    $value = $cache->get('test_key');

    // Cleanup
    @unlink($tempDir . '/subfolder/test_key.cache');
    @rmdir($tempDir . '/subfolder');
    @rmdir($tempDir);

    return $value === 'test_value';
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 15: Default TTL
if (test("Default TTL", function () {
    $cache = new CacheService(null, 7200); // 2 hour default TTL

    $cache->set('default_ttl_key', 'value'); // No TTL specified
    $value = $cache->get('default_ttl_key');

    return $value === 'value';
})) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Cleanup all test caches
echo "\n" . color("Cleaning up test cache entries...", 'yellow') . "\n";
$cache = new CacheService();
$cache->clear('*');

// Print summary
echo "\n" . color("=== Test Summary ===", 'yellow') . "\n";
echo "Passed: " . color($tests_passed, 'green') . "\n";
echo "Failed: " . color($tests_failed, 'red') . "\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n" . color("All tests passed! ✓", 'green') . "\n";
    exit(0);
} else {
    echo "\n" . color("Some tests failed! ✗", 'red') . "\n";
    exit(1);
}

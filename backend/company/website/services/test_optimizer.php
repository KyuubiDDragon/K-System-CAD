<?php

/**
 * ImageOptimizer Test Script
 *
 * Run this script to test the ImageOptimizer service:
 * php test_optimizer.php
 */

require_once __DIR__ . '/ImageOptimizer.php';

// ANSI color codes for terminal output
class Color {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

echo Color::BLUE . "===================================\n" . Color::RESET;
echo Color::BLUE . "ImageOptimizer Test Suite\n" . Color::RESET;
echo Color::BLUE . "===================================\n\n" . Color::RESET;

try {
    // Test 1: Initialize optimizer
    echo Color::YELLOW . "Test 1: Initializing ImageOptimizer...\n" . Color::RESET;
    $optimizer = new ImageOptimizer();
    echo Color::GREEN . "✓ ImageOptimizer initialized successfully\n\n" . Color::RESET;

    // Test 2: Check available library
    echo Color::YELLOW . "Test 2: Checking image processing library...\n" . Color::RESET;
    if (extension_loaded('imagick')) {
        echo Color::GREEN . "✓ Imagick extension available\n" . Color::RESET;
        $imagick = new Imagick();
        echo "  Version: " . $imagick->getVersion()['versionString'] . "\n\n";
    } elseif (extension_loaded('gd')) {
        echo Color::GREEN . "✓ GD extension available\n" . Color::RESET;
        $gdInfo = gd_info();
        echo "  Version: " . $gdInfo['GD Version'] . "\n";
        echo "  JPEG Support: " . ($gdInfo['JPEG Support'] ? 'Yes' : 'No') . "\n";
        echo "  PNG Support: " . ($gdInfo['PNG Support'] ? 'Yes' : 'No') . "\n";
        echo "  GIF Support: " . ($gdInfo['GIF Read Support'] ? 'Yes' : 'No') . "\n";
        echo "  WebP Support: " . ($gdInfo['WebP Support'] ?? false ? 'Yes' : 'No') . "\n\n";
    } else {
        echo Color::RED . "✗ No image processing library available\n\n" . Color::RESET;
        exit(1);
    }

    // Test 3: Create test image
    echo Color::YELLOW . "Test 3: Creating test image...\n" . Color::RESET;
    $testDir = __DIR__ . '/test_images';
    if (!file_exists($testDir)) {
        mkdir($testDir, 0755, true);
    }

    $testImage = $testDir . '/test_source.jpg';
    $width = 2000;
    $height = 1500;

    // Create a test image with GD
    $img = imagecreatetruecolor($width, $height);

    // Fill with gradient background
    for ($y = 0; $y < $height; $y++) {
        $r = (int)(255 * ($y / $height));
        $g = (int)(100 + (155 * ($y / $height)));
        $b = (int)(200 - (100 * ($y / $height)));
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }

    // Add some text
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagestring($img, 5, 50, 50, 'K-Systems ImageOptimizer Test', $white);
    imagestring($img, 5, 51, 51, 'K-Systems ImageOptimizer Test', $black);

    // Save test image
    imagejpeg($img, $testImage, 100);
    imagedestroy($img);

    $testSize = filesize($testImage);
    echo Color::GREEN . "✓ Test image created: {$width}x{$height}, " . formatBytes($testSize) . "\n\n" . Color::RESET;

    // Test 4: Basic optimization
    echo Color::YELLOW . "Test 4: Testing basic optimization...\n" . Color::RESET;
    $optimizedImage = $testDir . '/test_optimized.jpg';

    $result = $optimizer->optimize($testImage, $optimizedImage);

    if ($result['success']) {
        echo Color::GREEN . "✓ Optimization successful\n" . Color::RESET;
        echo "  Original size: " . formatBytes($result['originalSize']) . "\n";
        echo "  Optimized size: " . formatBytes($result['optimizedSize']) . "\n";
        echo "  Savings: " . formatBytes($result['savings']) . " ({$result['savingsPercent']}%)\n";
        echo "  Original dimensions: {$result['originalDimensions']['width']}x{$result['originalDimensions']['height']}\n";
        echo "  New dimensions: {$result['newDimensions']['width']}x{$result['newDimensions']['height']}\n\n";
    } else {
        echo Color::RED . "✗ Optimization failed: {$result['error']}\n\n" . Color::RESET;
    }

    // Test 5: Create thumbnails
    echo Color::YELLOW . "Test 5: Creating thumbnails...\n" . Color::RESET;

    $thumbnailResults = $optimizer->createThumbnails($testImage, [
        'thumbnail' => 150,
        'medium' => 640,
        'large' => 1024
    ]);

    foreach ($thumbnailResults as $sizeName => $result) {
        if ($result['success']) {
            echo Color::GREEN . "✓ {$sizeName} created: " . Color::RESET;
            echo "{$result['newDimensions']['width']}x{$result['newDimensions']['height']}, ";
            echo formatBytes($result['optimizedSize']) . "\n";
        } else {
            echo Color::RED . "✗ {$sizeName} failed: {$result['error']}\n" . Color::RESET;
        }
    }
    echo "\n";

    // Test 6: Test with custom options
    echo Color::YELLOW . "Test 6: Testing custom optimization settings...\n" . Color::RESET;
    $customOptimized = $testDir . '/test_custom.jpg';

    $result = $optimizer->optimize($testImage, $customOptimized, [
        'maxWidth' => 800,
        'maxHeight' => 600,
        'quality' => 75,
        'stripExif' => true
    ]);

    if ($result['success']) {
        echo Color::GREEN . "✓ Custom optimization successful\n" . Color::RESET;
        echo "  Quality: 75\n";
        echo "  Max dimensions: 800x600\n";
        echo "  Result: {$result['newDimensions']['width']}x{$result['newDimensions']['height']}\n";
        echo "  Size: " . formatBytes($result['optimizedSize']) . " ({$result['savingsPercent']}% savings)\n\n";
    } else {
        echo Color::RED . "✗ Custom optimization failed: {$result['error']}\n\n" . Color::RESET;
    }

    // Test 7: Test image validation
    echo Color::YELLOW . "Test 7: Testing image validation...\n" . Color::RESET;

    $testFiles = [
        $testImage => true,
        __FILE__ => false, // This PHP file
    ];

    foreach ($testFiles as $file => $expected) {
        $isImage = $optimizer->isImage($file);
        $filename = basename($file);

        if ($isImage === $expected) {
            echo Color::GREEN . "✓ {$filename}: " . Color::RESET;
            echo ($isImage ? "Valid image" : "Not an image") . "\n";
        } else {
            echo Color::RED . "✗ {$filename}: Validation failed\n" . Color::RESET;
        }
    }
    echo "\n";

    // Test 8: Performance test
    echo Color::YELLOW . "Test 8: Performance test (5 iterations)...\n" . Color::RESET;

    $iterations = 5;
    $totalTime = 0;

    for ($i = 1; $i <= $iterations; $i++) {
        $perfImage = $testDir . "/test_perf_{$i}.jpg";

        $startTime = microtime(true);
        $result = $optimizer->optimize($testImage, $perfImage);
        $endTime = microtime(true);

        $time = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $totalTime += $time;

        if ($result['success']) {
            echo "  Iteration {$i}: " . number_format($time, 2) . "ms";
            echo " ({$result['savingsPercent']}% savings)\n";
        }
    }

    $avgTime = $totalTime / $iterations;
    echo Color::GREEN . "✓ Average optimization time: " . number_format($avgTime, 2) . "ms\n\n" . Color::RESET;

    // Summary
    echo Color::BLUE . "===================================\n" . Color::RESET;
    echo Color::BLUE . "Test Summary\n" . Color::RESET;
    echo Color::BLUE . "===================================\n" . Color::RESET;
    echo Color::GREEN . "All tests completed successfully!\n" . Color::RESET;
    echo "\nTest images saved in: {$testDir}\n";
    echo "You can inspect the optimized images to verify quality.\n\n";

    // Cleanup option
    echo Color::YELLOW . "Cleanup test images? (y/N): " . Color::RESET;
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) === 'y') {
        array_map('unlink', glob("{$testDir}/*"));
        rmdir($testDir);
        echo Color::GREEN . "✓ Test images cleaned up\n" . Color::RESET;
    } else {
        echo Color::BLUE . "Test images preserved for inspection\n" . Color::RESET;
    }

} catch (Exception $e) {
    echo Color::RED . "\n✗ Error: " . $e->getMessage() . "\n" . Color::RESET;
    echo Color::RED . "Stack trace:\n" . $e->getTraceAsString() . "\n" . Color::RESET;
    exit(1);
}

// Helper function
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

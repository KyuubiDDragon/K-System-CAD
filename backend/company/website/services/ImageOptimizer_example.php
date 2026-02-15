<?php

/**
 * ImageOptimizer Usage Examples
 *
 * This file demonstrates how to use the ImageOptimizer service
 * in various scenarios within the K-Systems application.
 */

require_once __DIR__ . '/ImageOptimizer.php';

// ============================================
// EXAMPLE 1: Basic Image Optimization
// ============================================

function example1_basicOptimization()
{
    $optimizer = new ImageOptimizer();

    $sourcePath = '/path/to/uploaded/image.jpg';
    $targetPath = '/path/to/optimized/image.jpg';

    $result = $optimizer->optimize($sourcePath, $targetPath);

    if ($result['success']) {
        echo "Optimization successful!\n";
        echo "Original size: {$result['originalSize']} bytes\n";
        echo "Optimized size: {$result['optimizedSize']} bytes\n";
        echo "Savings: {$result['savingsPercent']}%\n";
    } else {
        echo "Optimization failed: {$result['error']}\n";
    }
}

// ============================================
// EXAMPLE 2: Optimization with Custom Options
// ============================================

function example2_customOptions()
{
    $optimizer = new ImageOptimizer();

    $result = $optimizer->optimize(
        '/path/to/source.png',
        '/path/to/target.png',
        [
            'maxWidth' => 1024,
            'maxHeight' => 768,
            'quality' => 90,
            'stripExif' => true,
            'convertPngToJpeg' => true
        ]
    );

    return $result;
}

// ============================================
// EXAMPLE 3: Generate Multiple Thumbnail Sizes
// ============================================

function example3_createThumbnails()
{
    $optimizer = new ImageOptimizer();

    $sourcePath = '/path/to/original.jpg';

    // Use default sizes (thumbnail: 150px, medium: 640px, large: 1024px)
    $results = $optimizer->createThumbnails($sourcePath);

    foreach ($results as $sizeName => $result) {
        if ($result['success']) {
            echo "$sizeName created: {$result['path']}\n";
        }
    }

    // Or use custom sizes
    $customSizes = [
        'small' => 200,
        'medium' => 500,
        'large' => 1200,
        'xlarge' => 1920
    ];

    $results = $optimizer->createThumbnails($sourcePath, $customSizes);

    return $results;
}

// ============================================
// EXAMPLE 4: Check if File is Valid Image
// ============================================

function example4_validateImage()
{
    $optimizer = new ImageOptimizer();

    $filePath = '/path/to/file.jpg';

    if ($optimizer->isImage($filePath)) {
        echo "File is a valid image\n";
    } else {
        echo "File is not a supported image format\n";
    }
}

// ============================================
// EXAMPLE 5: Integration with MediaController
// ============================================

/**
 * Modified uploadMedia function with automatic image optimization
 */
function uploadMediaWithOptimization(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_POST, 'website_id', FILTER_VALIDATE_INT);

        if (!$websiteId) {
            http_response_code(400);
            echo json_encode(['error' => 'Website ID is required']);
            return;
        }

        // Check if website exists and belongs to this authority
        $stmt = $pdo->prepare("
            SELECT id FROM kdd_website_config
            WHERE id = ? AND authority_id = ?
        ");
        $stmt->execute([$websiteId, $authorityId]);

        if (!$stmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found or access denied']);
            return;
        }

        // Check if a file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['file'];
        $originalName = $file['name'];
        $tempPath = $file['tmp_name'];
        $fileType = $file['type'];
        $fileSize = $file['size'];

        // Validate file type
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type']);
            return;
        }

        // Create directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/website/' . $authorityId . '/' . $websiteId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate a unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalName);
        $uploadPath = $uploadDir . '/' . $filename;

        // Move the uploaded file to temp location first
        if (!move_uploaded_file($tempPath, $uploadPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file']);
            return;
        }

        // Initialize optimizer
        $optimizer = new ImageOptimizer();

        // Check if file is an image
        if ($optimizer->isImage($uploadPath)) {
            // Optimize the main image
            $optimizedPath = $uploadDir . '/optimized_' . $filename;

            $optimizationResult = $optimizer->optimize($uploadPath, $optimizedPath, [
                'maxWidth' => 1920,
                'maxHeight' => 1080,
                'quality' => 85,
                'stripExif' => true,
                'convertPngToJpeg' => true
            ]);

            if ($optimizationResult['success']) {
                // Replace original with optimized version
                unlink($uploadPath);
                rename($optimizedPath, $uploadPath);

                // Create thumbnails
                $thumbnailResults = $optimizer->createThumbnails($uploadPath, [
                    'thumbnail' => 150,
                    'medium' => 640,
                    'large' => 1024
                ]);

                // Update file size to optimized size
                $fileSize = $optimizationResult['optimizedSize'];

                error_log("Image optimized: saved {$optimizationResult['savingsPercent']}%");
            }
        }

        $relativePath = 'uploads/website/' . $authorityId . '/' . $websiteId . '/' . $filename;

        // Determine media type
        $mediaTypeEnum = 'image';
        if (strpos($fileType, 'video/') === 0) {
            $mediaTypeEnum = 'video';
        } elseif (strpos($fileType, 'audio/') === 0) {
            $mediaTypeEnum = 'audio';
        }

        // Save file information to database
        $stmt = $pdo->prepare("
            INSERT INTO kdd_website_media (
                website_id, file_name, file_path, file_type, file_size,
                media_type, authority_id, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $websiteId,
            $filename,
            $relativePath,
            $fileType,
            $fileSize,
            $mediaTypeEnum,
            $authorityId
        ]);

        $mediaId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'File uploaded and optimized successfully',
            'fileName' => $filename,
            'filePath' => $relativePath,
            'mediaId' => $mediaId,
            'optimized' => isset($optimizationResult) ? $optimizationResult['success'] : false,
            'savings' => isset($optimizationResult) ? $optimizationResult['savingsPercent'] : 0
        ]);

    } catch (PDOException $e) {
        error_log("Error in uploadMediaWithOptimization: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error in uploadMediaWithOptimization: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}

// ============================================
// EXAMPLE 6: Batch Process Existing Images
// ============================================

/**
 * Optimize all existing images in a directory
 */
function example6_batchOptimize()
{
    $optimizer = new ImageOptimizer();

    $sourceDir = '/path/to/images/';
    $targetDir = '/path/to/optimized/';

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $files = glob($sourceDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

    $totalSavings = 0;
    $totalOriginalSize = 0;
    $optimizedCount = 0;

    foreach ($files as $file) {
        $filename = basename($file);
        $targetPath = $targetDir . $filename;

        $result = $optimizer->optimize($file, $targetPath);

        if ($result['success']) {
            $totalOriginalSize += $result['originalSize'];
            $totalSavings += $result['savings'];
            $optimizedCount++;

            echo "Optimized: $filename - {$result['savingsPercent']}% savings\n";
        } else {
            echo "Failed: $filename - {$result['error']}\n";
        }
    }

    $totalSavingsPercent = $totalOriginalSize > 0
        ? round(($totalSavings / $totalOriginalSize) * 100, 2)
        : 0;

    echo "\n=== Summary ===\n";
    echo "Files optimized: $optimizedCount\n";
    echo "Total original size: " . formatBytes($totalOriginalSize) . "\n";
    echo "Total savings: " . formatBytes($totalSavings) . " ($totalSavingsPercent%)\n";
}

// ============================================
// EXAMPLE 7: Error Handling
// ============================================

function example7_errorHandling()
{
    $optimizer = new ImageOptimizer();

    $result = $optimizer->optimize('/path/to/image.jpg', '/path/to/target.jpg');

    if (!$result['success']) {
        // Log the error
        error_log("Image optimization failed: {$result['error']}");

        // Check the detailed log
        foreach ($result['log'] as $logEntry) {
            error_log($logEntry);
        }

        // Handle specific error cases
        if (strpos($result['error'], 'not exist') !== false) {
            // Handle missing file
            echo "Source file not found";
        } elseif (strpos($result['error'], 'not a supported') !== false) {
            // Handle unsupported format
            echo "Unsupported image format";
        } else {
            // Generic error
            echo "Optimization failed";
        }
    }
}

// ============================================
// Helper function
// ============================================

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

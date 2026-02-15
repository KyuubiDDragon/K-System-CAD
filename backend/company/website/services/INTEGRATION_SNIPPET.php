<?php

/**
 * Integration Snippet for ImageOptimizer
 *
 * This snippet can be directly inserted into the uploadMedia() function
 * in /backend/company/website/index.php after line 1594
 *
 * Location: After the file has been moved to the upload directory
 * Insert after: if (!move_uploaded_file($tempPath, $uploadPath)) { ... }
 */

// ========================================
// ADD THIS CODE TO uploadMedia() FUNCTION
// After line 1594 in index.php
// ========================================

// Optimize image if applicable
require_once __DIR__ . '/services/ImageOptimizer.php';

try {
    $optimizer = new ImageOptimizer();

    // Check if the uploaded file is an image
    if ($optimizer->isImage($uploadPath)) {
        error_log("Starting image optimization for: $filename");

        // Create temporary path for optimized image
        $optimizedPath = $uploadDir . '/temp_optimized_' . $filename;

        // Optimize the image with default settings
        $optimizationResult = $optimizer->optimize($uploadPath, $optimizedPath, [
            'maxWidth' => 1920,        // Maximum width
            'maxHeight' => 1080,       // Maximum height
            'quality' => 85,           // JPEG quality (85%)
            'stripExif' => true,       // Remove EXIF data for privacy
            'convertPngToJpeg' => true // Convert large PNGs to JPEG if no transparency
        ]);

        if ($optimizationResult['success']) {
            // Replace original with optimized version
            unlink($uploadPath);
            rename($optimizedPath, $uploadPath);

            // Update file size to optimized size
            $fileSize = $optimizationResult['optimizedSize'];

            // Log optimization results
            error_log(sprintf(
                "Image optimized: %s | Original: %s | Optimized: %s | Savings: %s%% | Dimensions: %dx%d → %dx%d",
                $filename,
                formatBytes($optimizationResult['originalSize']),
                formatBytes($optimizationResult['optimizedSize']),
                $optimizationResult['savingsPercent'],
                $optimizationResult['originalDimensions']['width'],
                $optimizationResult['originalDimensions']['height'],
                $optimizationResult['newDimensions']['width'],
                $optimizationResult['newDimensions']['height']
            ));

            // Optional: Create thumbnails for faster loading
            $thumbnailResults = $optimizer->createThumbnails($uploadPath, [
                'thumbnail' => 150,  // 150px thumbnail
                'medium' => 640,     // 640px medium size
                'large' => 1024      // 1024px large size
            ]);

            // Log thumbnail creation
            foreach ($thumbnailResults as $sizeName => $result) {
                if ($result['success']) {
                    error_log("Thumbnail created: {$sizeName} - {$result['newDimensions']['width']}x{$result['newDimensions']['height']}");
                }
            }

            // Optional: Store optimization metadata in response
            // This can be used by the frontend to display optimization stats
            $optimizationMetadata = [
                'optimized' => true,
                'originalSize' => $optimizationResult['originalSize'],
                'optimizedSize' => $optimizationResult['optimizedSize'],
                'savings' => $optimizationResult['savingsPercent'],
                'dimensions' => $optimizationResult['newDimensions'],
                'thumbnails' => array_keys($thumbnailResults)
            ];
        } else {
            error_log("Image optimization failed: {$optimizationResult['error']}");
            // Continue with original file if optimization fails
        }
    }
} catch (Exception $e) {
    // Log error but don't fail the upload
    error_log("Error during image optimization: " . $e->getMessage());
}

// Continue with the rest of the uploadMedia function...
// The $fileSize variable now contains the optimized size if optimization was successful

// ========================================
// END OF INTEGRATION SNIPPET
// ========================================


// ========================================
// HELPER FUNCTION
// Add this helper function at the end of index.php (outside any other function)
// ========================================

/**
 * Format bytes to human-readable size
 *
 * @param int $bytes
 * @return string
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}


// ========================================
// OPTIONAL: MODIFY THE RESPONSE JSON
// Replace the echo json_encode at the end of uploadMedia with:
// ========================================

echo json_encode([
    'success' => true,
    'message' => 'File uploaded successfully' . (isset($optimizationResult) && $optimizationResult['success'] ? ' and optimized' : ''),
    'fileName' => $filename,
    'filePath' => $relativePath,
    'mediaId' => $mediaId,
    // Optional: Include optimization metadata
    'optimization' => isset($optimizationMetadata) ? $optimizationMetadata : null
]);


// ========================================
// COMPLETE MODIFIED uploadMedia FUNCTION
// Here's the complete modified function for reference:
// ========================================

/*
function uploadMedia(PDO $pdo, int $userId, string $authority, int $authorityId): void
{
    try {
        // Get the website ID from the request
        $websiteId = filter_input(INPUT_POST, 'website_id', FILTER_VALIDATE_INT);
        $mediaType = filter_input(INPUT_POST, 'mediaType', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'image';

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
            $errorMessage = isset($_FILES['file']) ? getUploadErrorMessage($_FILES['file']['error']) : 'No file uploaded';
            http_response_code(400);
            echo json_encode(['error' => $errorMessage]);
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
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'text/plain',
            'video/mp4',
            'video/webm',
            'video/ogg',
            'audio/mpeg',
            'audio/wav',
            'audio/ogg'
        ];

        if (!in_array($fileType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type']);
            return;
        }

        // Validate file size (max 30MB)
        $maxSize = 30 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            http_response_code(400);
            echo json_encode(['error' => 'File is too large. Maximum size is 30MB']);
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
        $relativePath = 'uploads/website/' . $authorityId . '/' . $websiteId . '/' . $filename;

        // Move the uploaded file
        if (!move_uploaded_file($tempPath, $uploadPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to move uploaded file']);
            return;
        }

        // ===== IMAGE OPTIMIZATION INTEGRATION STARTS HERE =====

        require_once __DIR__ . '/services/ImageOptimizer.php';

        try {
            $optimizer = new ImageOptimizer();

            if ($optimizer->isImage($uploadPath)) {
                error_log("Starting image optimization for: $filename");

                $optimizedPath = $uploadDir . '/temp_optimized_' . $filename;

                $optimizationResult = $optimizer->optimize($uploadPath, $optimizedPath, [
                    'maxWidth' => 1920,
                    'maxHeight' => 1080,
                    'quality' => 85,
                    'stripExif' => true,
                    'convertPngToJpeg' => true
                ]);

                if ($optimizationResult['success']) {
                    unlink($uploadPath);
                    rename($optimizedPath, $uploadPath);
                    $fileSize = $optimizationResult['optimizedSize'];

                    error_log(sprintf(
                        "Image optimized: %s | Savings: %s%%",
                        $filename,
                        $optimizationResult['savingsPercent']
                    ));

                    $thumbnailResults = $optimizer->createThumbnails($uploadPath, [
                        'thumbnail' => 150,
                        'medium' => 640,
                        'large' => 1024
                    ]);

                    $optimizationMetadata = [
                        'optimized' => true,
                        'originalSize' => $optimizationResult['originalSize'],
                        'optimizedSize' => $optimizationResult['optimizedSize'],
                        'savings' => $optimizationResult['savingsPercent']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error during image optimization: " . $e->getMessage());
        }

        // ===== IMAGE OPTIMIZATION INTEGRATION ENDS HERE =====

        // Determine the right media_type enum value based on file type
        $mediaTypeEnum = 'image';
        if (strpos($fileType, 'video/') === 0) {
            $mediaTypeEnum = 'video';
        } elseif (strpos($fileType, 'audio/') === 0) {
            $mediaTypeEnum = 'audio';
        } elseif (strpos($fileType, 'application/') === 0 || $fileType === 'text/plain') {
            $mediaTypeEnum = 'document';
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
            'message' => 'File uploaded successfully' . (isset($optimizationResult) && $optimizationResult['success'] ? ' and optimized' : ''),
            'fileName' => $filename,
            'filePath' => $relativePath,
            'mediaId' => $mediaId,
            'optimization' => isset($optimizationMetadata) ? $optimizationMetadata : null
        ]);

    } catch (PDOException $e) {
        error_log("Error in uploadMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error in uploadMedia: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
}
*/

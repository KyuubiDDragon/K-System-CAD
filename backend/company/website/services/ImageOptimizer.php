<?php

declare(strict_types=1);

/**
 * ImageOptimizer Service
 *
 * Handles automatic image optimization for media uploads.
 * Features:
 * - Automatic resizing to reasonable dimensions
 * - Multiple size generation (thumbnail, medium, large)
 * - Intelligent compression (JPEG/PNG/WebP)
 * - PNG to JPEG conversion for large files without transparency
 * - EXIF data stripping for privacy
 * - Format detection and validation
 *
 * @author K-Systems
 * @version 1.0.0
 */
class ImageOptimizer
{
    // Maximum dimensions for different sizes
    private const MAX_FULL_WIDTH = 1920;
    private const MAX_FULL_HEIGHT = 1080;

    // Predefined thumbnail sizes
    private const SIZE_THUMBNAIL = 150;
    private const SIZE_MEDIUM = 640;
    private const SIZE_LARGE = 1024;

    // Compression quality settings
    private const JPEG_QUALITY = 85;
    private const PNG_COMPRESSION = 9;
    private const WEBP_QUALITY = 85;

    // Maximum file size for PNG to JPEG conversion (5MB)
    private const PNG_TO_JPEG_THRESHOLD = 5 * 1024 * 1024;

    // Supported image formats
    private const SUPPORTED_FORMATS = [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/png' => IMAGETYPE_PNG,
        'image/gif' => IMAGETYPE_GIF,
        'image/webp' => IMAGETYPE_WEBP
    ];

    private bool $useImagick = false;
    private array $optimizationLog = [];

    /**
     * Constructor - checks for available image libraries
     */
    public function __construct()
    {
        $this->useImagick = extension_loaded('imagick') && class_exists('Imagick');

        if (!$this->useImagick && !extension_loaded('gd')) {
            throw new RuntimeException('Neither GD nor Imagick extension is available');
        }

        $this->log('Using ' . ($this->useImagick ? 'Imagick' : 'GD') . ' library');
    }

    /**
     * Main optimization method
     *
     * @param string $sourcePath Path to source image
     * @param string $targetPath Path where optimized image will be saved
     * @param array $options Optional settings:
     *   - maxWidth: Maximum width (default: 1920)
     *   - maxHeight: Maximum height (default: 1080)
     *   - quality: Compression quality (default: 85 for JPEG)
     *   - stripExif: Remove EXIF data (default: true)
     *   - convertPngToJpeg: Convert large PNG to JPEG (default: true)
     * @return array Result with success status and metadata
     */
    public function optimize(string $sourcePath, string $targetPath, array $options = []): array
    {
        $this->optimizationLog = [];

        try {
            // Validate source file
            if (!file_exists($sourcePath)) {
                throw new InvalidArgumentException("Source file does not exist: $sourcePath");
            }

            if (!$this->isImage($sourcePath)) {
                throw new InvalidArgumentException("File is not a supported image format");
            }

            $originalSize = filesize($sourcePath);
            $imageType = $this->getImageType($sourcePath);

            $this->log("Optimizing image: $sourcePath");
            $this->log("Original size: " . $this->formatBytes($originalSize));
            $this->log("Image type: " . $this->getImageTypeName($imageType));

            // Get options with defaults
            $maxWidth = $options['maxWidth'] ?? self::MAX_FULL_WIDTH;
            $maxHeight = $options['maxHeight'] ?? self::MAX_FULL_HEIGHT;
            $quality = $options['quality'] ?? self::JPEG_QUALITY;
            $stripExif = $options['stripExif'] ?? true;
            $convertPngToJpeg = $options['convertPngToJpeg'] ?? true;

            // Get image dimensions
            list($width, $height) = getimagesize($sourcePath);

            // Check if PNG should be converted to JPEG
            $shouldConvert = false;
            if ($imageType === IMAGETYPE_PNG && $convertPngToJpeg && $originalSize > self::PNG_TO_JPEG_THRESHOLD) {
                if (!$this->hasTransparency($sourcePath)) {
                    $shouldConvert = true;
                    $imageType = IMAGETYPE_JPEG;
                    $this->log("Converting PNG to JPEG (no transparency detected)");
                }
            }

            // Calculate new dimensions
            $newDimensions = $this->calculateDimensions($width, $height, $maxWidth, $maxHeight);
            $needsResize = ($newDimensions['width'] !== $width || $newDimensions['height'] !== $height);

            if ($needsResize) {
                $this->log("Resizing from {$width}x{$height} to {$newDimensions['width']}x{$newDimensions['height']}");
            }

            // Perform optimization
            if ($this->useImagick) {
                $result = $this->optimizeWithImagick(
                    $sourcePath,
                    $targetPath,
                    $imageType,
                    $newDimensions,
                    $quality,
                    $stripExif,
                    $shouldConvert
                );
            } else {
                $result = $this->optimizeWithGD(
                    $sourcePath,
                    $targetPath,
                    $imageType,
                    $newDimensions,
                    $quality,
                    $stripExif,
                    $shouldConvert
                );
            }

            $optimizedSize = filesize($targetPath);
            $savings = $originalSize - $optimizedSize;
            $savingsPercent = round(($savings / $originalSize) * 100, 2);

            $this->log("Optimized size: " . $this->formatBytes($optimizedSize));
            $this->log("Savings: " . $this->formatBytes($savings) . " ({$savingsPercent}%)");

            return [
                'success' => true,
                'originalSize' => $originalSize,
                'optimizedSize' => $optimizedSize,
                'savings' => $savings,
                'savingsPercent' => $savingsPercent,
                'originalDimensions' => ['width' => $width, 'height' => $height],
                'newDimensions' => $newDimensions,
                'wasConverted' => $shouldConvert,
                'log' => $this->optimizationLog
            ];

        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->optimizationLog
            ];
        }
    }

    /**
     * Create multiple thumbnail sizes
     *
     * @param string $sourcePath Path to source image
     * @param array $sizes Array of sizes to generate:
     *   ['thumbnail' => 150, 'medium' => 640, 'large' => 1024]
     * @return array Results for each size
     */
    public function createThumbnails(string $sourcePath, array $sizes = []): array
    {
        if (empty($sizes)) {
            $sizes = [
                'thumbnail' => self::SIZE_THUMBNAIL,
                'medium' => self::SIZE_MEDIUM,
                'large' => self::SIZE_LARGE
            ];
        }

        $results = [];
        $sourceDir = dirname($sourcePath);
        $sourceBasename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $sourceExtension = pathinfo($sourcePath, PATHINFO_EXTENSION);

        foreach ($sizes as $sizeName => $maxSize) {
            $targetPath = $sourceDir . '/' . $sourceBasename . '_' . $sizeName . '.' . $sourceExtension;

            $result = $this->optimize($sourcePath, $targetPath, [
                'maxWidth' => $maxSize,
                'maxHeight' => $maxSize
            ]);

            $results[$sizeName] = array_merge($result, [
                'path' => $targetPath,
                'size' => $maxSize
            ]);
        }

        return $results;
    }

    /**
     * Check if file is a supported image
     *
     * @param string $filePath Path to file
     * @return bool True if file is a supported image
     */
    public function isImage(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $imageType = @exif_imagetype($filePath);

        return in_array($imageType, self::SUPPORTED_FORMATS, true);
    }

    /**
     * Get image type constant
     *
     * @param string $filePath Path to image file
     * @return int Image type constant (IMAGETYPE_*)
     */
    private function getImageType(string $filePath): int
    {
        $imageType = @exif_imagetype($filePath);

        if ($imageType === false) {
            throw new RuntimeException("Unable to determine image type");
        }

        if (!in_array($imageType, self::SUPPORTED_FORMATS, true)) {
            throw new RuntimeException("Unsupported image type");
        }

        return $imageType;
    }

    /**
     * Get human-readable image type name
     */
    private function getImageTypeName(int $imageType): string
    {
        $types = [
            IMAGETYPE_JPEG => 'JPEG',
            IMAGETYPE_PNG => 'PNG',
            IMAGETYPE_GIF => 'GIF',
            IMAGETYPE_WEBP => 'WebP'
        ];

        return $types[$imageType] ?? 'Unknown';
    }

    /**
     * Check if PNG image has transparency
     */
    private function hasTransparency(string $filePath): bool
    {
        if ($this->useImagick) {
            try {
                $image = new Imagick($filePath);
                $hasAlpha = $image->getImageAlphaChannel() !== Imagick::ALPHACHANNEL_UNDEFINED;
                $image->destroy();
                return $hasAlpha;
            } catch (Exception $e) {
                // Fallback to GD if Imagick fails
            }
        }

        // GD method
        $img = imagecreatefrompng($filePath);
        if (!$img) {
            return false;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        // Check a sample of pixels for transparency
        $sampleSize = min(100, $width * $height);
        $step = max(1, (int)floor(($width * $height) / $sampleSize));

        for ($x = 0; $x < $width; $x += max(1, (int)floor($width / 10))) {
            for ($y = 0; $y < $height; $y += max(1, (int)floor($height / 10))) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;

                if ($alpha > 0) {
                    imagedestroy($img);
                    return true;
                }
            }
        }

        imagedestroy($img);
        return false;
    }

    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);

        return [
            'width' => (int)round($width * $ratio),
            'height' => (int)round($height * $ratio)
        ];
    }

    /**
     * Optimize using Imagick library
     */
    private function optimizeWithImagick(
        string $sourcePath,
        string $targetPath,
        int $imageType,
        array $dimensions,
        int $quality,
        bool $stripExif,
        bool $convertToJpeg
    ): bool {
        $image = new Imagick($sourcePath);

        // Strip EXIF data if requested
        if ($stripExif) {
            $image->stripImage();
        }

        // Resize if needed
        if ($dimensions['width'] !== $image->getImageWidth() || $dimensions['height'] !== $image->getImageHeight()) {
            $image->resizeImage(
                $dimensions['width'],
                $dimensions['height'],
                Imagick::FILTER_LANCZOS,
                1
            );
        }

        // Set compression
        if ($convertToJpeg || $imageType === IMAGETYPE_JPEG) {
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality($quality);
            $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        } elseif ($imageType === IMAGETYPE_PNG) {
            $image->setImageFormat('png');
            $image->setImageCompressionQuality(self::PNG_COMPRESSION);
        } elseif ($imageType === IMAGETYPE_WEBP) {
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(self::WEBP_QUALITY);
        }

        // Optimize
        $image->stripImage();

        // Write to file
        $result = $image->writeImage($targetPath);
        $image->destroy();

        return $result;
    }

    /**
     * Optimize using GD library
     */
    private function optimizeWithGD(
        string $sourcePath,
        string $targetPath,
        int $imageType,
        array $dimensions,
        int $quality,
        bool $stripExif,
        bool $convertToJpeg
    ): bool {
        // Load source image
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new RuntimeException("Unsupported image type for GD");
        }

        if (!$sourceImage) {
            throw new RuntimeException("Failed to load source image");
        }

        // Create new image with desired dimensions
        $newImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);

        // Preserve transparency for PNG and GIF
        if (($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) && !$convertToJpeg) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $dimensions['width'], $dimensions['height'], $transparent);
        }

        // Resample
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $dimensions['width'],
            $dimensions['height'],
            imagesx($sourceImage),
            imagesy($sourceImage)
        );

        // Save optimized image
        $result = false;
        if ($convertToJpeg || $imageType === IMAGETYPE_JPEG) {
            $result = imagejpeg($newImage, $targetPath, $quality);
        } elseif ($imageType === IMAGETYPE_PNG) {
            // PNG compression level is 0-9 (inverted from quality)
            $result = imagepng($newImage, $targetPath, self::PNG_COMPRESSION);
        } elseif ($imageType === IMAGETYPE_GIF) {
            $result = imagegif($newImage, $targetPath);
        } elseif ($imageType === IMAGETYPE_WEBP) {
            $result = imagewebp($newImage, $targetPath, self::WEBP_QUALITY);
        }

        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Add entry to optimization log
     */
    private function log(string $message): void
    {
        $this->optimizationLog[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    }

    /**
     * Get the optimization log
     */
    public function getLog(): array
    {
        return $this->optimizationLog;
    }
}

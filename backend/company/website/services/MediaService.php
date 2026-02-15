<?php
/**
 * Media Service
 * Handles file upload, validation, and optimization
 */

declare(strict_types=1);

namespace CompanyWebsite\Services;

use CompanyWebsite\Config;

class MediaService
{
    private string $uploadDir;
    private int $maxFileSize;
    private array $allowedTypes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uploadDir = Config::MEDIA_UPLOAD_DIR;
        $this->maxFileSize = Config::MEDIA_MAX_SIZE;
        $this->allowedTypes = Config::ALLOWED_MEDIA_TYPES;

        // Ensure upload directory exists
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file
     *
     * @param array $file The $_FILES array element
     * @param int $authorityId Authority ID for organization
     * @return array Result with success status and file info
     */
    public function uploadFile(array $file, int $authorityId): array
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => $this->getUploadErrorMessage($file['error'])
            ];
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'success' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedTypes)
            ];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_' . $authorityId . '_', true) . '.' . $extension;
        $filepath = $this->uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => false,
                'error' => 'Failed to move uploaded file'
            ];
        }

        // Get file info
        $filesize = filesize($filepath);
        $url = '/uploads/website_media/' . $filename;

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $url,
            'filesize' => $filesize,
            'mime_type' => $mimeType,
            'original_name' => $file['name']
        ];
    }

    /**
     * Delete a file
     *
     * @param string $filename Filename to delete
     * @return bool True if deleted successfully
     */
    public function deleteFile(string $filename): bool
    {
        $filepath = $this->uploadDir . '/' . basename($filename);

        if (file_exists($filepath)) {
            return unlink($filepath);
        }

        return false;
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Optimize an image (optional - can be extended)
     *
     * @param string $filepath Path to the image file
     * @return bool True if optimized successfully
     */
    public function optimizeImage(string $filepath): bool
    {
        // TODO: Implement image optimization if needed
        // For now, just return true
        return true;
    }

    /**
     * Generate thumbnail (optional - can be extended)
     *
     * @param string $filepath Path to the image file
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @return string|null Path to thumbnail or null on failure
     */
    public function generateThumbnail(string $filepath, int $width = 200, int $height = 200): ?string
    {
        // TODO: Implement thumbnail generation if needed
        return null;
    }
}

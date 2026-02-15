<?php
/**
 * Authority Branding Management API
 * Handles logo uploads, color settings, and branding configuration for authorities
 */
declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth_check.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../utils/permission_helper.php';

// Check permissions - only admins with ADMIN_AUTHORITY_SETTINGS or ALL_PERMISSIONS can access
$hasPermission = hasPermission($userPermissions ?? [], 'ADMIN_AUTHORITY_SETTINGS') ||
                 hasAllPermissions($userPermissions ?? []);

if (!$hasPermission) {
    http_response_code(403);
    echo json_encode([
        "error" => "Insufficient permissions",
        "required" => "ADMIN_AUTHORITY_SETTINGS or ALL_PERMISSIONS",
        "current" => $userPermissions ?? []
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'getBranding') {
            getBrandingData($pdo, $_SESSION['authority_id']);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid action for GET request"]);
        }
        break;
        
    case 'POST':
        if ($action === 'updateBranding') {
            updateBrandingData($pdo, $_SESSION['authority_id']);
        } elseif ($action === 'uploadLogo') {
            uploadAuthorityLogo($pdo, $_SESSION['authority_id']);
        } elseif ($action === 'uploadBackground') {
            uploadDefaultBackground($pdo, $_SESSION['authority_id']);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid action for POST request"]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
}

/**
 * Get current branding data for the authority
 */
function getBrandingData(PDO $pdo, int $authorityId): void
{
    try {
        $stmt = $pdo->prepare("
            SELECT logo_url, primary_color, secondary_color, app_title, default_background, name, display_name
            FROM kdd_authorities 
            WHERE id = ?
        ");
        $stmt->execute([$authorityId]);
        $branding = $stmt->fetch();
        
        if ($branding) {
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "branding" => [
                    "logo_url" => $branding['logo_url'],
                    "primary_color" => $branding['primary_color'] ?: "#3B82F6",
                    "secondary_color" => $branding['secondary_color'] ?: "#6B7280", 
                    "app_title" => $branding['app_title'] ?: $branding['display_name'],
                    "default_background" => $branding['default_background'],
                    "authority_name" => $branding['name'],
                    "authority_display_name" => $branding['display_name']
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Authority not found"]);
        }
    } catch (PDOException $e) {
        error_log("Error fetching branding data: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

/**
 * Update branding data (colors, title)
 */
function updateBrandingData(PDO $pdo, int $authorityId): void
{
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON data"]);
            return;
        }
        
        // Validate color codes
        $primaryColor = $data['primary_color'] ?? '#3B82F6';
        $secondaryColor = $data['secondary_color'] ?? '#6B7280';
        $appTitle = $data['app_title'] ?? null;
        
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $primaryColor)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid primary color format"]);
            return;
        }
        
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $secondaryColor)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid secondary color format"]);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE kdd_authorities 
            SET primary_color = ?, secondary_color = ?, app_title = ? 
            WHERE id = ?
        ");
        $stmt->execute([$primaryColor, $secondaryColor, $appTitle, $authorityId]);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Branding data updated successfully"
        ]);
        
    } catch (PDOException $e) {
        error_log("Error updating branding data: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error"]);
    }
}

/**
 * Upload authority logo
 */
function uploadAuthorityLogo(PDO $pdo, int $authorityId): void
{
    try {
        if (!isset($_FILES['logo'])) {
            http_response_code(400);
            echo json_encode(["error" => "No file uploaded"]);
            return;
        }
        
        $file = $_FILES['logo'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["error" => "File upload error: " . $file['error']]);
            return;
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["error" => "File too large (max 5MB)"]);
            return;
        }
        
        // Validate image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid image file"]);
            return;
        }
        
        // Setup upload directory
        $uploadDir = '/var/www/html/uploads/authority_logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileName = 'authority_' . $authorityId . '_logo_' . time() . '.png';
        $uploadPath = $uploadDir . $fileName;
        
        // Process and resize image
        $image = null;
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($file['tmp_name']);
                break;
            default:
                http_response_code(400);
                echo json_encode(["error" => "Unsupported image format"]);
                return;
        }
        
        if (!$image) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to process image"]);
            return;
        }
        
        // Resize to 200x200 maintaining aspect ratio
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $targetSize = 200;
        
        if ($originalWidth > $originalHeight) {
            $newWidth = $targetSize;
            $newHeight = intval($originalHeight * $targetSize / $originalWidth);
        } else {
            $newHeight = $targetSize;
            $newWidth = intval($originalWidth * $targetSize / $originalHeight);
        }
        
        $resized = imagecreatetruecolor($targetSize, $targetSize);
        $backgroundColor = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $backgroundColor);
        
        // Enable alpha blending for transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefill($resized, 0, 0, $transparent);
        imagealphablending($resized, true);
        
        // Center the image
        $xPos = ($targetSize - $newWidth) / 2;
        $yPos = ($targetSize - $newHeight) / 2;
        
        imagecopyresampled($resized, $image, $xPos, $yPos, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save as PNG with transparency
        imagepng($resized, $uploadPath);
        imagedestroy($image);
        imagedestroy($resized);
        
        // Update database
        $publicUrl = '/uploads/authority_logos/' . $fileName;
        $stmt = $pdo->prepare("UPDATE kdd_authorities SET logo_url = ? WHERE id = ?");
        $stmt->execute([$publicUrl, $authorityId]);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "logo_url" => $publicUrl,
            "message" => "Logo uploaded successfully"
        ]);
        
    } catch (Exception $e) {
        error_log("Error uploading logo: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to upload logo"]);
    }
}

/**
 * Upload default background
 */
function uploadDefaultBackground(PDO $pdo, int $authorityId): void
{
    try {
        if (!isset($_FILES['background'])) {
            http_response_code(400);
            echo json_encode(["error" => "No file uploaded"]);
            return;
        }
        
        $file = $_FILES['background'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["error" => "File upload error: " . $file['error']]);
            return;
        }
        
        // Check file size (max 10MB for backgrounds)
        if ($file['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(["error" => "File too large (max 10MB)"]);
            return;
        }
        
        // Validate image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid image file"]);
            return;
        }
        
        // Setup upload directory
        $uploadDir = '/var/www/html/uploads/authority_backgrounds/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'authority_' . $authorityId . '_bg_' . time() . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to save uploaded file"]);
            return;
        }
        
        // Update database
        $publicUrl = '/uploads/authority_backgrounds/' . $fileName;
        $stmt = $pdo->prepare("UPDATE kdd_authorities SET default_background = ? WHERE id = ?");
        $stmt->execute([$publicUrl, $authorityId]);
        
        http_response_code(200);
        echo json_encode([
            "success" => true, 
            "background_url" => $publicUrl,
            "message" => "Background uploaded successfully"
        ]);
        
    } catch (Exception $e) {
        error_log("Error uploading background: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Failed to upload background"]);
    }
}
?>
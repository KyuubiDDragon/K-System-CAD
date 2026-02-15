<?php
/**
 * Backend Endpoint: fireprotection/index.php
 * Handles uploading fire protection documents and retrieving file list.
 * Optionally uploads files to Dropbox.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';

// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "Database connection failed."]); exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange
require_once __DIR__ . '/dropbox.php';       // Include the refactored Dropbox helpers

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null; // Extract authority ID from token

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority) || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}
$allowedAuthorities = ["fire", "police", "medic", "justice", "statepark", "casa", "test", "fireguard"];
if (!in_array($authority, $allowedAuthorities)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'submitDocument' => ['module' => 'fireprotection', 'action' => 'write'],
    'getLastFiles'   => ['module' => 'fireprotection', 'action' => 'read'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') { http_response_code(400); echo json_encode(["error" => "No action specified."]); exit(); }
if ($required_permission === null) { http_response_code(404); echo json_encode(['error' => 'Invalid action specified.']); exit(); }

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true; // Has specific permission
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    switch ($action) {
        case 'submitDocument':
            if ($request_method === 'POST') submitDocument($pdo, $userId, $authority, $baseUploadPath, $basePublicUrl, $authorityId); else MethodNotAllowed(); break;
        case 'getLastFiles':
            if ($request_method === 'GET') getLastFiles($pdo, $authority, $authorityId); else MethodNotAllowed(); break;
        default:
            http_response_code(500); echo json_encode(['error' => 'Action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();



// Helper function to get authority ID
function getAuthorityId(PDO $pdo, ?string $authority): ?int {
    if (!$authority) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM kdd_authorities WHERE name = ?");
        $stmt->execute([$authority]);
        $authorityId = $stmt->fetchColumn();
        return $authorityId ? (int)$authorityId : null;
    } catch (\PDOException $e) {
        error_log("Error fetching authority ID: " . $e->getMessage());
        return null;
    }
}

// --- Function Implementations (PDO Refactored) ---

/**
 * Handles file upload, saves metadata, and optionally uploads to Dropbox.
 */
function submitDocument(PDO $pdo, int $requestingUserId, string $authority, string $baseUploadPath, string $basePublicUrl, int $authorityId): void {
    // --- Input Validation ---
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400); echo json_encode(['error' => 'No file uploaded or upload error occurred.']); return;
    }

    $fileInfo = $_FILES['image'];
    $isEvent = filter_var($_POST["isEvent"] ?? false, FILTER_VALIDATE_BOOLEAN);
    $year = filter_var($_POST["year"] ?? date('Y'), FILTER_SANITIZE_SPECIAL_CHARS); // Default to current year if not provided
    $uploadToDropboxFlag = filter_var($_POST["uploadToDropbox"] ?? false, FILTER_VALIDATE_BOOLEAN); // Optional flag

    // --- File Processing ---
    $originalName = $fileInfo['name'];
    $tmpName = $fileInfo['tmp_name'];
    $size = $fileInfo['size'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
    $safeOriginalName = preg_replace("/[^a-zA-Z0-9\.\s\-_]/", "_", basename($originalName));
    $safeBaseName = pathinfo($safeOriginalName, PATHINFO_FILENAME);

     // Determine Subfolder (adjust logic if needed)
     // Consider validating year format
    $subfoldername = $isEvent ? "Begrenzte Zertifikate" : (string)$year;

    // Define Paths (authority specific subdirectory within base path)
    $authorityUploadDir = $baseUploadPath . $authority . '/' . $subfoldername . '/';
    $authorityPublicUrl = $basePublicUrl . $authority . '/' . $subfoldername . '/';

    // Allowed extensions (adjust as needed)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Focus on document/image types
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400); echo json_encode(["error" => "Invalid file type '{$fileExtension}'. Allowed: " . implode(', ', $allowedExtensions)]); return;
    }

    // Create unique filename if necessary
    $targetFileName = $safeOriginalName; // Start with original sanitized name
    $counter = 1;
    while (file_exists($authorityUploadDir . $targetFileName)) {
        $targetFileName = $safeBaseName . "_" . $counter . "." . $fileExtension;
        $counter++;
    }
    $localFilePath = $authorityUploadDir . $targetFileName;
    $publicFileUrl = $authorityPublicUrl . $targetFileName;

    // Create directory if needed
    if (!is_dir($authorityUploadDir)) {
        if (!mkdir($authorityUploadDir, 0775, true)) {
            error_log("Failed to create directory: $authorityUploadDir");
            http_response_code(500); echo json_encode(['error' => 'Server error: Could not create target directory.']); return;
        }
    }

    // --- Local Upload & DB Insert ---
    $dbSuccess = false;
    $newDbId = null;
    try {
        // Move uploaded file
        if (move_uploaded_file($tmpName, $localFilePath)) {
            // Insert into DB
            $sql = "INSERT INTO `kdd_fireprotection_files` (name, link, creator, created_at, authority_id) VALUES (?, ?, ?, NOW(), ?)";
            $stmt = $pdo->prepare($sql);
            $dbSuccess = $stmt->execute([$safeBaseName, $publicFileUrl, $requestingUserId, $authorityId]); // Store base name and public URL
            if ($dbSuccess) {
                 $newDbId = $pdo->lastInsertId();
                 // Log local save?
                 // logDatabaseChange(...);
            } else {
                unlink($localFilePath); // Clean up if DB insert fails
                throw new \PDOException("Failed to insert file metadata into database.");
            }
        } else {
            http_response_code(500); echo json_encode(["error" => "Failed to move uploaded file."]); return;
        }
    } catch (\PDOException $e) {
        error_log("DB error in submitDocument ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not save file metadata."]); return;
    } catch (\Exception $e) { // Catch move_uploaded_file errors indirectly
         error_log("File move error in submitDocument ($authority): " . $e->getMessage());
         http_response_code(500); echo json_encode(["error" => "Could not save uploaded file."]); return;
    }

    // --- Optional Dropbox Upload ---
    $dropboxResult = null;
    if ($dbSuccess && $uploadToDropboxFlag) {
        try {
            // Get Dropbox Credentials from DB
            $tokenData = getTokenData($pdo); // Assumes this function exists and uses PDO
            if (!$tokenData || empty($tokenData['access_token']) || empty($tokenData['refresh_token']) || empty($tokenData['client_id']) || empty($tokenData['client_secret'])) {
                 error_log("Dropbox credentials missing or incomplete in database.");
                 $dropboxResult = ['status' => 'error', 'message' => 'Dropbox upload configured but credentials missing.'];
            } else {
                // Define Dropbox path (customize as needed)
                $dropboxPath = "/{$authority}/FireProtection/{$subfoldername}/{$targetFileName}";

                echo "\nUploading to Dropbox...\n"; // Debug output
                // Call the refactored Dropbox helper function
                $dropboxResult = uploadToDropbox(
                    $pdo,
                    $tokenData['access_token'],
                    $tokenData['refresh_token'],
                    $tokenData['client_id'],
                    $tokenData['client_secret'],
                    $localFilePath, // Use the path where the file was saved locally
                    $dropboxPath
                );
                echo "\nDropbox Result: " . json_encode($dropboxResult) . "\n"; // Debug output

                // Optionally: Update DB record with Dropbox link if successful
                if ($dropboxResult['status'] === 'success' && isset($dropboxResult['shareableLink'])) {
                    $sqlDropboxLink = "UPDATE `kdd_fireprotection_files` SET dropbox_link = ? WHERE authority_id = ? AND id = ?";
                    $stmtDropboxLink = $pdo->prepare($sqlDropboxLink);
                    $stmtDropboxLink->execute([$dropboxResult['shareableLink'], $newDbId]);
                }
            }
        } catch (\Throwable $e) {
            error_log("Error during optional Dropbox upload: " . $e->getMessage());
            $dropboxResult = ['status' => 'error', 'message' => 'Dropbox upload failed: ' . $e->getMessage()];
        }
    }

    // --- Final Response ---
    $finalResponse = [
        'status' => 'success',
        'message' => 'File processed successfully.',
        'local_url' => $publicFileUrl,
        'db_id' => $newDbId
    ];
    if ($uploadToDropboxFlag) {
        $finalResponse['dropbox_status'] = $dropboxResult['status'] ?? 'error';
        if (isset($dropboxResult['shareableLink'])) {
            $finalResponse['dropbox_link'] = $dropboxResult['shareableLink'];
        }
        if (isset($dropboxResult['message'])) {
             $finalResponse['dropbox_message'] = $dropboxResult['message'];
        }
        // Adjust final HTTP code based on Dropbox outcome? Maybe 207 if local success but dropbox failed.
        if ($finalResponse['dropbox_status'] !== 'success') {
             http_response_code(207); // Multi-Status
             $finalResponse['status'] = 'warning';
             $finalResponse['message'] = 'File saved locally, but Dropbox operation failed.';
        } else {
              http_response_code(201); // Created (fully successful)
        }
    } else {
        http_response_code(201); // Created (local only)
    }

    echo json_encode($finalResponse);
}


function getLastFiles(PDO $pdo, string $authority, int $authorityId): void {
    try {
        // Fetch last files for this authority context
        $sql = "SELECT id, name, link, creator, created_at 
                FROM `kdd_fireprotection_files` 
                WHERE authority_id = ? 
                ORDER BY created_at DESC LIMIT 100";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format/modify data as needed before sending
        // Could add creator names, or other transformations

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'files' => $files
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in getLastFiles ($authority): " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not retrieve files."]);
    }
}

// --- Removed function submitDocumentPdf ---

?>
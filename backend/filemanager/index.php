<?php

/**
 * Backend Endpoint: filemanager/index.php
 * Handles file and folder operations within an authority context.
 * Uses Cookie-based Authentication and PDO database connection.
 */

declare(strict_types=1);

// --- Core Initialisation ---
require_once __DIR__ . '/../bootstrap.php';



// --- Dependencies & DB Connection ---
try {
    require_once __DIR__ . '/../db.php'; // Defines $pdo
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
require_once __DIR__ . '/../logging/logging.php'; // For logDatabaseChange

// --- Authentication ---
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Authentication system error."]);
    exit();
}

// --- User Context & Authority Validation ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null; // Authority is key here
$authorityId = $decoded_jwt->authority_id ?? null;

// Verwende Umgebungsvariablen mit Fallback zu den direkten Pfaden
$baseUploadPath = $_ENV['UPLOAD_BASE_DIR'] 
    ? rtrim($_ENV['UPLOAD_BASE_DIR'], '/') . "/{$authority}/filemanager/" 
    : "../../uploads/{$authority}/filemanager/";
    
$basePublicUrl = $_ENV['PUBLIC_UPLOAD_URL'] 
    ? rtrim($_ENV['PUBLIC_UPLOAD_URL'], '/') . "/{$authority}/filemanager/" 
    : "/uploads/{$authority}/filemanager/";

// Debug-Logging für Pfade
error_log("DEBUG: UPLOAD_BASE_DIR = " . ($_ENV['UPLOAD_BASE_DIR'] ?? 'nicht gesetzt'));
error_log("DEBUG: PUBLIC_UPLOAD_URL = " . ($_ENV['PUBLIC_UPLOAD_URL'] ?? 'nicht gesetzt'));
error_log("DEBUG: Resultierender baseUploadPath = {$baseUploadPath}");

if ($userId === null || !is_int($userId) || $authority === null || !is_string($authority)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid token payload."]);
    exit();
}
require_once __DIR__ . '/../utils/authority_helper.php';
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'filemanager')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to filemanager features."]); exit();
}

// --- Authorization & Action Routing ---
$action = $_REQUEST['action'] ?? '';
$request_method = $_SERVER['REQUEST_METHOD'];

// Map actions to required permissions (module.action format)
$permissions_map = [
    'getFilesAndFolders' => ['module' => 'filemanager', 'action' => 'read'],
    'uploadFiles'        => ['module' => 'filemanager', 'action' => 'write'],
    'createFolder'       => ['module' => 'filemanager', 'action' => 'write'],
    'renameFolder'       => ['module' => 'filemanager', 'action' => 'write'],
    'moveFile'           => ['module' => 'filemanager', 'action' => 'write'],
    'moveFolder'         => ['module' => 'filemanager', 'action' => 'write'],
    'deleteFile'         => ['module' => 'filemanager', 'action' => 'delete'],
    'deleteFolder'       => ['module' => 'filemanager', 'action' => 'delete'],
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($action === '') {
    http_response_code(400);
    echo json_encode(["error" => "No action specified."]);
    exit();
}
if ($required_permission === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid action specified.']);
    exit();
}

// Load permission helper
require_once __DIR__ . '/../utils/permission_helper.php';

$module = $required_permission['module'];
$actionType = $required_permission['action'];

// Check permission levels using module-based permissions
if (hasAllPermissions($userPermissions)) {
    $has_permission = true;
} elseif (hasModulePermission($userPermissions, $module, $actionType)) {
    $has_permission = true;
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'write')) {
    $has_permission = true; // WRITE implies READ
} elseif ($actionType === 'read' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies READ
} elseif ($actionType === 'write' && hasModulePermission($userPermissions, $module, 'delete')) {
    $has_permission = true; // DELETE implies WRITE
}

// --- Execute Action or Deny ---
if ($has_permission) {
    $is_post_request = ($request_method === 'POST');
    // Add PUT/DELETE checks if preferred

    switch ($action) {
        case 'getFilesAndFolders':
            if ($request_method === 'GET' || $is_post_request) getFilesAndFolders($pdo, $authority);
            else MethodNotAllowed();
            break; // Allow POST if folderId sent in body
        case 'uploadFiles':
            if ($is_post_request) uploadFiles($pdo, $userId, $authority, $baseUploadPath, $basePublicUrl);
            else MethodNotAllowed();
            break;
        case 'createFolder':
            if ($is_post_request) createFolder($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'deleteFile':
            if ($is_post_request) deleteFile($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider DELETE
        case 'deleteFolder':
            if ($is_post_request) deleteFolder($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider DELETE
        case 'renameFolder':
            if ($is_post_request) renameFolder($pdo, $userId, $authority);
            else MethodNotAllowed();
            break; // Consider PUT
        case 'moveFile':
            if ($is_post_request) moveFile($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;
        case 'moveFolder':
            if ($is_post_request) moveFolder($pdo, $userId, $authority);
            else MethodNotAllowed();
            break;

        default:
            http_response_code(500);
            echo json_encode(['error' => 'Action routing error.']);
            break;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit();





// --- Function Implementations (PDO Refactored) ---

function getFilesAndFolders(PDO $pdo, string $authority): void
{
    // Get authority ID first (for all request types)
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Check if request method is GET or POST
    $request_method = $_SERVER['REQUEST_METHOD'];
    
    $folderId = null;
    if ($request_method === 'GET') {
        // For GET requests, use query parameter
        $folderId = isset($_GET['folderId']) ? filter_var($_GET['folderId'], FILTER_VALIDATE_INT) : null;
    } else {
        // For POST requests, get data from JSON
        $data = getJsonRequestData();
        $folderId = $data['folderId'] ?? null;
        if ($folderId !== null) {
            $folderId = filter_var($folderId, FILTER_VALIDATE_INT);
        }
    }

    try {
        $folders = [];
        $files = [];
        $currentFolderName = null;

        // Query für Unterordner
        if ($folderId === null) {
            // Root level
            $sqlFolders = "SELECT * FROM `kdd_files_folder` WHERE authority_id = ? AND parent_id IS NULL AND is_deleted = 0";
            $stmtFolders = $pdo->prepare($sqlFolders);
            $stmtFolders->execute([$authorityId]);
        } else { // Specific folder level
            $sqlFolders = "SELECT * FROM `kdd_files_folder` WHERE authority_id = ? AND parent_id = ? AND is_deleted = 0";
            $stmtFolders = $pdo->prepare($sqlFolders);
            $stmtFolders->execute([$authorityId, $folderId]);
        }
        $folders = $stmtFolders->fetchAll(PDO::FETCH_ASSOC);

        // Query für Dateien im aktuellen Ordner
        $sqlFiles = "SELECT * FROM `kdd_files` WHERE authority_id = ? AND folder_id = ? AND is_deleted = 0";
        $stmtFiles = $pdo->prepare($sqlFiles);
        if ($folderId !== null) {
            $stmtFiles->execute([$authorityId, $folderId]);
        } else {
            // For root level files, use NULL folder_id
            $stmtFiles->execute([$authorityId, null]);
        }
        $files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);

        // Aktuellen Ordnernamen holen (wenn nicht root)
        if ($folderId !== null) {
            $sqlCurrentFolder = "SELECT name FROM `kdd_files_folder` WHERE authority_id = ? AND id = ? AND is_deleted = 0";
            $stmtCurrentFolder = $pdo->prepare($sqlCurrentFolder);
            $stmtCurrentFolder->execute([$authorityId, $folderId]);
            $currentFolderName = $stmtCurrentFolder->fetchColumn();
        }

        http_response_code(200);
        echo json_encode([
            "folders" => $folders,
            "files" => $files,
            "currentFolderName" => $currentFolderName
        ]);
    } catch (\PDOException $e) {
        error_log("DB error in getFilesAndFolders (FolderID: " . ($folderId ?? 'NULL') . ", Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not retrieve files and folders."]);
    }
}

function uploadFiles(PDO $pdo, int $requestingUserId, string $authority, string $baseUploadPath, string $basePublicUrl): void
{
    // For file uploads, we need to use POST data directly instead of JSON
    // File uploads come as multipart/form-data, not JSON

    // Debug file uploads - add error logging to see what's coming in
    error_log("DEBUG uploadFiles: POST data: " . print_r($_POST, true));
    error_log("DEBUG uploadFiles: FILES data: " . print_r($_FILES, true));
    error_log("DEBUG uploadFiles: baseUploadPath = {$baseUploadPath}");
    error_log("DEBUG uploadFiles: basePublicUrl = {$basePublicUrl}");
    error_log("DEBUG uploadFiles: authority = {$authority}");

    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    $folderIdInput = $_POST['folderId'] ?? null;
    $folderId = null;
    if ($folderIdInput !== null && $folderIdInput !== '' && $folderIdInput !== '0') {
        $folderId = filter_var($folderIdInput, FILTER_VALIDATE_INT);
        if ($folderId === false || $folderId <= 0) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid folder ID specified."]);
            return;
        }
    }

    // Fix file array key - try both 'files' and 'files[]' formats
    $files = null;
    if (isset($_FILES['files'])) {
        $files = $_FILES['files'];
    } elseif (isset($_FILES['files[]'])) {
        $files = $_FILES['files[]'];
    }

    // More flexible check for files
    if (empty($files) || !isset($files['name'])) {
        http_response_code(400);
        echo json_encode([
            "error" => "No files provided or invalid upload data format.",
            "debug" => [
                "files_keys" => array_keys($_FILES),
                "post_keys" => array_keys($_POST)
            ]
        ]);
        return;
    }

    // Handle both single file and array of files
    if (!is_array($files['name'])) {
        // Convert single file to array format for consistent processing
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $key) {
            $files[$key] = [$files[$key]];
        }
    }

    // Construct authority-specific paths
    $authorityUploadDir = $baseUploadPath;
    $authorityPublicUrl = $basePublicUrl;

    // Create directory if it doesn't exist
    if (!is_dir($authorityUploadDir)) {
        if (!mkdir($authorityUploadDir, 0775, true)) { // Use 0775, recursive
            error_log("Failed to create upload directory: $authorityUploadDir");
            http_response_code(500);
            echo json_encode(["error" => "Server setup error: Cannot create upload directory."]);
            return;
        }
    }

    // Allowed file extensions (configure as needed)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'psd', 'mp4', 'mov', 'avi', 'wmv']; // Added video examples
    $uploadedFilesInfo = [];
    $errors = [];

    try {
        // Prepare statement for DB insertion
        $sqlInsert = "INSERT INTO `kdd_files` (authority_id, name, path, folder_id, size, extension, uploaded, is_deleted)
                      VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        foreach ($files['name'] as $key => $originalName) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                $errors[] = "Error uploading '{$originalName}': Code " . $files['error'][$key];
                continue; // Skip this file
            }

            $tmpName = $files['tmp_name'][$key];
            $size = $files['size'][$key];

            // Sanitize filename and get extension
            $safeOriginalName = preg_replace("/[^a-zA-Z0-9\.\s\-_]/", "_", basename($originalName)); // Basic sanitization
            $extension = strtolower(pathinfo($safeOriginalName, PATHINFO_EXTENSION));
            $baseName = pathinfo($safeOriginalName, PATHINFO_FILENAME);

            // Validate extension
            if (!in_array($extension, $allowedExtensions)) {
                $errors[] = "File type '{$extension}' for file '{$safeOriginalName}' is not allowed.";
                continue; // Skip this file
            }
            // Validate size (e.g., max 50MB) - check php.ini limits too!
            $maxFileSize = 50 * 1024 * 1024; // 50 MB
            if ($size > $maxFileSize) {
                $errors[] = "File '{$safeOriginalName}' exceeds maximum size limit.";
                continue;
            }

            // Create unique filename if conflict exists
            $newName = $safeOriginalName;
            $i = 1;
            while (file_exists($authorityUploadDir . $newName)) {
                $newName = "{$baseName}_{$i}.{$extension}";
                $i++;
            }
            $targetFilePath = $authorityUploadDir . $newName;
            $publicFilePath = $authorityPublicUrl . $newName;

            // Move uploaded file
            if (move_uploaded_file($tmpName, $targetFilePath)) {
                // Insert into DB
                $success = $stmtInsert->execute([
                    $authorityId,      // authority_id
                    $safeOriginalName, // Store original (sanitized) name in DB
                    $publicFilePath,   // Store public URL path
                    $folderId,         // NULL if root
                    $size,
                    $extension
                ]);
                if ($success) {
                    $uploadedFilesInfo[] = ['name' => $safeOriginalName, 'path' => $publicFilePath, 'id' => $pdo->lastInsertId()];
                    // Log change
                    // logDatabaseChange(...)
                } else {
                    $errors[] = "Failed to save file '{$safeOriginalName}' metadata to database.";
                    unlink($targetFilePath); // Clean up saved file if DB insert fails
                }
            } else {
                $errors[] = "Failed to move uploaded file '{$safeOriginalName}' to destination.";
            }
        } // End foreach file

        // Send response
        if (!empty($uploadedFilesInfo) && empty($errors)) {
            http_response_code(201); // Created
            echo json_encode(["success" => true, "message" => "Files uploaded successfully.", "files" => $uploadedFilesInfo]);
        } elseif (!empty($uploadedFilesInfo) && !empty($errors)) {
            http_response_code(207); // Multi-Status
            echo json_encode(["success" => false, "message" => "Some files uploaded successfully, but errors occurred.", "files" => $uploadedFilesInfo, "errors" => $errors]);
        } else {
            http_response_code(400); // Bad Request or 500?
            echo json_encode(["success" => false, "message" => "No files were uploaded successfully.", "errors" => $errors]);
        }
    } catch (\PDOException $e) {
        error_log("DB error during file upload (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database error during file upload."]);
    } catch (\Exception $e) {
        error_log("General error during file upload (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not process file upload."]);
    }
}


function createFolder(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $name = $data['name'] ?? null;
    $parentIdInput = $data['parentId'] ?? null;
    $parentId = null;
    if ($parentIdInput !== null && $parentIdInput !== '' && $parentIdInput !== '0') {
        $parentId = filter_var($parentIdInput, FILTER_VALIDATE_INT);
        if ($parentId === false || $parentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parent ID specified.']);
            return;
        }
    }

    if (empty(trim($name ?? ''))) {
        http_response_code(400);
        echo json_encode(["error" => "Folder name cannot be empty."]);
        return;
    }
    // Add validation for folder name characters?

    try {
        // Optional: Check if parentId exists and belongs to the authority?

        $sql = "INSERT INTO `kdd_files_folder` (authority_id, name, parent_id, is_deleted) VALUES (?, ?, ?, 0)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $name, $parentId]);

        if ($success) {
            $newId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Folder created successfully.", "id" => $newId]);
            // Log change
            $changes = [['column_name' => 'name', 'old_value' => null, 'new_value' => $name], ['column_name' => 'parent_id', 'old_value' => null, 'new_value' => $parentId]];
            logDatabaseChange($authorityId, $pdo, 'INSERT', "files_folder", $newId, $requestingUserId, $changes);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create folder."]);
        }
    } catch (\PDOException $e) {
        // Handle potential unique name constraints within a parent folder?
        error_log("DB error in createFolder (Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not create folder."]);
    }
}

// Soft deletes file entry in DB. Does NOT delete physical file.
function deleteFile(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $fileId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$fileId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing file ID."]);
        return;
    }

    try {
        $sql = "UPDATE `kdd_files` SET is_deleted = 1 WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$authorityId, $fileId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "File marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "files", $fileId, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "File not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute file deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteFile (ID: $fileId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete file."]);
    }
}

// Soft deletes folder entry in DB. Does NOT delete physical folder or contents.
function deleteFolder(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $folderId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$folderId) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing folder ID."]);
        return;
    }

    try {
        // TODO: Check if folder is empty before deleting? Or handle recursive delete?
        // Current logic only soft-deletes the folder entry itself.

        $sql = "UPDATE `kdd_files_folder` SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$requestingUserId, $authorityId, $folderId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Folder marked as deleted."]);
            // Log change
            $changes = [['column_name' => 'is_deleted', 'old_value' => 0, 'new_value' => 1]];
            logDatabaseChange($authorityId, $pdo, 'DELETE', "files_folder", $folderId, $requestingUserId, $changes);
        } elseif ($success) {
            http_response_code(404);
            echo json_encode(["error" => "Folder not found or already deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute folder deletion.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in deleteFolder (ID: $folderId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not delete folder."]);
    }
}

function renameFolder(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $folderId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $newName = $data['name'] ?? null;

    if (!$folderId || empty(trim($newName ?? ''))) {
        http_response_code(400);
        echo json_encode(["error" => "Folder ID and new name are required."]);
        return;
    }

    try {
        // $oldEntry = getEntryById($pdo, $folderId, "kdd_files_folder"); // For logging

        $sql = "UPDATE `kdd_files_folder` SET name = ?, updated_by = ?, updated_at = NOW() WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$newName, $requestingUserId, $authorityId, $folderId]);

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Folder renamed successfully."]);
            // Log change
            // if ($oldEntry) { $changes = getEntryChanges($oldEntry, ['name'=>$newName]); logDatabaseChange(...); }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "Folder name not changed or folder not found."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute folder rename.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in renameFolder (ID: $folderId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not rename folder."]);
    }
}

function moveFile(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $fileId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $newFolderIdInput = $data['newFolderId'] ?? null;
    $newFolderId = null; // Assume root if not specified or '0'
    if ($newFolderIdInput !== null && $newFolderIdInput !== '' && $newFolderIdInput !== '0') {
        $newFolderId = filter_var($newFolderIdInput, FILTER_VALIDATE_INT);
        if ($newFolderId === false || $newFolderId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid target folder ID specified.']);
            return;
        }
    }

    if (!$fileId) {
        http_response_code(400);
        echo json_encode(["error" => "File ID is required."]);
        return;
    }

    try {
        // $oldEntry = getEntryById($pdo, $fileId, "kdd_files"); // For logging

        $sql = "UPDATE `kdd_files` SET folder_id = ?, updated_by = ?, updated_at = NOW() WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $newFolderId, $newFolderId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $requestingUserId, PDO::PARAM_INT);
        $stmt->bindValue(3, $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(4, $fileId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "File moved successfully."]);
            // Log change
            // if ($oldEntry) { $changes = getEntryChanges($oldEntry, ['folder_id'=>$newFolderId]); logDatabaseChange(...); }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "File not moved (already in target folder or file not found?)."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute file move.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in moveFile (ID: $fileId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not move file."]);
    }
}

function moveFolder(PDO $pdo, int $requestingUserId, string $authority): void
{
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Get data from JSON request
    $data = getJsonRequestData();

    $folderId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    $newParentIdInput = $data['newParentId'] ?? null;
    $newParentId = null; // Assume root
    if ($newParentIdInput !== null && $newParentIdInput !== '' && $newParentIdInput !== '0') {
        $newParentId = filter_var($newParentIdInput, FILTER_VALIDATE_INT);
        if ($newParentId === false || $newParentId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid target parent folder ID specified.']);
            return;
        }
    }

    if (!$folderId) {
        http_response_code(400);
        echo json_encode(["error" => "Folder ID is required."]);
        return;
    }
    // Prevent moving a folder into itself or one of its descendants (complex check, skipped for now)
    if ($folderId === $newParentId) {
        http_response_code(400);
        echo json_encode(["error" => "Cannot move a folder into itself."]);
        return;
    }
    // Optional: Check if newParentId exists?

    try {
        // $oldEntry = getEntryById($pdo, $folderId, "kdd_files_folder"); // For logging

        $sql = "UPDATE `kdd_files_folder` SET parent_id = ?, updated_by = ?, updated_at = NOW() WHERE authority_id = ? AND id = ? AND is_deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $newParentId, $newParentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(2, $requestingUserId, PDO::PARAM_INT);
        $stmt->bindValue(3, $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(4, $folderId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Folder moved successfully."]);
            // Log change
            // if ($oldEntry) { $changes = getEntryChanges($oldEntry, ['parent_id'=>$newParentId]); logDatabaseChange(...); }
        } elseif ($success) {
            http_response_code(200);
            echo json_encode(["error" => "Folder not moved (already in target folder or folder not found?)."]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to execute folder move.']);
        }
    } catch (\PDOException $e) {
        error_log("DB error in moveFolder (ID: $folderId, Authority: $authority): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Could not move folder."]);
    }
}

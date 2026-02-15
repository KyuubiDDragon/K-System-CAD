<?php
/**
 * Backend Endpoint: account/index.php
 * Handles account-related actions: registration, password changes, settings updates.
 * Uses Cookie-based Authentication and PDO database connection.
 */
declare(strict_types=1);

// Gemeinsamen Bootstrap-Code einbinden (ersetzt die vorherige Initialisierung)
require_once __DIR__ . '/../bootstrap.php';

// Zugriff auf globale Ressourcen aus bootstrap.php sicherstellen
global $pdo;
global $config;

// --- Routing & Action Handling ---
$method = $_SERVER['REQUEST_METHOD'];
// Decide whether to use GET or POST for actions
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to allow GET or POST for action trigger

// --- Public Actions (No Authentication Required) ---
if ($action === 'register' && $method === 'POST') {
    registerUser($pdo); // Pass PDO
    exit();
}
if ($action === 'reset_password' && $method === 'POST') {
    resetPassword($pdo); // Pass PDO
    exit();
}

// --- Authentication Check for Protected Actions ---
// All actions below this point require the user to be logged in.
try {
    require_once __DIR__ . '/../auth_check.php'; // Checks cookie, defines $decoded_jwt or exits
} catch (\Throwable $e) {
    error_log("Critical error during auth check include: " . $e->getMessage());
    http_response_code(500); echo json_encode(["error" => "Authentication system error."]); exit();
}

// --- User Context & Authority Validation (for protected actions) ---
$userId = $decoded_jwt->userId ?? null;
$userPermissions = $decoded_jwt->permissions ?? [];
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

if ($userId === null || $authority === null || $authorityId === null) {
    http_response_code(400); echo json_encode(["error" => "Invalid token payload."]); exit();
}

if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403); echo json_encode(["error" => "Invalid authority context."]); exit();
}

// Feature-Zugriff prüfen
if (!hasFeatureAccess($pdo, $authorityId, 'default')) {
    http_response_code(403); echo json_encode(["error" => "This authority doesn't have access to basic account features."]); exit();
}

// --- Authorization & Routing for Protected Actions ---
// Define permissions needed for protected actions in this file (module.action format)
$permissions_map = [
    'checkUser'             => ['module' => 'account', 'action' => 'read'],
    // Aktionen, die WRITE_ACCOUNT benötigen:
    'changePassword'        => ['module' => 'account', 'action' => 'write'],
    'updateTemplateImages'  => ['module' => 'account', 'action' => 'write'],
    'updateSignature'       => ['module' => 'account', 'action' => 'write'],
    'updateSettings'        => ['module' => 'account', 'action' => 'write'],
    // 'logout' action removed -> use login.php?action=logout
];

$required_permission = $permissions_map[$action] ?? null;
$has_permission = false;

if ($required_permission === null) {
    http_response_code(404); echo json_encode(['error' => 'Invalid action specified for authenticated user.']); exit();
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

// Execute action or deny
if ($has_permission) {
    // Check HTTP Method for protected actions
    switch ($action) {
        case 'changePassword':
            if ($method === 'POST') changePassword($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateTemplateImages':
             if ($method === 'POST') updateTemplateImages($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'checkUser':
             // This was originally used by reloadUserData, might be GET
             if ($method === 'POST' || $method === 'GET') checkUser($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateSignature':
             if ($method === 'POST') updateSignature($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        case 'updateSettings':
             if ($method === 'POST') updateSettings($pdo, $userId, $authority, $authorityId); else MethodNotAllowed(); break;
        default:
            http_response_code(500); echo json_encode(['error' => 'Protected action routing error.']); break;
    }
} else {
    http_response_code(403); echo json_encode(["error" => "Permission denied for action: " . htmlspecialchars($action)]);
}

exit(); // End script

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

// --- Function Implementations ---

// Public function: registerUser (Needs specific INSERT logic)
function registerUser(PDO $pdo): void {
    // Get data from JSON request instead of POST
    $data = getJsonRequestData();
    if (!$data) return; // Error already handled in getJsonRequestData()

    $username = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;
    $authority = $data['authority'] ?? null;
    
    if (!$username || !$email || !$password || !$authority) {
        http_response_code(400); 
        echo json_encode(["error" => "Missing required registration data."]); 
        return;
    }
    
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         http_response_code(400); echo json_encode(["error" => "Invalid email format."]); return;
    }
    // Add more validation: password strength, username uniqueness, authority validation etc.

    try {
        // Check if username or email already exists
        $sqlCheck = "SELECT id FROM kdd_users WHERE authority_id = ? AND (username = ? OR email = ?)";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $username, $email]);
        if ($stmtCheck->fetch()) {
             http_response_code(409); // Conflict
             echo json_encode(["error" => "Username or Email already exists."]);
             return;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Use PASSWORD_DEFAULT

        // --- INSERT User Logic ---
        // This needs to be adapted to your EXACT kdd_users table structure
        $sqlInsert = "INSERT INTO kdd_users (authority_id, username, email, password, authority, created_at, updated_at)
                      VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);

        $success = $stmtInsert->execute([$authorityId, $username, $email, $hashed_password, $authority]);

        if ($success) {
            $newUserId = $pdo->lastInsertId();
            // Maybe automatically assign default roles/permissions here?
            // Maybe log the registration? logDatabaseChange(...)
            http_response_code(201); // Created
            echo json_encode(["message" => "User registered successfully.", "userId" => $newUserId]);
        } else {
             http_response_code(500);
             echo json_encode(["error" => "Failed to register user."]);
        }
        // --- End INSERT ---

    } catch (\PDOException $e) {
        error_log("Database error during registration: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "An internal error occurred during registration."]);
    }
}

// Public function: resetPassword (Needs specific logic and email sending)
function resetPassword(PDO $pdo): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $email = $data['email'] ?? null;
    $authority = $data['authority'] ?? null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$authority) {
        http_response_code(400); 
        echo json_encode(["error" => "Invalid or missing email address or authority."]); 
        return;
    }
    
    // Get authority ID
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        return;
    }

    try {
        // Check if email exists
        $sqlCheck = "SELECT id FROM kdd_users WHERE authority_id = ? AND email = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$authorityId, $email]);
        $userId = $stmtCheck->fetchColumn();

        if ($userId) {
            // Email exists
            $temporary_password = generateRandomPassword();
            $hashed_temporary_password = password_hash($temporary_password, PASSWORD_DEFAULT);

            // Update password in DB
            $sqlUpdate = "UPDATE kdd_users SET password = ? WHERE authority_id = ? AND id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $success = $stmtUpdate->execute([$hashed_temporary_password, $authorityId, $userId]);

            if ($success) {
                // --- Email Sending Logic ---
                // IMPLEMENT email sending here! Use a library like PHPMailer or Symfony Mailer.
                $mail_sent = false; // Placeholder
                // $mail_sent = send_reset_email($email, $temporary_password);

                if ($mail_sent) {
                    http_response_code(200);
                    echo json_encode(["message" => "A temporary password has been sent to your email address."]);
                    // Log password reset action? logDatabaseChange(...)
                } else {
                     http_response_code(500);
                     echo json_encode(["error" => "Password reset in database, but failed to send email."]); // Critical error
                     error_log("Failed to send password reset email to: " . $email);
                }
                // --- End Email Sending ---
            } else {
                 http_response_code(500);
                 echo json_encode(["error" => "Failed to update password in database."]);
            }
        } else {
            // Email not found - send success message anyway to prevent user enumeration
            http_response_code(200); // Or maybe 404? Security vs UX tradeoff. 200 is often preferred.
            echo json_encode(["message" => "If an account with that email exists, a temporary password has been sent."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error during password reset: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "An internal error occurred during password reset."]);
    }
}

// Protected function: changePassword
function changePassword(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $old_password = $data['oldPassword'] ?? null;
    $new_password = $data['password'] ?? null;

    if (!$old_password || !$new_password) {
        http_response_code(400); 
        echo json_encode(["error" => "Old and new passwords are required."]); 
        return;
    }
    // Add password strength validation for $new_password if needed

    try {
        // Get current password hash
        $sqlSelect = "SELECT password FROM kdd_users WHERE authority_id = ? AND id = ?";
        $stmtSelect = $pdo->prepare($sqlSelect);
        $stmtSelect->execute([$authorityId, $userId]);
        $stored_hashed_password = $stmtSelect->fetchColumn();

        if (!$stored_hashed_password) {
             http_response_code(404); // Should not happen if token is valid
             echo json_encode(["error" => "User not found."]); return;
        }

        // Verify old password
        if (password_verify($old_password, $stored_hashed_password)) {
            // Hash new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password in DB
            $sqlUpdate = "UPDATE kdd_users SET password = ? WHERE authority_id = ? AND id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $success = $stmtUpdate->execute([$hashed_new_password, $authorityId, $userId]);

            if ($success) {
                http_response_code(200);
                echo json_encode(["success" => true, "message" => "Password changed successfully."]);
                // Log change
                $changes = [['column_name' => 'password', 'old_value' => '********', 'new_value' => '********']]; // Don't log hashes
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $userId, $changes);
            } else {
                 http_response_code(500);
                 echo json_encode(["error" => "Failed to update password."]);
            }
        } else {
            // Old password incorrect - return specific error message
            http_response_code(401); // Unauthorized (incorrect credential)
            echo json_encode(["success" => false, "error" => "Incorrect old password."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error during password change for user $userId: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "An internal error occurred."]);
    }
}

// Protected function: updateTemplateImages
// Depends on helper functions: getEntryById, getEntryChanges (assumed in logging.php)
function updateTemplateImages(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $isNeutral = ($data['neutral'] ?? 'false') == 'true';
    $sql = "";
    $params = [];

    // Assuming getEntryById accepts PDO now and returns an array or object
    $oldEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
    if (!$oldEvent) { http_response_code(404); echo json_encode(["error" => "User not found for update."]); return; }

    if ($isNeutral) {
        $headerImageNeutral = $data['headerImageNeutral'] ?? null;
        $footerImageNeutral = $data['footerImageNeutral'] ?? null;
        if ($headerImageNeutral === null || $footerImageNeutral === null) {
             http_response_code(400); echo json_encode(["error" => "Missing neutral template images."]); return;
        }
        $sql = "UPDATE kdd_users SET mail_header_neutral = ?, mail_footer_neutral = ? WHERE authority_id = ? AND id = ?";
        $params = [$headerImageNeutral, $footerImageNeutral, $authorityId, $userId];
    } else {
        $headerImage = $data['headerImage'] ?? null;
        $footerImage = $data['footerImage'] ?? null;
         if ($headerImage === null || $footerImage === null) {
             http_response_code(400); echo json_encode(["error" => "Missing standard template images."]); return;
        }
        $sql = "UPDATE kdd_users SET mail_header = ?, mail_footer = ? WHERE authority_id = ? AND id = ?";
        $params = [$headerImage, $footerImage, $authorityId, $userId];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if ($success) {
             http_response_code(200);
             echo json_encode(["success" => true, "message" => "Template images updated successfully."]);
             // Log changes
             $updatedEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
             $changes = getEntryChanges($oldEvent, $updatedEvent); // Assume this works with PDO results
             if (!empty($changes)) {
                 global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $userId, $changes);
             }
        } else {
            http_response_code(500); echo json_encode(["error" => "Failed to update template images."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in updateTemplateImages for user $userId: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update template images."]);
    }
}

// Protected function: updateSignature
// Depends on helper functions: getEntryById, getEntryChanges (assumed in logging.php)
function updateSignature(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $signature = $data['signature'] ?? null;
    if ($signature === null) {
        http_response_code(400); 
        echo json_encode(["error" => "Missing signature data."]); 
        return;
    }

    $oldEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
    if (!$oldEvent) { http_response_code(404); echo json_encode(["error" => "User not found for update."]); return; }

    try {
        $sql = "UPDATE kdd_users SET signature = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$signature, $authorityId, $userId]);

        if ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Signature updated successfully."]);
            // Log changes
            $updatedEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
            $changes = getEntryChanges($oldEvent, $updatedEvent);
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $userId, $changes);
            }
        } else {
            http_response_code(500); echo json_encode(["error" => "Failed to update signature."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in updateSignature for user $userId: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update signature."]);
    }
}

// Protected function: updateSettings
// Depends on helper functions: getEntryById, getEntryChanges (assumed in logging.php)
function updateSettings(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    $data = getJsonRequestData();
    if (!$data) return;

    $documentView = $data['documentView'] ?? null;
    if ($documentView === null) {
        http_response_code(400); 
        echo json_encode(["error" => "Missing documentView data."]); 
        return;
    }
    // Add validation for allowed documentView values if necessary

    $oldEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
    if (!$oldEvent) { http_response_code(404); echo json_encode(["error" => "User not found for update."]); return; }

    try {
        $sql = "UPDATE kdd_users SET documentView = ? WHERE authority_id = ? AND id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$documentView, $authorityId, $userId]);

        if ($success) {
            http_response_code(200);
            echo json_encode(["success" => true, "message" => "Settings updated successfully."]);
            // Log changes
            $updatedEvent = getEntryById($pdo, $userId, 'kdd_users', $authorityId);
            $changes = getEntryChanges($oldEvent, $updatedEvent);
            if (!empty($changes)) {
                global $decoded_jwt;
                $authorityId = $decoded_jwt->authority_id ?? 0;
                logDatabaseChange($authorityId, $pdo, 'UPDATE', 'kdd_users', $userId, $userId, $changes);
            }
        } else {
            http_response_code(500); echo json_encode(["error" => "Failed to update settings."]);
        }
    } catch (\PDOException $e) {
        error_log("Database error in updateSettings for user $userId: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not update settings."]);
    }
}

// Protected function: checkUser (Refactored for PDO)
function checkUser(PDO $pdo, int $userId, string $authority, int $authorityId): void {
    try {
        // Fetch permissions and banned status using JOINs
        $sql = "SELECT p.name as permission_name, u.banned FROM kdd_users u
                LEFT JOIN kdd_user_roles ur ON ur.authority_id = u.authority_id AND u.id = ur.user_id
                LEFT JOIN kdd_roles r ON r.authority_id = ur.authority_id AND ur.role_id = r.id
                LEFT JOIN kdd_role_permissions rp ON rp.authority_id = r.authority_id AND r.id = rp.role_id
                LEFT JOIN kdd_permissions p ON rp.permission_id = p.id
                WHERE u.authority_id = ? AND u.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$authorityId, $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            // User exists (token was valid) but has no roles/permissions assigned? Or user deleted since token issued?
            http_response_code(404); // Or 403? User exists but has no access rights basically.
            echo json_encode(["error" => "User found but has no assigned roles or permissions."]);
            return;
        }

        $permissions = [];
        $banned = null;
        foreach ($results as $row) {
            if ($row['permission_name'] !== null) { // Avoid adding NULL if user has role with 0 permissions
                $permissions[] = $row['permission_name'];
            }
            if ($banned === null) { // Get banned status from the first row (should be same for all rows of the user)
                $banned = $row['banned'];
            }
        }

        http_response_code(200);
        echo json_encode(["permissions" => array_unique($permissions), "banned" => (bool)$banned]); // Cast banned to boolean

    } catch (\PDOException $e) {
        error_log("Database error in checkUser for user $userId: " . $e->getMessage());
        http_response_code(500); echo json_encode(["error" => "Could not check user status."]);
    }
}

// Unchanged helper function
function generateRandomPassword($length = 10): string {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_password = '';
    for ($i = 0; $i < $length; $i++) {
        $random_password .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_password;
}
?>
<?php
/**
 * dropbox.php
 * Helper functions for interacting with the Dropbox API v2.
 * Uses PDO for storing/retrieving API tokens.
 */
declare(strict_types=1);

// No need for direct .env loading or error reporting setup here,
// assuming the calling script handles this.

// No need for CORS headers or OPTIONS handling.

// require_once __DIR__ . '/../vendor/autoload.php'; // Composer's autoloader should be loaded by the calling script.
// require_once __DIR__ . '/../db.php'; // The $pdo object should be passed into functions needing it.
// require_once __DIR__ . '/../logging/logging.php'; // Not used directly here.

use GuzzleHttp\Client; // Example if using Guzzle, otherwise stick to cURL
use GuzzleHttp\Exception\RequestException;

/**
 * Uploads a file to a specified Dropbox path. Handles token refresh.
 *
 * @param PDO $pdo PDO database object (for token refresh).
 * @param string $accessToken Current Dropbox Access Token.
 * @param string $refreshToken Dropbox Refresh Token.
 * @param string $clientId Dropbox App Client ID.
 * @param string $clientSecret Dropbox App Client Secret.
 * @param string $sourceFilePath Absolute path to the local file to upload.
 * @param string $dropboxPath Full path in Dropbox where the file should be saved (e.g., /Apps/MyApp/file.txt).
 * @param int $retryCount Internal counter to prevent infinite refresh loops.
 * @return array Status array: ['status' => 'success', 'shareableLink' => '...'] or ['status' => 'error', 'message' => '...']
 */
function uploadToDropbox(PDO $pdo, string $accessToken, string $refreshToken, string $clientId, string $clientSecret, string $sourceFilePath, string $dropboxPath, int $retryCount = 0): array {
    // Basic check if source file exists
    if (!file_exists($sourceFilePath) || !is_readable($sourceFilePath)) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

        return ['status' => 'error', 'message' => 'Source file does not exist or is not readable: ' . $sourceFilePath];
    }

    $maxRetries = 1; // Allow only one retry after token refresh

    try {
        $uploadUrl = 'https://content.dropboxapi.com/2/files/upload';
        $apiArgs = json_encode([
            'path' => $dropboxPath,
            'mode' => 'add', // Or 'overwrite'
            'autorename' => true,
            'mute' => false,
            'strict_conflict' => false
        ]);

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . $apiArgs
        ];

        $fileData = file_get_contents($sourceFilePath);
        if ($fileData === false) {
            return ['status' => 'error', 'message' => 'Could not read file content from: ' . $sourceFilePath];
        }

        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increase timeout for uploads
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("cURL Error during upload: " . $curlError);
        }

        if ($httpCode === 200) {
            $responseJson = json_decode($response, true);
            if (!isset($responseJson['path_lower'])) {
                 throw new Exception("Dropbox upload response missing 'path_lower'. Response: " . $response);
            }

            // Try to create a shareable link
            $shareableLinkResult = createShareableLink($accessToken, $responseJson['path_lower']);
            if ($shareableLinkResult['status'] === 'success') {
                return [
                    'status' => 'success',
                    'dropbox_path' => $responseJson['path_display'] ?? $responseJson['path_lower'],
                    'shareableLink' => $shareableLinkResult['shareableLink']
                ];
            } else {
                // Upload succeeded, but link creation failed
                return [
                    'status' => 'warning', // Or error?
                    'message' => 'File uploaded successfully but failed to create shareable link: ' . $shareableLinkResult['message'],
                    'dropbox_path' => $responseJson['path_display'] ?? $responseJson['path_lower']
                 ];
            }
        } elseif ($httpCode === 401 && $retryCount < $maxRetries) { // Access token likely expired
            error_log("Dropbox access token expired. Attempting refresh (Retry " . ($retryCount + 1) . ")");
            $refreshResult = refreshToken($pdo, $clientId, $clientSecret, $refreshToken); // Pass PDO
            if ($refreshResult['status'] === 'success') {
                // Retry the upload with the new token
                return uploadToDropbox($pdo, $refreshResult['accessToken'], $refreshToken, $clientId, $clientSecret, $sourceFilePath, $dropboxPath, $retryCount + 1);
            } else {
                // Refresh failed
                return ['status' => 'error', 'message' => 'Failed to refresh Dropbox token: ' . $refreshResult['message']];
            }
        } else {
            // Other HTTP errors
            $errorMessage = "Error uploading file to Dropbox. HTTP Code: {$httpCode}. Response: " . $response;
            error_log($errorMessage);
            return ['status' => 'error', 'message' => $errorMessage];
        }
    } catch (\Throwable $e) { // Catch any exception/error
        error_log("Exception in uploadToDropbox: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Upload failed: " . $e->getMessage()];
    }
}


/**
 * Creates a public shareable link for a file/folder in Dropbox.
 *
 * @param string $accessToken Dropbox Access Token.
 * @param string $dropboxPath Path to the file/folder in Dropbox (e.g., /path/to/file.txt).
 * @return array Status array: ['status' => 'success', 'shareableLink' => '...'] or ['status' => 'error', 'message' => '...']
 */
function createShareableLink(string $accessToken, string $dropboxPath): array {
    try {
        $url = 'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings';
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        $postFields = json_encode([
            'path' => $dropboxPath,
            'settings' => [
                'requested_visibility' => 'public' // Create a link anyone can view
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout for link creation

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
 throw new Exception("cURL Error: " . $curlError); }

        $responseJson = json_decode($response, true);

        if ($httpCode === 200 && isset($responseJson['url'])) {
            // Link created successfully, modify for direct download/view
            $shareableLink = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $responseJson['url']); // More direct link
            $shareableLink = str_replace('?dl=0', '', $shareableLink); // Remove dl=0 if present

            return ['status' => 'success', 'shareableLink' => $shareableLink];
        } elseif ($httpCode === 409 && strpos($response, "shared_link_already_exists") !== false) {
             // Link already exists, try to retrieve it
             error_log("Shareable link already exists for path: " . $dropboxPath . ". Attempting to retrieve.");
             return getExistingShareableLink($accessToken, $dropboxPath); // Call helper to get existing link
        } else {
             $errorMessage = "Error creating shareable link. HTTP Code: {$httpCode}. Response: " . $response;
             error_log($errorMessage);
             return ['status' => 'error', 'message' => $errorMessage];
        }
    } catch (\Throwable $e) {
        error_log("Exception in createShareableLink: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Link creation failed: " . $e->getMessage()];
    }
}

/**
 * Helper function to get existing shareable links for a path.
 *
 * @param string $accessToken Dropbox Access Token.
 * @param string $dropboxPath Path to the file/folder in Dropbox.
 * @return array Status array like createShareableLink.
 */
function getExistingShareableLink(string $accessToken, string $dropboxPath): array {
     try {
        $url = 'https://api.dropboxapi.com/2/sharing/list_shared_links';
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        $postFields = json_encode(['path' => $dropboxPath]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
 throw new Exception("cURL Error getting existing links: " . $curlError); }

        $responseJson = json_decode($response, true);

        // Find the first public link if available
        if ($httpCode === 200 && isset($responseJson['links']) && !empty($responseJson['links'])) {
            foreach ($responseJson['links'] as $link) {
                 // Check if link visibility is public (or desired type)
                 if (isset($link['link_permissions']['resolved_visibility']['.tag']) && $link['link_permissions']['resolved_visibility']['.tag'] === 'public') {
                    $shareableLink = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $link['url']);
                    $shareableLink = str_replace('?dl=0', '', $shareableLink);
                    return ['status' => 'success', 'shareableLink' => $shareableLink];
                 }
            }
             // No suitable existing public link found, maybe create a new one was the right initial approach
             return ['status' => 'error', 'message' => 'Shared link already exists, but no suitable public link found.'];
        } else {
             $errorMessage = "Error retrieving existing shareable links. HTTP Code: {$httpCode}. Response: " . $response;
             error_log($errorMessage);
             return ['status' => 'error', 'message' => $errorMessage];
        }
    } catch (\Throwable $e) {
        error_log("Exception in getExistingShareableLink: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Getting existing link failed: " . $e->getMessage()];
    }
}


/**
 * Refreshes the Dropbox OAuth2 Access Token using a Refresh Token.
 * Updates the token in the database.
 *
 * @param PDO $pdo PDO database object.
 * @param string $clientId Dropbox App Client ID.
 * @param string $clientSecret Dropbox App Client Secret.
 * @param string $refreshToken Current Dropbox Refresh Token.
 * @return array Status array: ['status' => 'success', 'accessToken' => '...'] or ['status' => 'error', 'message' => '...']
 */
function refreshToken(PDO $pdo, string $clientId, string $clientSecret, string $refreshToken): array {
    try {
        $url = "https://api.dropbox.com/oauth2/token";
        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }
 throw new Exception("cURL Error refreshing token: " . $curlError); }

        $responseData = json_decode($response, true);

        if ($httpCode === 200 && isset($responseData['access_token'])) {
            $newAccessToken = $responseData['access_token'];
            // Update the new access token in the database
            if (updateTokenData($pdo, $newAccessToken, $refreshToken)) { // Pass PDO
                 return ['status' => 'success', 'accessToken' => $newAccessToken];
            } else {
                 return ['status' => 'error', 'message' => 'Token refreshed but failed to update database.'];
            }
        } else {
            $errorMessage = "Token refresh failed. HTTP Code: {$httpCode}. Response: " . $response;
            error_log($errorMessage);
            return ['status' => 'error', 'message' => $errorMessage];
        }
    } catch (\Throwable $e) {
        error_log("Exception in refreshToken: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Token refresh failed: " . $e->getMessage()];
    }
}


/**
 * Retrieves Dropbox API credentials and tokens from the database.
 * Assumes a single entry with id = 1.
 *
 * @param PDO $pdo PDO database object.
 * @return array|false Associative array with token data or false on failure.
 */
function getTokenData(PDO $pdo) {
    // Get authority ID
    global $decoded;
    $authority = $decoded->authority ?? null;
    global $decoded_jwt;
    $authorityId = $decoded_jwt->authority_id ?? 0;
    if (!$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid authority']);
        exit;
    }

    // Removed: global $authority, require "../db.php"
    try {
        // Table name seems global (no authority prefix)
        $stmt = $pdo->prepare("SELECT access_token, refresh_token, client_id, client_secret FROM dropbox_tokens WHERE id = ? LIMIT 1");
        $stmt->execute([$authorityId, 1]); // Hardcoded ID = 1
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch associative array

        // Return data or false if not found
        return $tokenData ?: false;

    } catch (\PDOException $e) {
        error_log("DB error in getTokenData: " . $e->getMessage());
        return false; // Signal error
    }
}

/**
 * Updates the Dropbox Access Token in the database.
 * Uses the Refresh Token to identify the row (assuming it's unique).
 *
 * @param PDO $pdo PDO database object.
 * @param string $newAccessToken The new access token.
 * @param string $refreshToken The refresh token used to identify the record.
 * @return bool True on success, False on failure.
 */
function updateTokenData(PDO $pdo, string $newAccessToken, string $refreshToken): bool {
    // Removed: global $authority, require "../db.php"
    try {
        $stmt = $pdo->prepare("UPDATE dropbox_tokens SET access_token = ? WHERE refresh_token = ?");
        $success = $stmt->execute([$authorityId, $newAccessToken, $refreshToken]);

        // Check if the update was successful and affected a row
        if ($success && $stmt->rowCount() > 0) {
            return true;
        } elseif ($success) {
            // Execution succeeded but no rows affected (maybe refresh token didn't match?)
            error_log("Access token update executed but no rows affected for refresh token ending in " . substr($refreshToken, -6));
            return false; // Indicate potential issue
        } else {
            // execute() returned false
            error_log("Failed to execute access token update statement.");
            return false;
        }
    } catch (\PDOException $e) {
        error_log("DB error in updateTokenData: " . $e->getMessage());
        return false; // Signal error
    }
}

?>
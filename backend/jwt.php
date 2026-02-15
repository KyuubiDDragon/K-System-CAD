<?php
// backend/jwt.php - Complete File (Version using .env and firebase/php-jwt v6+)

// Use strict types for better code quality
declare(strict_types=1);

// --- Dependencies ---
// Note: This file expects bootstrap.php to already be loaded by the calling script
// Do NOT include bootstrap.php here to avoid circular/redundant includes
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;


/**
 * Creates a JWT for the given user.
 * Uses configuration from the loaded .env file.
 *
 * @param array $user User data array (must include id, username, authority, authority_id)
 * @param PDO $pdo Database connection for loading permissions
 * @param bool $rememberMe If true, sets a longer expiration time (e.g., 30 days).
 * @return string The encoded JWT.
 * @throws InvalidArgumentException If required JWT configuration (.env variables) is missing.
 */
function create_jwt(array $user, \PDO $pdo, bool $rememberMe = false): string {
    // --- Get configuration from environment variables ---
    $key = $_ENV['JWT_SECRET_KEY'] ?? getenv('JWT_SECRET_KEY');
    $issuer = $_ENV['JWT_ISSUER'] ?? getenv('JWT_ISSUER') ?? 'K-Systems';
    $audience = $_ENV['JWT_AUDIENCE'] ?? getenv('JWT_AUDIENCE') ?? 'K-Systems';
    $defaultExpiration = $_ENV['JWT_EXPIRATION_TIME'] ?? getenv('JWT_EXPIRATION_TIME') ?? 3600;

    // Validate that required config is present
    if (!$key) {
        error_log("JWT Configuration Error: Missing JWT_SECRET_KEY in environment variables");
        throw new \InvalidArgumentException("JWT configuration is incomplete: missing secret key");
    }
    // --- End configuration ---

    $algorithm = 'HS256'; // Define the algorithm used
    $iat = time(); // Issued At timestamp
    $nbf = $iat; // Not Before timestamp (token is valid immediately)

    // Calculate expiration timestamp based on rememberMe flag
    // Default: 15 minutes (900 seconds), Remember-Me: 30 days
    $expirationSeconds = $rememberMe ? (60 * 60 * 24 * 30) : 900; // 30 days or 15 minutes
    $exp = $iat + $expirationSeconds; // Expiration timestamp

    // Optional Key ID (can be used for key rotation verification)
    // Keep hardcoded for now, or make configurable via .env if needed
    $kid = "7e2760d6-81e2-11ec-8be7-0242ac130002";

    // --- Load Permissions as Bitmask ---
    require_once __DIR__ . '/helpers/PermissionManager.php';

    $permissionManager = new PermissionManager($pdo, (int)$user['authority_id']);
    $permissionsBitmask = $permissionManager->loadUserPermissions((int)$user['id']);
    // --- End Permissions Loading ---

    // --- Load Active Features ---
    require_once __DIR__ . '/utils/authority_helper.php';
    $activeFeatures = getAuthorityActiveFeatures($pdo, (int)$user['authority_id']);
    // --- End Features Loading ---

    // --- Build Payload ---
    // Include necessary claims for authentication and authorization.
    // Mail fields (header/footer) are NO LONGER included to reduce token size below 4KB cookie limit.
    // These are now stored in localStorage on the frontend (loaded from login response).

    // Generate unique JWT ID (jti) for session tracking
    $jti = bin2hex(random_bytes(16)); // 32 character hex string

    $payload = [
        "iss" => $issuer,          // Issuer of the token (your application)
        "aud" => $audience,        // Audience the token is intended for (your application)
        "iat" => $iat,             // Issued At time
        "nbf" => $nbf,             // Not Before time
        "exp" => $exp,             // Expiration time
        "jti" => $jti,             // ✅ JWT ID - unique identifier for this token/session
        // --- User specific claims ---
        "userId"       => $user['id'],      // User's unique ID (camelCase convention suggested)
        "username"     => $user['username'],
        "authority"    => $user['authority'],       // User's authority context
        "authority_id" => $user['authority_id'] ?? null, // Authority ID for database filtering
        "permissions"  => $permissionsBitmask,  // ✅ Permissions as bitmask object { employee: 7, company: 3, ... }
        "active_features" => $activeFeatures,   // ✅ Active features for this authority ['employee', 'calendar', 'document', ...]
        "banned"       => !empty($user['banned']), // ✅ User suspension status (boolean)
        // ❌ REMOVED: mail_header, mail_footer, mail_header_neutral, mail_footer_neutral
        // → Now stored in localStorage via frontend (see auth.ts)
    ];
    // --- End Payload ---

    // --- Build Header (Optional) ---
    // The firebase/php-jwt library automatically adds 'alg' and 'typ'.
    // Add 'kid' (Key ID) if you use it.
    $header = [
        "kid" => $kid
    ];
    // --- End Header ---

    // Encode the payload into a JWT string
    try {
        $jwt = JWT::encode($payload, $key, $algorithm, null, $header);

        // --- Token Size Monitoring ---
        $tokenSize = strlen($jwt);
        error_log("[JWT] Token created for user {$user['id']} - Size: {$tokenSize} bytes");

        // Warning if approaching 4KB cookie limit
        if ($tokenSize > 3500) {
            error_log("[JWT] ⚠️ WARNING: Large token ({$tokenSize} bytes) approaching 4KB limit!");
        }
        // --- End Monitoring ---

        // --- Optional Compression (for tokens > 2.5KB) ---
        if ($tokenSize > 2500) {
            $compressed = gzcompress($jwt, 6);  // Level 6 = Balance between speed and size
            $compressedSize = strlen($compressed);

            // Only use compression if it saves > 20%
            if ($compressedSize < $tokenSize * 0.8) {
                $jwt = 'gz:' . base64_encode($compressed);
                error_log("[JWT] Compressed: {$tokenSize}b → {$compressedSize}b (saved " . round((1 - $compressedSize/$tokenSize) * 100) . "%)");
            } else {
                error_log("[JWT] Compression not beneficial ({$compressedSize}b vs {$tokenSize}b), using uncompressed");
            }
        }
        // --- End Compression ---

        return $jwt;
    } catch (\Exception $e) {
        error_log("JWT Encoding Error: " . $e->getMessage());
        // Re-throw or handle as appropriate for your application
        throw new \RuntimeException("Could not create authentication token.", 500, $e);
    }
}


/**
 * Extract JWT ID (jti) from token without full validation
 * Useful for session lookups before full validation
 *
 * @param string $jwt The JWT string
 * @return string|null The jti claim or null if not found
 */
function get_jti_from_jwt(string $jwt): ?string {
    try {
        // Handle compressed tokens
        if (str_starts_with($jwt, 'gz:')) {
            $compressed = base64_decode(substr($jwt, 3));
            $jwt = gzuncompress($compressed);
        }

        // Split JWT into parts
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        // Decode payload (no validation, just parsing)
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return $payload['jti'] ?? null;
    } catch (\Exception $e) {
        error_log("[JWT] Error extracting jti: " . $e->getMessage());
        return null;
    }
}

/**
 * Decodes and validates a JWT string based on configuration from .env.
 * Checks signature, expiration, nbf, issuer, and audience.
 *
 * @param string $jwt The JWT string to decode.
 * @return object The decoded payload as a standard PHP object.
 * @throws ExpiredException If the token is expired.
 * @throws SignatureInvalidException If the signature is invalid.
 * @throws BeforeValidException If the token is not yet valid (NBF check fails).
 * @throws \UnexpectedValueException If issuer, audience, or other JWT structure checks fail.
 * @throws InvalidArgumentException If required JWT configuration (.env variables) is missing for decoding.
 */
function decode_jwt(string $jwt): object {
    // --- Get configuration from environment variables ---
    $key = $_ENV['JWT_SECRET_KEY'] ?? getenv('JWT_SECRET_KEY');
    $audience = $_ENV['JWT_AUDIENCE'] ?? getenv('JWT_AUDIENCE') ?? 'K-Systems';
    $issuer = $_ENV['JWT_ISSUER'] ?? getenv('JWT_ISSUER') ?? 'K-Systems';

    // Validate required config for decoding
    if (!$key) {
        error_log("JWT Configuration Error: Missing JWT_SECRET_KEY in environment variables");
        throw new \InvalidArgumentException("JWT configuration is incomplete: missing secret key");
    }
    // --- End configuration ---

    $algorithm = 'HS256'; // Must match the encoding algorithm

    // --- Check for Compression ---
    if (str_starts_with($jwt, 'gz:')) {
        // Token is compressed, decompress it
        $compressed = base64_decode(substr($jwt, 3));
        $jwt = gzuncompress($compressed);
        if ($jwt === false) {
            error_log("JWT Decompression Error: Failed to decompress token");
            throw new \UnexpectedValueException('Invalid compressed token.', 401);
        }
        error_log("[JWT] Decompressed token");
    }
    // --- End Decompression ---

    // --- Decode and Validate ---
    try {
        // Decode the JWT. This automatically verifies:
        // - Signature (using $key and $algorithm)
        // - Expiration ('exp' claim)
        // - Not Before ('nbf' claim)
        // Requires firebase/php-jwt v6+ which uses the Key object.
        $decoded = JWT::decode($jwt, new Key($key, $algorithm));

        // === Perform manual validation for Audience (aud) and Issuer (iss) ===
        // Ensure these claims exist and match the expected values from .env
        if (!isset($decoded->aud) || $decoded->aud !== $audience) {
            throw new \UnexpectedValueException('Invalid token audience.');
        }
        if (!isset($decoded->iss) || $decoded->iss !== $issuer) {
             throw new \UnexpectedValueException('Invalid token issuer.');
        }
        // === End Manual Validation ===

        // Return the decoded payload if all checks pass
        return $decoded;

    // Catch specific JWT exceptions for detailed error handling
    } catch (ExpiredException $e) {
        // Token is correctly signed but has expired
        throw $e; // Re-throw for specific handling upstream (e.g., in auth_check.php)
    } catch (SignatureInvalidException $e) {
        // Signature verification failed
        error_log("JWT Signature Verification Failed: " . $e->getMessage());
        throw new \UnexpectedValueException('Invalid token signature.', 401, $e);
    } catch (BeforeValidException $e) {
        // Token is not yet valid (based on 'nbf' claim)
        error_log("JWT Before Valid Exception: " . $e->getMessage());
        throw new \UnexpectedValueException('Token not yet valid.', 401, $e);
    } catch (\Exception $e) {
        // Catch other potential exceptions during decoding (e.g., malformed token)
        error_log("JWT General Decode Exception: " . $e->getMessage());
        // Wrap in a standard exception type for consistent upstream handling
        throw new \UnexpectedValueException('Invalid token: ' . $e->getMessage(), 401, $e);
    }
}


/**
 * Extracts the Bearer token from the Authorization header.
 * This function is kept for potential other uses but is deprecated
 * for the primary cookie-based authentication flow.
 *
 * @param array $headers Associative array of headers (e.g., from getallheaders() or $_SERVER).
 * @return string|null The token string or null if not found or format is incorrect.
 * @deprecated Use cookie-based authentication ('auth_token' cookie) instead.
 */
function getBearerToken(array $headers): ?string {
    // Trigger a deprecated notice if this function is actually called
    trigger_error('Function getBearerToken() is deprecated for the primary cookie authentication flow.', E_USER_DEPRECATED);

    $authHeader = null;
    // Find the Authorization header case-insensitively
    foreach ($headers as $headerName => $headerValue) {
        if (strtolower($headerName) === 'authorization') {
            // Handle cases where header value might be an array (less common)
            $authHeader = is_array($headerValue) ? $headerValue[0] : (string) $headerValue;
            break;
        }
    }

    // Check if header exists and matches the Bearer format
    if ($authHeader !== null) {
        if (preg_match('/^Bearer\s+(\S+)/i', $authHeader, $matches)) {
            return $matches[1]; // Return the token part
        }
    }

    return null; // No valid Bearer token found
}

/**
 * Helper function to ensure a value is an array
 * @param mixed $value Value to convert to array if needed
 * @return array The resulting array
 */
function ensureArray($value): array {
    if (is_array($value)) {
        return $value;
    }
    if (is_object($value)) {
        return (array)$value;
    }
    if ($value === null) {
        return [];
    }
    // For scalar values, make a single-item array
    return [$value];
}

?>
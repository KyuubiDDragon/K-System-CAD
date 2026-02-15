<?php
/**
 * Authentication and Authorization Service
 * Handles JWT validation and permission checks
 */

declare(strict_types=1);

namespace CompanyWebsite\Services;

use PDO;

class AuthService
{
    private PDO $pdo;
    private $decoded_jwt;
    private array $userPermissions = [];
    private ?int $userId = null;
    private ?string $authority = null;
    private ?int $authorityId = null;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     * @param bool $requireAuth Whether authentication is required
     */
    public function __construct(PDO $pdo, bool $requireAuth = true)
    {
        $this->pdo = $pdo;

        if ($requireAuth) {
            $this->authenticate();
        }
    }

    /**
     * Authenticate the user via JWT token
     *
     * @throws \Exception If authentication fails
     */
    private function authenticate(): void
    {
        // Load auth_check.php to validate JWT
        require_once __DIR__ . '/../../../auth_check.php';

        // $decoded_jwt is set by auth_check.php
        global $decoded_jwt;
        $this->decoded_jwt = $decoded_jwt;

        // Extract user context
        $this->userId = $decoded_jwt->userId ?? null;
        $this->userPermissions = $decoded_jwt->permissions ?? [];
        $this->authority = $decoded_jwt->authority ?? null;
        $this->authorityId = $decoded_jwt->authority_id ?? null;

        // Validate required fields
        if ($this->userId === null || !is_int($this->userId) ||
            $this->authority === null || !is_string($this->authority) ||
            $this->authorityId === null) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid token payload."]);
            exit();
        }

        // Load authority helper
        require_once __DIR__ . '/../../../utils/authority_helper.php';

        // Validate authority
        if (!isValidAuthority($this->pdo, $this->authorityId)) {
            http_response_code(403);
            echo json_encode(["error" => "Invalid authority context."]);
            exit();
        }

        // Check feature access
        if (!hasFeatureAccess($this->pdo, $this->authorityId, 'company_websites')) {
            http_response_code(403);
            echo json_encode(["error" => "This authority doesn't have access to company website features."]);
            exit();
        }
    }

    /**
     * Check if user has a specific permission
     *
     * @param string $permission The permission to check
     * @return bool True if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        // Load permission helper
        require_once __DIR__ . '/../../../utils/permission_helper.php';

        // Check if user has all permissions
        if (hasAllPermissions($this->userPermissions)) {
            return true;
        }

        // Check if user has the specific permission
        if (hasPermission($this->userPermissions, $permission)) {
            return true;
        }

        // Check if user has higher-level permissions (WRITE/DELETE implies READ)
        if ($permission === 'READ_COMPANY_WEBSITES') {
            if (hasPermission($this->userPermissions, 'WRITE_COMPANY_WEBSITES') ||
                hasPermission($this->userPermissions, 'DELETE_COMPANY_WEBSITES')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify user has access to a specific website
     *
     * @param int $websiteId Website ID
     * @return bool True if user has access
     */
    public function hasWebsiteAccess(int $websiteId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM kdd_website_config
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$websiteId, $this->authorityId]);
            return $stmt->fetchColumn() !== false;
        } catch (\PDOException $e) {
            error_log("Error checking website access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user ID
     *
     * @return int User ID
     */
    public function getUserId(): int
    {
        return $this->userId ?? 0;
    }

    /**
     * Get authority name
     *
     * @return string Authority name
     */
    public function getAuthority(): string
    {
        return $this->authority ?? '';
    }

    /**
     * Get authority ID
     *
     * @return int Authority ID
     */
    public function getAuthorityId(): int
    {
        return $this->authorityId ?? 0;
    }

    /**
     * Get user permissions
     *
     * @return array User permissions
     */
    public function getUserPermissions(): array
    {
        return $this->userPermissions;
    }
}

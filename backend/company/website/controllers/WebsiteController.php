<?php
/**
 * WebsiteController
 * Handles website configuration CRUD operations
 */

declare(strict_types=1);

namespace CompanyWebsite\Controllers;

use PDO;
use CompanyWebsite\Services\AuthService;

class WebsiteController
{
    private PDO $pdo;
    private AuthService $auth;

    public function __construct(PDO $pdo, AuthService $auth)
    {
        $this->pdo = $pdo;
        $this->auth = $auth;
    }

    
    /**
     * Get all websites for the authority
     */
    public function getAll(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        getAllWebsites($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get website details
     */
    public function getDetails(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        getWebsiteDetails($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get website configuration
     */
    public function getConfig(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        getWebsiteConfig($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Create a new website
     */
    public function create(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        createWebsite($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Save website configuration
     */
    public function saveConfig(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        saveWebsiteConfig($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Save website data
     */
    public function save(): void
    {
        require_once __DIR__ . '/../index.php.backup';
        saveWebsite($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get all public websites (no auth required)
     */
    public static function getPublic(PDO $pdo): void
    {
        require_once __DIR__ . '/../index.php.backup';
        getPublicWebsites($pdo);
    }

}

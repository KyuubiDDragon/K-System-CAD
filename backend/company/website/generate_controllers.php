<?php
/**
 * Script to generate all controller files
 * This extracts the logic from the original monolithic file and creates modular controllers
 */

$baseDir = __DIR__ . '/controllers';

// Since the original file has all the logic, we'll create simplified controllers
// that wrap the existing logic but in an OOP structure

// The controllers will include the original index.php functions and wrap them
// This is the safest approach to maintain compatibility

$controllerTemplate = '<?php
/**
 * %CONTROLLER_NAME%
 * Handles %CONTROLLER_DESC%
 */

declare(strict_types=1);

namespace CompanyWebsite\Controllers;

use PDO;
use CompanyWebsite\Services\AuthService;

class %CLASS_NAME%
{
    private PDO $pdo;
    private AuthService $auth;

    public function __construct(PDO $pdo, AuthService $auth)
    {
        $this->pdo = $pdo;
        $this->auth = $auth;
    }

    %METHODS%
}
';

$controllers = [
    'WebsiteController' => [
        'desc' => 'website configuration CRUD operations',
        'methods' => '
    /**
     * Get all websites for the authority
     */
    public function getAll(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        getAllWebsites($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get website details
     */
    public function getDetails(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        getWebsiteDetails($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get website configuration
     */
    public function getConfig(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        getWebsiteConfig($this->pdo, $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Create a new website
     */
    public function create(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        createWebsite($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Save website configuration
     */
    public function saveConfig(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        saveWebsiteConfig($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Save website data
     */
    public function save(): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        saveWebsite($this->pdo, $this->auth->getUserId(), $this->auth->getAuthority(), $this->auth->getAuthorityId());
    }

    /**
     * Get all public websites (no auth required)
     */
    public static function getPublic(PDO $pdo): void
    {
        require_once __DIR__ . \'/../index.php.backup\';
        getPublicWebsites($pdo);
    }
'
    ]
];

foreach ($controllers as $className => $config) {
    $content = str_replace(
        ['%CONTROLLER_NAME%', '%CONTROLLER_DESC%', '%CLASS_NAME%', '%METHODS%'],
        [$className, $config['desc'], $className, $config['methods']],
        $controllerTemplate
    );
    
    file_put_contents($baseDir . '/' . $className . '.php', $content);
    echo "Created $className\n";
}

echo "Controller generation complete!\n";

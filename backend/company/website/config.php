<?php
/**
 * Website Manager Configuration
 * Shared configuration and constants for the website management module
 */

declare(strict_types=1);

namespace CompanyWebsite;

class Config
{
    /**
     * Public actions that don't require authentication
     */
    public const PUBLIC_ACTIONS = [
        'getWebsiteDetails',
        'getNavigation',
        'getPageDetails',
        'submitContactForm',
        'getPages',
        'getPublicWebsites',
        'getCategories',
        'getPosts'
    ];

    /**
     * Permission mappings for actions
     */
    public const PERMISSION_MAP = [
        // Configuration permissions
        'getWebsites'           => 'READ_COMPANY_WEBSITES', // Alias for getAllWebsites
        'getAllWebsites'        => 'READ_COMPANY_WEBSITES',
        'getWebsiteDetails'     => 'READ_COMPANY_WEBSITES',
        'getWebsiteConfig'      => 'READ_COMPANY_WEBSITES',
        'createWebsite'         => 'WRITE_COMPANY_WEBSITES',
        'saveWebsite'           => 'WRITE_COMPANY_WEBSITES',
        'saveConfig'            => 'WRITE_COMPANY_WEBSITES',
        // Page permissions
        'getPages'              => 'READ_COMPANY_WEBSITES',
        'getPageDetails'        => 'READ_COMPANY_WEBSITES',
        'savePage'              => 'WRITE_COMPANY_WEBSITES',
        'deletePage'            => 'DELETE_COMPANY_WEBSITES',
        // Post permissions
        'getPosts'              => 'READ_COMPANY_WEBSITES',
        'savePost'              => 'WRITE_COMPANY_WEBSITES',
        'deletePost'            => 'DELETE_COMPANY_WEBSITES',
        // Category permissions
        'getCategories'         => 'READ_COMPANY_WEBSITES',
        'saveCategory'          => 'WRITE_COMPANY_WEBSITES',
        'deleteCategory'        => 'DELETE_COMPANY_WEBSITES',
        // Media permissions
        'getMedia'              => 'READ_COMPANY_WEBSITES',
        'uploadMedia'           => 'WRITE_COMPANY_WEBSITES',
        'uploadMediaBulk'       => 'WRITE_COMPANY_WEBSITES',
        'updateMedia'           => 'WRITE_COMPANY_WEBSITES',
        'deleteMedia'           => 'DELETE_COMPANY_WEBSITES',
        // Contact permissions
        'getContactSubmissions' => 'READ_COMPANY_WEBSITES',
        'markSubmissionAsRead'  => 'WRITE_COMPANY_WEBSITES',
        'deleteContact'         => 'DELETE_COMPANY_WEBSITES',
        // Navigation permissions
        'getNavigation'         => 'READ_COMPANY_WEBSITES',
        'saveNavigationItem'    => 'WRITE_COMPANY_WEBSITES',
        'createNavigationItem'  => 'WRITE_COMPANY_WEBSITES',
        'updateNavigationItem'  => 'WRITE_COMPANY_WEBSITES',
        'deleteNavigationItem'  => 'DELETE_COMPANY_WEBSITES',
        'updateNavigationOrder' => 'WRITE_COMPANY_WEBSITES',
        'updatePostOrder'       => 'WRITE_COMPANY_WEBSITES',
        'updateCategoryOrder'   => 'WRITE_COMPANY_WEBSITES',
        'getPublicWebsites'     => 'READ_COMPANY_WEBSITES'
    ];

    /**
     * Media upload configuration
     */
    public const MEDIA_UPLOAD_DIR = __DIR__ . '/../../../uploads/website_media';
    public const MEDIA_MAX_SIZE = 10 * 1024 * 1024; // 10MB
    public const ALLOWED_MEDIA_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'video/mp4'
    ];

    /**
     * Get required permission for an action
     *
     * @param string $action The action name
     * @return string|null The required permission or null if not found
     */
    public static function getRequiredPermission(string $action): ?string
    {
        return self::PERMISSION_MAP[$action] ?? null;
    }

    /**
     * Check if action is public (no auth required)
     *
     * @param string $action The action name
     * @return bool True if action is public
     */
    public static function isPublicAction(string $action): bool
    {
        return in_array($action, self::PUBLIC_ACTIONS);
    }
}

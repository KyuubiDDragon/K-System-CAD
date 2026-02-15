<?php

/**
 * CacheService Usage Examples and Integration Guide
 *
 * This file demonstrates various ways to use the CacheService
 * for the Website Manager module.
 */

require_once __DIR__ . '/CacheService.php';

// ============================================================================
// BASIC USAGE EXAMPLES
// ============================================================================

/**
 * Example 1: Basic cache operations
 */
function example_basic_operations()
{
    $cache = new CacheService();

    // Store a value
    $cache->set('user_123', ['name' => 'John Doe', 'email' => 'john@example.com'], 3600);

    // Retrieve a value
    $user = $cache->get('user_123');

    // Check if exists
    if ($cache->has('user_123')) {
        echo "User cache exists\n";
    }

    // Delete a value
    $cache->delete('user_123');
}

/**
 * Example 2: Using the helper function
 */
function example_helper_function()
{
    // Simple usage with callback
    $data = cached('expensive_query', function () {
        // Simulate expensive database query
        sleep(2);
        return ['result' => 'data from database'];
    }, 3600);

    // Subsequent calls will use cached data
    $data = cached('expensive_query', function () {
        // This won't be executed if cache is valid
        return ['result' => 'data from database'];
    }, 3600);
}

// ============================================================================
// WEBSITE MANAGER INTEGRATION EXAMPLES
// ============================================================================

/**
 * Example 3: Cache website configuration
 */
function getWebsiteConfig($websiteId, $authorityId)
{
    $cache = new CacheService();
    $cacheKey = "website_{$websiteId}_config";

    return cached($cacheKey, function () use ($websiteId, $authorityId) {
        // Database query to fetch website config
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT * FROM company_websites WHERE id = :id AND authority_id = :authority_id"
        );
        $stmt->execute(['id' => $websiteId, 'authority_id' => $authorityId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, 3600); // Cache for 1 hour
}

/**
 * Example 4: Cache all pages for a website
 */
function getWebsitePages($websiteId, $authorityId)
{
    $cacheKey = "website_{$websiteId}_pages";

    return cached($cacheKey, function () use ($websiteId, $authorityId) {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT * FROM company_website_pages
             WHERE website_id = :website_id
             AND authority_id = :authority_id
             ORDER BY sort_order ASC"
        );
        $stmt->execute([
            'website_id' => $websiteId,
            'authority_id' => $authorityId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, 1800); // Cache for 30 minutes
}

/**
 * Example 5: Cache individual page
 */
function getPage($pageId, $authorityId)
{
    $cacheKey = "page_{$pageId}";

    return cached($cacheKey, function () use ($pageId, $authorityId) {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT * FROM company_website_pages
             WHERE id = :id AND authority_id = :authority_id"
        );
        $stmt->execute(['id' => $pageId, 'authority_id' => $authorityId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, 3600);
}

/**
 * Example 6: Cache blog posts
 */
function getWebsitePosts($websiteId, $authorityId, $limit = 10)
{
    $cacheKey = "website_{$websiteId}_posts_limit_{$limit}";

    return cached($cacheKey, function () use ($websiteId, $authorityId, $limit) {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT * FROM company_website_posts
             WHERE website_id = :website_id
             AND authority_id = :authority_id
             AND status = 'published'
             ORDER BY published_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':website_id', $websiteId, PDO::PARAM_INT);
        $stmt->bindValue(':authority_id', $authorityId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, 600); // Cache for 10 minutes (shorter for blog content)
}

/**
 * Example 7: Cache navigation menu
 */
function getWebsiteNavigation($websiteId, $authorityId)
{
    $cacheKey = "website_{$websiteId}_navigation";

    return cached($cacheKey, function () use ($websiteId, $authorityId) {
        global $pdo;
        $stmt = $pdo->prepare(
            "SELECT * FROM company_website_navigation
             WHERE website_id = :website_id
             AND authority_id = :authority_id
             ORDER BY parent_id, sort_order ASC"
        );
        $stmt->execute([
            'website_id' => $websiteId,
            'authority_id' => $authorityId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, 3600);
}

// ============================================================================
// CACHE INVALIDATION EXAMPLES
// ============================================================================

/**
 * Example 8: Invalidate cache when updating website
 */
function updateWebsite($websiteId, $data, $authorityId)
{
    global $pdo;
    $cache = new CacheService();

    // Update database
    $stmt = $pdo->prepare(
        "UPDATE company_websites
         SET name = :name, domain = :domain, updated_at = NOW()
         WHERE id = :id AND authority_id = :authority_id"
    );
    $stmt->execute([
        'name' => $data['name'],
        'domain' => $data['domain'],
        'id' => $websiteId,
        'authority_id' => $authorityId
    ]);

    // Invalidate related caches
    $cache->delete("website_{$websiteId}_config");
    $cache->clear("website_{$websiteId}_*"); // Clear all website-related caches

    return true;
}

/**
 * Example 9: Invalidate cache when creating/updating page
 */
function savePage($pageId, $websiteId, $data, $authorityId)
{
    global $pdo;
    $cache = new CacheService();

    // Save to database (simplified)
    // ... database operations ...

    // Invalidate caches
    $cache->delete("page_{$pageId}");
    $cache->delete("website_{$websiteId}_pages");
    $cache->delete("website_{$websiteId}_navigation"); // If page affects navigation

    return true;
}

/**
 * Example 10: Invalidate cache when deleting
 */
function deletePage($pageId, $websiteId, $authorityId)
{
    global $pdo;
    $cache = new CacheService();

    // Delete from database
    $stmt = $pdo->prepare(
        "DELETE FROM company_website_pages
         WHERE id = :id AND authority_id = :authority_id"
    );
    $stmt->execute(['id' => $pageId, 'authority_id' => $authorityId]);

    // Invalidate caches
    $cache->delete("page_{$pageId}");
    $cache->delete("website_{$websiteId}_pages");

    return true;
}

// ============================================================================
// ADVANCED USAGE EXAMPLES
// ============================================================================

/**
 * Example 11: Cache with custom TTL based on content type
 */
function getCachedContent($type, $id, $authorityId)
{
    $ttlMap = [
        'static' => 86400,  // 24 hours for static content
        'dynamic' => 300,   // 5 minutes for dynamic content
        'realtime' => 60    // 1 minute for real-time data
    ];

    $ttl = $ttlMap[$type] ?? 3600;
    $cacheKey = "{$type}_{$id}";

    return cached($cacheKey, function () use ($id, $authorityId) {
        // Fetch from database
        return ['data' => 'content'];
    }, $ttl);
}

/**
 * Example 12: Warm up cache (pre-populate)
 */
function warmUpWebsiteCache($websiteId, $authorityId)
{
    $cache = new CacheService();

    // Pre-load commonly accessed data
    getWebsiteConfig($websiteId, $authorityId);
    getWebsitePages($websiteId, $authorityId);
    getWebsiteNavigation($websiteId, $authorityId);
    getWebsitePosts($websiteId, $authorityId, 10);

    return true;
}

/**
 * Example 13: Cache statistics and monitoring
 */
function getCacheHealth()
{
    $cache = new CacheService();
    $stats = $cache->getStats();

    return [
        'total_entries' => $stats['total_entries'],
        'total_size_mb' => round($stats['total_size'] / 1024 / 1024, 2),
        'expired_entries' => $stats['expired_entries'],
        'health' => $stats['expired_entries'] > 100 ? 'warning' : 'good'
    ];
}

/**
 * Example 14: Scheduled cache cleanup (can be called via cron)
 */
function cleanupCache()
{
    $cache = new CacheService();
    $cleaned = $cache->cleanExpired();

    error_log("Cache cleanup: Removed {$cleaned} expired entries");
    return $cleaned;
}

/**
 * Example 15: Debug mode for development
 */
function debugCacheOperations()
{
    $cache = new CacheService();
    $cache->setDebug(true); // Enable logging

    $cache->set('debug_test', ['data' => 'test'], 60);
    $value = $cache->get('debug_test');
    $cache->delete('debug_test');

    // Check error log for debug messages
}

// ============================================================================
// CONTROLLER INTEGRATION EXAMPLE
// ============================================================================

/**
 * Example 16: Full controller method with caching
 */
function handleGetWebsiteRequest()
{
    // Get request parameters
    $websiteId = $_GET['id'] ?? null;
    $authorityId = $_SESSION['authority_id'] ?? null;

    if (!$websiteId || !$authorityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        return;
    }

    try {
        $cache = new CacheService();

        // Try cache first
        $website = $cache->get("website_{$websiteId}_config");

        if ($website === null) {
            // Cache miss - fetch from database
            global $pdo;
            $stmt = $pdo->prepare(
                "SELECT w.*,
                        (SELECT COUNT(*) FROM company_website_pages WHERE website_id = w.id) as page_count,
                        (SELECT COUNT(*) FROM company_website_posts WHERE website_id = w.id) as post_count
                 FROM company_websites w
                 WHERE w.id = :id AND w.authority_id = :authority_id"
            );
            $stmt->execute([
                'id' => $websiteId,
                'authority_id' => $authorityId
            ]);
            $website = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($website) {
                // Cache for 1 hour
                $cache->set("website_{$websiteId}_config", $website, 3600);
            }
        }

        if (!$website) {
            http_response_code(404);
            echo json_encode(['error' => 'Website not found']);
            return;
        }

        // Return cached or fresh data
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $website,
            'cached' => $cache->has("website_{$websiteId}_config")
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        error_log("Error fetching website: " . $e->getMessage());
    }
}

// ============================================================================
// BATCH OPERATIONS
// ============================================================================

/**
 * Example 17: Clear all website caches
 */
function clearAllWebsiteCaches()
{
    $cache = new CacheService();
    $cleared = $cache->clear('website_*');
    return ['cleared' => $cleared];
}

/**
 * Example 18: Clear specific website caches
 */
function clearWebsiteCache($websiteId)
{
    $cache = new CacheService();
    $patterns = [
        "website_{$websiteId}_config",
        "website_{$websiteId}_pages",
        "website_{$websiteId}_posts*",
        "website_{$websiteId}_navigation"
    ];

    $cleared = 0;
    foreach ($patterns as $pattern) {
        $cleared += $cache->clear($pattern);
    }

    return ['cleared' => $cleared];
}

/**
 * Example 19: Multi-level cache strategy
 */
function getPageWithFallback($pageId, $websiteId, $authorityId)
{
    $cache = new CacheService();

    // Level 1: Try page cache
    $cacheKey = "page_{$pageId}";
    $page = $cache->get($cacheKey);

    if ($page !== null) {
        return $page;
    }

    // Level 2: Fetch from database
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT * FROM company_website_pages
         WHERE id = :id AND authority_id = :authority_id"
    );
    $stmt->execute(['id' => $pageId, 'authority_id' => $authorityId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($page) {
        // Store in cache
        $cache->set($cacheKey, $page, 3600);
        return $page;
    }

    // Level 3: Return default/empty page
    return null;
}

/**
 * Example 20: Cache with version/tag support
 */
function getCachedWithVersion($key, $version, $callback, $ttl = 3600)
{
    $versionedKey = "{$key}_v{$version}";
    return cached($versionedKey, $callback, $ttl);
}

// Usage:
// $data = getCachedWithVersion('website_config', '2.0', function() { ... }, 3600);
// When you need to invalidate all v2.0 caches, increment version to 2.1

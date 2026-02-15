<?php

/**
 * CacheService - File-based caching system for Website Manager
 *
 * Provides a simple, thread-safe caching mechanism without external dependencies.
 * Cache files are stored in the filesystem with automatic expiration.
 *
 * @package K-Systems
 * @subpackage Website
 */
class CacheService
{
    /**
     * @var string Directory where cache files are stored
     */
    private $cacheDir;

    /**
     * @var int Default time-to-live in seconds (1 hour)
     */
    private $defaultTTL = 3600;

    /**
     * @var bool Enable debug logging
     */
    private $debug = false;

    /**
     * Constructor
     *
     * @param string|null $cacheDir Custom cache directory path (optional)
     * @param int|null $defaultTTL Custom default TTL in seconds (optional)
     */
    public function __construct($cacheDir = null, $defaultTTL = null)
    {
        // Set cache directory - default to backend/cache/website/
        $this->cacheDir = $cacheDir ?? dirname(__DIR__, 3) . '/cache/website/';

        // Set default TTL if provided
        if ($defaultTTL !== null) {
            $this->defaultTTL = $defaultTTL;
        }

        // Create cache directory if it doesn't exist
        $this->ensureCacheDirectoryExists();
    }

    /**
     * Retrieve a value from cache
     *
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get($key)
    {
        $cacheFile = $this->getCacheFile($key);

        // Check if cache file exists
        if (!file_exists($cacheFile)) {
            $this->log("Cache miss: {$key} (file not found)");
            return null;
        }

        // Open file with shared lock for reading
        $handle = fopen($cacheFile, 'r');
        if (!$handle) {
            $this->log("Cache error: Cannot open file for key {$key}");
            return null;
        }

        // Acquire shared lock
        if (!flock($handle, LOCK_SH)) {
            fclose($handle);
            $this->log("Cache error: Cannot acquire lock for key {$key}");
            return null;
        }

        try {
            // Read cache metadata and data
            $contents = fread($handle, filesize($cacheFile));
            $data = json_decode($contents, true);

            // Unlock and close
            flock($handle, LOCK_UN);
            fclose($handle);

            // Validate cache structure
            if (!isset($data['expiry']) || !isset($data['value'])) {
                $this->log("Cache error: Invalid cache structure for key {$key}");
                $this->delete($key);
                return null;
            }

            // Check if expired
            if ($this->isExpired($cacheFile, $data['expiry'])) {
                $this->log("Cache expired: {$key}");
                $this->delete($key);
                return null;
            }

            $this->log("Cache hit: {$key}");
            return $data['value'];
        } catch (Exception $e) {
            flock($handle, LOCK_UN);
            fclose($handle);
            $this->log("Cache error: Exception reading key {$key} - {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Store a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache (will be JSON encoded)
     * @param int|null $ttl Time-to-live in seconds (null uses default)
     * @return bool Success status
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheFile = $this->getCacheFile($key);
        $ttl = $ttl ?? $this->defaultTTL;
        $expiry = time() + $ttl;

        // Prepare cache data
        $cacheData = [
            'key' => $key,
            'value' => $value,
            'expiry' => $expiry,
            'created_at' => time(),
            'ttl' => $ttl
        ];

        $jsonData = json_encode($cacheData, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            $this->log("Cache error: Cannot encode data for key {$key}");
            return false;
        }

        // Ensure cache directory exists
        $this->ensureCacheDirectoryExists();

        // Write to temporary file first (atomic operation)
        $tempFile = $cacheFile . '.tmp.' . uniqid();

        // Open temporary file with exclusive lock
        $handle = fopen($tempFile, 'w');
        if (!$handle) {
            $this->log("Cache error: Cannot create temp file for key {$key}");
            return false;
        }

        // Acquire exclusive lock
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            @unlink($tempFile);
            $this->log("Cache error: Cannot acquire lock for key {$key}");
            return false;
        }

        try {
            // Write data
            fwrite($handle, $jsonData);
            fflush($handle);

            // Unlock and close
            flock($handle, LOCK_UN);
            fclose($handle);

            // Atomic rename
            if (!rename($tempFile, $cacheFile)) {
                @unlink($tempFile);
                $this->log("Cache error: Cannot rename temp file for key {$key}");
                return false;
            }

            $this->log("Cache set: {$key} (TTL: {$ttl}s)");
            return true;
        } catch (Exception $e) {
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($tempFile);
            $this->log("Cache error: Exception writing key {$key} - {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Delete a specific cache entry
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete($key)
    {
        $cacheFile = $this->getCacheFile($key);

        if (file_exists($cacheFile)) {
            $success = @unlink($cacheFile);
            $this->log("Cache delete: {$key} " . ($success ? 'success' : 'failed'));
            return $success;
        }

        return true; // Already deleted
    }

    /**
     * Clear cache entries matching a pattern
     *
     * @param string $pattern Wildcard pattern (e.g., 'website_*', '*_config')
     * @return int Number of entries cleared
     */
    public function clear($pattern = '*')
    {
        $cleared = 0;
        $files = glob($this->cacheDir . '*.cache');

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $filename = basename($file, '.cache');

            // Convert pattern to regex
            // First replace * with placeholder, then quote, then replace placeholder with .*
            $regex_pattern = str_replace('*', '__WILDCARD__', $pattern);
            $regex_pattern = preg_quote($regex_pattern, '/');
            $regex_pattern = str_replace('__WILDCARD__', '.*', $regex_pattern);
            $regex = '/^' . $regex_pattern . '$/';

            if (preg_match($regex, $filename)) {
                if (@unlink($file)) {
                    $cleared++;
                    $this->log("Cache cleared: {$filename}");
                }
            }
        }

        $this->log("Cache clear: {$cleared} entries removed (pattern: {$pattern})");
        return $cleared;
    }

    /**
     * Check if a cache entry exists and is valid
     *
     * @param string $key Cache key
     * @return bool True if exists and not expired
     */
    public function has($key)
    {
        $cacheFile = $this->getCacheFile($key);

        if (!file_exists($cacheFile)) {
            return false;
        }

        // Check if expired by attempting to read
        $value = $this->get($key);
        return $value !== null;
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics about cache usage
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '*.cache');
        $stats = [
            'total_entries' => 0,
            'total_size' => 0,
            'expired_entries' => 0,
            'oldest_entry' => null,
            'newest_entry' => null
        ];

        if ($files === false || empty($files)) {
            return $stats;
        }

        $stats['total_entries'] = count($files);

        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            $mtime = filemtime($file);

            if ($stats['oldest_entry'] === null || $mtime < $stats['oldest_entry']) {
                $stats['oldest_entry'] = $mtime;
            }

            if ($stats['newest_entry'] === null || $mtime > $stats['newest_entry']) {
                $stats['newest_entry'] = $mtime;
            }

            // Check if expired
            $contents = file_get_contents($file);
            $data = json_decode($contents, true);
            if (isset($data['expiry']) && time() > $data['expiry']) {
                $stats['expired_entries']++;
            }
        }

        return $stats;
    }

    /**
     * Clean up expired cache entries
     *
     * @return int Number of entries cleaned
     */
    public function cleanExpired()
    {
        $cleaned = 0;
        $files = glob($this->cacheDir . '*.cache');

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $contents = @file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            $data = json_decode($contents, true);
            if (isset($data['expiry']) && time() > $data['expiry']) {
                if (@unlink($file)) {
                    $cleaned++;
                    $this->log("Cache cleaned: " . basename($file, '.cache'));
                }
            }
        }

        $this->log("Cache cleanup: {$cleaned} expired entries removed");
        return $cleaned;
    }

    /**
     * Enable or disable debug logging
     *
     * @param bool $enabled
     */
    public function setDebug($enabled)
    {
        $this->debug = $enabled;
    }

    /**
     * Get the full path to a cache file
     *
     * @param string $key Cache key
     * @return string Full file path
     */
    private function getCacheFile($key)
    {
        // Sanitize key to prevent directory traversal
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return $this->cacheDir . $safeKey . '.cache';
    }

    /**
     * Check if a cache entry is expired
     *
     * @param string $file Cache file path
     * @param int $expiry Expiry timestamp
     * @return bool True if expired
     */
    private function isExpired($file, $expiry)
    {
        return time() > $expiry;
    }

    /**
     * Ensure cache directory exists with proper permissions
     */
    private function ensureCacheDirectoryExists()
    {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new Exception("Cannot create cache directory: {$this->cacheDir}");
            }
            $this->log("Cache directory created: {$this->cacheDir}");
        }

        // Check if writable
        if (!is_writable($this->cacheDir)) {
            throw new Exception("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    /**
     * Log debug messages
     *
     * @param string $message
     */
    private function log($message)
    {
        if ($this->debug) {
            error_log("[CacheService] " . $message);
        }
    }
}

/**
 * Helper function for simplified caching with callback
 *
 * Usage:
 *   $data = cached('my_key', function() {
 *       return expensive_operation();
 *   }, 3600);
 *
 * @param string $key Cache key
 * @param callable $callback Function to call if cache miss
 * @param int $ttl Time-to-live in seconds
 * @param CacheService|null $cache Custom cache instance (optional)
 * @return mixed Cached or computed value
 */
function cached($key, callable $callback, $ttl = 3600, $cache = null)
{
    // Use provided cache instance or create new one
    if ($cache === null) {
        static $defaultCache = null;
        if ($defaultCache === null) {
            $defaultCache = new CacheService();
        }
        $cache = $defaultCache;
    }

    // Try to get from cache
    $value = $cache->get($key);

    // If cache miss, execute callback and cache result
    if ($value === null) {
        $value = $callback();
        $cache->set($key, $value, $ttl);
    }

    return $value;
}

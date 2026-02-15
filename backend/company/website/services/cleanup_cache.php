#!/usr/bin/env php
<?php
/**
 * Cache Cleanup Script for Website Manager
 *
 * This script removes expired cache entries to prevent disk space issues
 * and maintain cache performance.
 *
 * Usage:
 *   php cleanup_cache.php
 *
 * Cron setup (run every hour):
 *   0 * * * * php /path/to/backend/company/website/services/cleanup_cache.php
 *
 * Or run every 6 hours:
 *   0 *\/6 * * * php /path/to/backend/company/website/services/cleanup_cache.php
 */

// Ensure script is run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Load CacheService
require_once __DIR__ . '/CacheService.php';

// Configuration
$config = [
    'verbose' => false, // Set to true for detailed output
    'max_size_mb' => 500, // Alert if cache exceeds this size
    'max_entries' => 10000, // Alert if cache exceeds this many entries
];

// Parse command line arguments
$options = getopt('vh', ['verbose', 'help']);
if (isset($options['h']) || isset($options['help'])) {
    echo "Usage: php cleanup_cache.php [OPTIONS]\n";
    echo "Options:\n";
    echo "  -v, --verbose    Show detailed output\n";
    echo "  -h, --help       Show this help message\n";
    exit(0);
}

if (isset($options['v']) || isset($options['verbose'])) {
    $config['verbose'] = true;
}

// Helper function for logging
function log_message($message, $level = 'INFO')
{
    global $config;

    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[{$timestamp}] [{$level}] {$message}";

    // Always log to error_log
    error_log($formatted);

    // Print to console if verbose
    if ($config['verbose']) {
        echo $formatted . "\n";
    }
}

try {
    log_message("Starting cache cleanup process", 'INFO');

    // Initialize cache service
    $cache = new CacheService();

    // Get statistics before cleanup
    log_message("Gathering cache statistics...", 'INFO');
    $statsBefore = $cache->getStats();

    log_message("Cache stats before cleanup:", 'INFO');
    log_message("  - Total entries: {$statsBefore['total_entries']}", 'INFO');
    log_message("  - Total size: " . formatBytes($statsBefore['total_size']), 'INFO');
    log_message("  - Expired entries: {$statsBefore['expired_entries']}", 'INFO');

    if ($statsBefore['oldest_entry']) {
        $oldestDate = date('Y-m-d H:i:s', $statsBefore['oldest_entry']);
        log_message("  - Oldest entry: {$oldestDate}", 'INFO');
    }

    if ($statsBefore['newest_entry']) {
        $newestDate = date('Y-m-d H:i:s', $statsBefore['newest_entry']);
        log_message("  - Newest entry: {$newestDate}", 'INFO');
    }

    // Perform cleanup
    log_message("Cleaning expired cache entries...", 'INFO');
    $cleaned = $cache->cleanExpired();
    log_message("Removed {$cleaned} expired cache entries", 'INFO');

    // Get statistics after cleanup
    $statsAfter = $cache->getStats();

    log_message("Cache stats after cleanup:", 'INFO');
    log_message("  - Total entries: {$statsAfter['total_entries']}", 'INFO');
    log_message("  - Total size: " . formatBytes($statsAfter['total_size']), 'INFO');
    log_message("  - Expired entries: {$statsAfter['expired_entries']}", 'INFO');

    // Calculate savings
    $sizeSaved = $statsBefore['total_size'] - $statsAfter['total_size'];
    $entriesReduced = $statsBefore['total_entries'] - $statsAfter['total_entries'];

    if ($sizeSaved > 0) {
        log_message("Space saved: " . formatBytes($sizeSaved), 'INFO');
    }

    if ($entriesReduced > 0) {
        log_message("Entries reduced: {$entriesReduced}", 'INFO');
    }

    // Check for alerts
    $sizeMB = $statsAfter['total_size'] / 1024 / 1024;
    if ($sizeMB > $config['max_size_mb']) {
        log_message("WARNING: Cache size ({$sizeMB} MB) exceeds threshold ({$config['max_size_mb']} MB)", 'WARNING');
    }

    if ($statsAfter['total_entries'] > $config['max_entries']) {
        log_message("WARNING: Cache entries ({$statsAfter['total_entries']}) exceed threshold ({$config['max_entries']})", 'WARNING');
    }

    if ($statsAfter['expired_entries'] > 50) {
        log_message("WARNING: Still {$statsAfter['expired_entries']} expired entries remaining (possible cleanup issue)", 'WARNING');
    }

    log_message("Cache cleanup completed successfully", 'INFO');

    exit(0);
} catch (Exception $e) {
    log_message("Cache cleanup failed: " . $e->getMessage(), 'ERROR');
    log_message("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

/**
 * Format bytes to human-readable format
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

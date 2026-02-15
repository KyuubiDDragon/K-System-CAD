<?php
// Simple diagnostic script to show PHP and server information

// Set content type to plain text for better readability
header("Content-Type: text/plain");

// Output PHP version
echo "PHP Version: " . phpversion() . "\n\n";

// Basic server information
echo "SERVER INFORMATION:\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Server Address: " . $_SERVER['SERVER_ADDR'] . "\n";
echo "Server Port: " . $_SERVER['SERVER_PORT'] . "\n\n";

// Request information
echo "REQUEST INFORMATION:\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP Self: " . $_SERVER['PHP_SELF'] . "\n";
echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? "none") . "\n\n";

// HTTP headers
echo "HTTP HEADERS:\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}
echo "\n";

// Check if mod_rewrite is enabled
echo "MOD_REWRITE CHECK:\n";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "mod_rewrite enabled: " . (in_array('mod_rewrite', $modules) ? "Yes" : "No") . "\n";
} else {
    echo "Apache modules function not available - likely running as CGI\n";
}
echo "\n";

// Environment variables
echo "ENVIRONMENT VARIABLES:\n";
foreach ($_ENV as $key => $value) {
    echo "$key: $value\n";
}
echo "\n";

// Load configuration
echo "LOADED PHP CONFIGURATION FILES:\n";
echo php_ini_loaded_file() . "\n";
echo "Additional .ini files: " . php_ini_scanned_files() . "\n\n";

// Check if file exists
echo "FILE CHECKS:\n";
echo "This file exists: " . __FILE__ . "\n";
echo "/var/www/html/api/health.php exists: " . (file_exists("/var/www/html/api/health.php") ? "Yes" : "No") . "\n";
echo "/var/www/html/health.php exists: " . (file_exists("/var/www/html/health.php") ? "Yes" : "No") . "\n";
echo "Current directory contents:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    echo "- $file\n";
} 
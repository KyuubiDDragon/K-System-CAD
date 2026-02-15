<?php
// Simple API test script

header("Content-Type: application/json");

// Create a response with useful information
$response = [
    'status' => 'success',
    'message' => 'API test endpoint reached',
    'timestamp' => time(),
    'request_info' => [
        'uri' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'script' => $_SERVER['SCRIPT_NAME']
    ],
    'server_info' => [
        'software' => $_SERVER['SERVER_SOFTWARE'],
        'document_root' => $_SERVER['DOCUMENT_ROOT']
    ],
    'file_info' => [
        'this_file' => __FILE__,
        'current_dir' => __DIR__,
        'exists_in_root' => file_exists('/var/www/html/api-test.php') ? true : false
    ]
];

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT); 
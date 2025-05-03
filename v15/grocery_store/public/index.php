<?php
/**
 * Entry Point for Grocery Store Web Application
 * 
 * Initializes application configuration, 
 * session management, and routing.
 */

// Prevent direct file access
if (php_sapi_name() === 'cli-server') {
    // Allow PHP built-in server to serve static files
    $filename = __DIR__ . preg_replace('#(\?.*)?$#', '', $_SERVER['REQUEST_URI']);
    if (is_file($filename)) {
        return false;
    }
}

// Security: Prevent potential information disclosure
header_remove('X-Powered-By');

// Start output buffering for potential redirects
ob_start();

// Include core configuration
require_once __DIR__ . '/../config/config.php';

// Initialize error handling
require_once __DIR__ . '/../config/errorlogger.php';

try {
    // Use router for request handling
    require_once __DIR__ . '/router.php';
} catch (Throwable $e) {
    // Catch any fatal errors
    ErrorLogger::logException($e);
    
    // Display user-friendly error page
    http_response_code(500);
    echo "An unexpected error occurred. Our team has been notified.";
    exit;
} finally {
    // Ensure output is sent
    ob_end_flush();
}
<?php
/**
 * config.php - Main Application Configuration
 *
 * This file provides global configuration settings, initializes session management,
 * sets up error handling, and defines utility functions used throughout the application.
 * It serves as the central configuration point for the Grocery Store Web Application.
 *
 * @category   Config
 * @package    GroceryStore
 */

// Enable strict error reporting in development, disable in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure error logging is initialized
require_once __DIR__ . '/errorlogger.php';
ErrorLogger::init();

// Set global error and exception handlers
set_error_handler([ErrorLogger::class, 'handleError']);
set_exception_handler(function($exception) {
    ErrorLogger::logException($exception);
    
    // In development, show error details
    if (ini_get('display_errors')) {
        echo "An error occurred. Please check the logs for details.";
        // Optionally, add detailed error display for development
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<pre>";
            print_r($exception);
            echo "</pre>";
        }
    }
});

/**
 * Application Constants
 * 
 * Define application-wide constants for paths, URLs, and settings
 */
define('APP_NAME', 'Grocery Store');
define('APP_URL', 'http://localhost:8000');
define('UPLOADS_DIR', __DIR__ . '/../public/images/products/');
define('CAPTCHA_DIR', __DIR__ . '/../public/images/captcha/');

// Initialize session with security settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1); // Force sessions to only use cookies
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1); // Only transmit cookie over HTTPS
    }
    
    session_start();
}

/**
 * Utility Functions 
 */

/**
 * Redirect user to specified URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return boolean True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Initialize cart count if user is logged in but cart_count isn't set
if (isset($_SESSION['user_id']) && !isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

/**
 * CSRF Protection Functions
 */

/**
 * Generate CSRF token for form protection
 * 
 * @return string Generated or existing CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from form submission
 * 
 * @param string $token CSRF token to verify
 * @return boolean True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        // Log potential CSRF attack attempt
        ErrorLogger::log("CSRF token verification failed", "SECURITY");
        return false;
    }
    return true;
}

/**
 * Set flash message for one-time display
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, info, warning, danger)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}
<?php
/**
 * Logout Process for Grocery Store Web Application
 * 
 * Handles secure user session termination and redirection.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Comprehensive session destruction
try {
    // Clear all session variables
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"] ?? false, $params["httponly"] ?? false
        );
    }

    // Destroy the session
    session_destroy();

    // Log logout event
    error_log("User logged out successfully at " . date('Y-m-d H:i:s'));

    // Start a new session for flash messaging
    session_start();

    // Set logout success message
    $_SESSION['flash_message'] = "You have been logged out successfully.";
    $_SESSION['flash_type'] = "success";

    // Redirect to home page
    header("Location: /");
    exit;

} catch (Exception $e) {
    // Log any unexpected errors during logout
    error_log("Logout Error: " . $e->getMessage());

    // Fallback redirect with error message
    $_SESSION['flash_message'] = "An error occurred during logout. Please try again.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /");
    exit;
}
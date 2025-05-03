<?php
/**
 * users.php - User Management API Endpoint
 *
 * Processes user-related operations including registration, login,
 * and profile management. Provides JSON responses for AJAX requests.
 */

header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/UserController.php';

// Initialize user controller
$userController = new UserController();

// Set up error handling for debugging
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

try {
    // Check the request method and action
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            // Handle the action
            switch ($_POST['action']) {
                case 'register':
                    $userController->register();
                    break;
                    
                case 'login':
                    $userController->login();
                    break;
                    
                case 'update_profile':
                    $userController->updateProfile();
                    break;
                    
                default:
                    // Invalid action
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid action'
                    ]);
                    break;
            }
        } else {
            // Missing action parameter
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing action parameter'
            ]);
        }
    } else {
        // Method not allowed
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
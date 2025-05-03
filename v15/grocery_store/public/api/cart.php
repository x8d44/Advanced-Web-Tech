<?php
/**
 * cart.php - Shopping Cart API Endpoint
 *
 * Processes cart operations including adding, updating, removing items,
 * and checkout. Provides JSON responses for AJAX requests.
 */

header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/CartController.php';

// Enable detailed error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the request for debugging
error_log("Cart API request received: " . $_SERVER['REQUEST_METHOD']);
error_log("Request body: " . file_get_contents('php://input'));
error_log("GET parameters: " . print_r($_GET, true));

// Get action from GET parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';

// If action is not in GET, try to get it from POST or request body
if (empty($action)) {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    } else {
        // Try to parse action from request body for JSON requests
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action'])) {
            $action = $input['action'];
        }
    }
}

error_log("Cart action determined: " . $action);

// Initialize controller
$cartController = new CartController();

// If we have an action, set it in $_GET so the controller can access it
if (!empty($action)) {
    $_GET['action'] = $action;
}

// Handle API request - ensuring we have proper output at the end
try {
    // We'll catch any redirects and convert them to JSON responses
    ob_start();
    $cartController->handleApiRequest();
    $output = ob_get_clean();
    
    // If there's content and it's not already JSON, wrap it
    if (!empty($output) && !preg_match('/^{.*}$|^\[.*\]$/s', trim($output))) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid response from server',
            'data' => $output
        ]);
    } else if (!empty($output)) {
        echo $output;
    } else {
        // Empty output usually means a redirect was attempted
        echo json_encode([
            'success' => false,
            'message' => 'No response from server'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
<?php
/**
 * Router for Grocery Store Web Application
 * 
 * Manages URL routing and request handling 
 * for different application endpoints.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Parse request URI
$request = $_SERVER['REQUEST_URI'];
$basepath = dirname($_SERVER['SCRIPT_NAME']);

// Remove base path from request
$path = substr($request, strlen($basepath));

// Extract the path part (without query string)
$urlParts = parse_url($path);
$path = isset($urlParts['path']) ? $urlParts['path'] : '';

// Clean up the path
$path = trim($path, '/');  // Remove leading/trailing slashes
$path = preg_replace('/\.php$/', '', $path);  // Remove .php extension if present

// Special handling for login processing
if ($path === 'login-process' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../controllers/UserController.php';
    $controller = new UserController();
    $controller->login();
    return;
}

// =====================================================
// TESTING SECTION - For development and testing purposes only
// =====================================================
// Check if the request is for a test file (either with or without .php extension)
$test_file = $path;
if (strlen($test_file) < 4 || substr($test_file, -4) !== '.php') {
    $test_file .= '.php';
}

// Security check - limit to local environments
$allowed_test_environments = ['localhost', '127.0.0.1', '[::1]'];
if (in_array($_SERVER['SERVER_NAME'], $allowed_test_environments) || 
    in_array($_SERVER['REMOTE_ADDR'], $allowed_test_environments)) {
    
    // Define the project root directory
    $project_root = realpath(__DIR__ . '/../..');
    $grocery_store_dir = $project_root . '/grocery_store';
    
    // Define the correct tests directory path (confirmed working path)
    $tests_dir = $grocery_store_dir . '/tests/';
    $test_file_path = $tests_dir . $test_file;
    
    // Check if the requested file exists in the tests directory
    if (file_exists($test_file_path)) {
        // Include and run the test file
        include_once $test_file_path;
        return;
    }
    
    // Special case for /tests or /test endpoint to list available tests
    if ($path === 'tests' || $path === 'test') {
        if (is_dir($tests_dir)) {
            echo "<h1>Available Test Files</h1>";
            echo "<ul>";
            foreach (scandir($tests_dir) as $file) {
                if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    echo "<li><a href='/{$file}'>{$file}</a></li>";
                }
            }
            echo "</ul>";
            return;
        }
    }
}

// Handle API requests
if (strpos($path, 'api/') === 0) {
    $apiPath = substr($path, 4); // Remove 'api/' from the start
    
    try {
        switch ($apiPath) {
            case 'users.php':
            case 'users':
                require __DIR__ . '/api/users.php';
                return;
            
            case 'products.php':
            case 'products':
                require __DIR__ . '/api/products.php';
                return;
            
            case 'orders.php':
            case 'orders':
                require __DIR__ . '/api/orders.php';
                return;
            
            case 'captcha.php':
            case 'captcha':
                require __DIR__ . '/api/captcha.php';
                return;
            
            case 'cart.php':
            case 'cart':
                require __DIR__ . '/api/cart.php';
                return;
            
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
                return;
        }
    } catch (Exception $e) {
        // Log routing errors
        error_log("API Routing Error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage()
        ]);
        return;
    }
}

// Route the request to appropriate views
try {
    switch ($path) {
        case '':
        case 'home':
            require __DIR__ . '/../views/home.php';
            break;
        
        case 'products':
            require __DIR__ . '/../views/products.php';
            break;
        
        case 'login':
            require __DIR__ . '/../views/login.php';
            break;
        
        case 'register':
            require __DIR__ . '/../views/register.php';
            break;
        
        case 'cart':  
            require __DIR__ . '/../views/cart.php';
            break;
        
        case 'order_confirmation':
            require __DIR__ . '/../views/order_confirmation.php';
            break;
        
        case 'customer/dashboard':
            require __DIR__ . '/../views/customer/dashboard.php';
            break;
        
        case 'customer/edit_profile':
            require __DIR__ . '/../views/customer/edit_profile.php';
            break;
        
        case 'logout':
            require __DIR__ . '/../views/logout.php';
            break;
        
        default:
            http_response_code(404);
            echo "Page not found: " . htmlspecialchars($path);
            break;
    }
} catch (Exception $e) {
    // Log routing errors
    error_log("View Routing Error: " . $e->getMessage());
    
    http_response_code(500);
    echo "An error occurred while processing your request.";
}
<?php
/**
 * orders.php - Order Management API Endpoint
 *
 * Processes order operations including creation, retrieval, and status updates.
 * Provides RESTful endpoints for managing the ordering process.
 */

header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/errorlogger.php';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    ErrorLogger::log("Database connection error in orders API: " . $e->getMessage(), "ERROR");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
    exit;
}

// Initialize order object
$order = new Order($db);

// Get HTTP method and route
$method = $_SERVER['REQUEST_METHOD'];

// Get order ID from query parameters
$order_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Process POST request (Buy Now functionality)
if ($method === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to place an order',
            'redirect' => '/login'
        ]);
        exit;
    }
    
    try {
        // Get request data
        $data = json_decode(file_get_contents('php://input'));
        
        // Verify CSRF token
        if (!isset($data->csrf_token) || !verify_csrf_token($data->csrf_token)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token'
            ]);
            exit;
        }
        
        // Validate required fields
        if (!isset($data->product_id) || !isset($data->quantity)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: product_id and quantity are required'
            ]);
            exit;
        }
        
        // Validate quantity
        if (!is_numeric($data->quantity) || $data->quantity < 1) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid quantity. Must be a positive number.'
            ]);
            exit;
        }
        
        // Get user information
        $user = new User($db);
        $userData = $user->getUserById($_SESSION['user_id']);
        
        if (!$userData) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User information not found'
            ]);
            exit;
        }
        
        // Verify product exists
        $product = new Product($db);
        $productData = $product->readOne($data->product_id);
        
        if (!$productData) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
            exit;
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Create order
        $order->user_id = $_SESSION['user_id'];
        $order->customer_name = $userData['name'];
        $order->customer_email = $userData['email'];
        $order->customer_phone = $userData['phone'];
        $order->status = 'pending';
        
        if ($order->create()) {
            // Add item to order_items
            if ($order->addItem($data->product_id, $data->quantity, $productData['price'])) {
                // Commit transaction
                $db->commit();
                
                ErrorLogger::log("Buy Now order created successfully - Order ID: {$order->order_id}", "INFO");
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_id' => $order->order_id
                ]);
            } else {
                // Roll back if adding item fails
                $db->rollBack();
                
                ErrorLogger::log("Failed to add item to Buy Now order", "ERROR");
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add item to order'
                ]);
            }
        } else {
            // Roll back if order creation fails
            $db->rollBack();
            
            ErrorLogger::log("Failed to create Buy Now order", "ERROR");
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create order'
            ]);
        }
    } catch (Exception $e) {
        // Roll back if an exception occurs
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        ErrorLogger::log("Exception during Buy Now order creation: " . $e->getMessage(), "ERROR");
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your order: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Validate manager access for sensitive operations
function validateManagerAccess() {
    // In a real application, you would use a more secure authentication mechanism
    // For this assessment, we'll use a simple token-based approach
    $manager_token = isset($_GET['manager_token']) ? $_GET['manager_token'] : '';
    
    // Validate the token (in production, this would be a secure token verification)
    if ($manager_token !== 'manager123') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden - Manager access required. Please provide a valid manager token.'
        ]);
        return false;
    }
    
    return true;
}

// Log API request for auditing
ErrorLogger::log("Orders API accessed - Method: $method, Order ID: $order_id, User ID: $user_id", "INFO");

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        // If order_id is provided, get specific order (for managers)
        if ($order_id) {
            // Check manager authentication for accessing specific order details
            if (!validateManagerAccess()) {
                exit;
            }
            
            $result = $order->readOne($order_id);
            if ($result) {
                // Format response for manager view
                $orderData = [
                    'order_id' => $result['order_id'],
                    'customer' => [
                        'name' => $result['user_name'] ?? $result['customer_name'],
                        'email' => $result['email'] ?? $result['customer_email'],
                        'phone' => $result['phone'] ?? $result['customer_phone']
                    ],
                    'items' => $result['items'],
                    'total' => $result['total_price'],
                    'formatted_total' => $result['formatted_total'],
                    'status' => $result['status'],
                    'order_date' => $result['order_date']
                ];
                
                ErrorLogger::log("Order details accessed successfully - Order ID: $order_id", "INFO");
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $orderData
                ]);
            } else {
                ErrorLogger::log("Order not found - Order ID: $order_id", "WARNING");
                
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }
        } 
        // If user_id is provided, get orders for that user (customer can see their own orders)
        else if ($user_id) {
            // Check if user is accessing their own orders or if a manager is accessing
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                // User accessing their own orders
                $results = $order->readByUser($user_id);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $results
                ]);
            } else {
                // Not the user's own orders, check if manager
                if (!validateManagerAccess()) {
                    exit;
                }
                
                $results = $order->readByUser($user_id);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $results
                ]);
            }
        } 
        // Get all orders (managers only)
        else {
            if (!validateManagerAccess()) {
                exit;
            }
            
            $results = $order->read();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
        }
        break;
        
    case 'PUT':
        // Update order status (manager only)
        if (!validateManagerAccess()) {
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'));
        
        // Validate required fields
        if (!isset($data->order_id) || !isset($data->status)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: order_id and status are required'
            ]);
            exit;
        }
        
        // Validate status value
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($data->status, $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid status value. Must be one of: ' . implode(', ', $validStatuses)
            ]);
            exit;
        }
        
        // Check if order exists
        $existingOrder = $order->readOne($data->order_id);
        if (!$existingOrder) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
            exit;
        }
        
        // Set order properties for update
        $order->order_id = $data->order_id;
        $order->status = $data->status;
        
        if ($order->updateStatus()) {
            ErrorLogger::log("Order status updated - Order ID: {$data->order_id}, New Status: {$data->status}", "INFO");
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);
        } else {
            ErrorLogger::log("Failed to update order status - Order ID: {$data->order_id}", "ERROR");
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update order status'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
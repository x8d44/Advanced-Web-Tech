<?php
/**
 * OrderController.php - Order Management Controller
 *
 * Handles all order-related operations including order creation,
 * retrieval, and status updates.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';

class OrderController {
    /** @var PDO Database connection */
    private $db;
    
    /** @var Order Order model instance */
    private $order;
    
    /** @var Product Product model instance */
    private $product;
    
    /**
     * Constructor initializes database connection and model instances
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->order = new Order($this->db);
        $this->product = new Product($this->db);
    }
    
    /**
     * Process order submission (Buy Now functionality)
     * 
     * @return void Outputs JSON response or redirects
     */
    public function placeOrder() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->handleNotLoggedIn('You must be logged in to place an order.', '/login');
            return;
        }
        
        try {
            // Get form data
            $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
            $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
            
            // If no POST data, check for JSON data
            if ($product_id === null) {
                $data = json_decode(file_get_contents('php://input'), true);
                $product_id = $data['product_id'] ?? null;
                $quantity = $data['quantity'] ?? 1;
                
                // Verify CSRF token
                if (isset($data['csrf_token']) && !verify_csrf_token($data['csrf_token'])) {
                    throw new Exception('Invalid security token. Please refresh and try again.');
                }
            } else {
                // Verify CSRF token for POST
                if (isset($_POST['csrf_token']) && !verify_csrf_token($_POST['csrf_token'])) {
                    throw new Exception('Invalid security token. Please refresh and try again.');
                }
            }
            
            // Validate product ID
            if (!$product_id) {
                throw new Exception('Invalid product selected.');
            }
            
            // Get product details
            $product = $this->product->readOne($product_id);
            
            if (!$product) {
                throw new Exception('Invalid product selected.');
            }
            
            // Validate quantity
            if (!is_numeric($quantity) || $quantity < 1) {
                throw new Exception('Invalid quantity.');
            }
            
            // Get user information
            require_once __DIR__ . '/../models/User.php';
            $user = new User($this->db);
            $userData = $user->getUserById($_SESSION['user_id']);
            
            if (!$userData) {
                throw new Exception('User information not found.');
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Set order properties
            $this->order->user_id = $_SESSION['user_id'];
            $this->order->status = 'pending';
            
            // Set customer information from user data
            $this->order->customer_name = $userData['name'];
            $this->order->customer_email = $userData['email'];
            $this->order->customer_phone = $userData['phone'];
            
            // Create the order
            if ($this->order->create()) {
                // Now add the item to order_items
                if (!$this->order->addItem($product_id, $quantity, $product['price'])) {
                    // If adding item fails, roll back
                    $this->db->rollBack();
                    throw new Exception('Failed to add product to order.');
                }
                
                // Commit transaction
                $this->db->commit();
                
                // Order successful - handle response
                $this->handleOrderSuccess($product['product_name']);
            } else {
                // If order creation fails, roll back
                $this->db->rollBack();
                throw new Exception('Failed to place order. Please try again.');
            }
        } catch (Exception $e) {
            // Roll back if an error occurs
            if (isset($this->db) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->handleError($e, "Order error");
        }
    }
    
    /**
     * Get all orders
     * 
     * @return array Orders
     */
    public function getAllOrders() {
        return $this->order->read();
    }
    
    /**
     * Get order by ID
     * 
     * @param int $order_id Order ID
     * @return array|boolean Order data or false
     */
    public function getOrderById($order_id) {
        return $this->order->readOne($order_id);
    }
    
    /**
     * Get orders by user ID
     * 
     * @param int $user_id User ID
     * @return array Orders
     */
    public function getOrdersByUser($user_id) {
        return $this->order->readByUser($user_id);
    }
    
    /**
     * Update order status
     * 
     * @param int $order_id Order ID
     * @param string $status New status
     * @return boolean Success or failure
     */
    public function updateOrderStatus($order_id, $status) {
        $this->order->order_id = $order_id;
        $this->order->status = $status;
        
        return $this->order->updateStatus();
    }
    
    /**
     * Handle API orders request
     * 
     * For RESTful API to get order details
     * 
     * @return void Outputs JSON response
     */
    public function handleApiRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $order_id = isset($_GET['id']) ? intval($_GET['id']) : null;
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
        
        // Validate manager access for sensitive operations
        if (!$this->validateManagerAccess()) {
            return;
        }
        
        // Log API request for auditing
        ErrorLogger::log("Orders API accessed - Method: $method, Order ID: $order_id, User ID: $user_id", "INFO");
        
        switch ($method) {
            case 'GET':
                $this->handleApiGetRequest($order_id, $user_id);
                break;
                
            case 'PUT':
                $this->handleApiPutRequest();
                break;
                
            case 'POST':
                $this->handleApiPostRequest();
                break;
                
            default:
                $this->sendJsonResponse(405, false, 'Method not allowed');
                break;
        }
    }
    
    /**
     * Handle GET requests for orders API
     * 
     * @param int|null $order_id Order ID
     * @param int|null $user_id User ID
     * @return void Outputs JSON response
     */
    private function handleApiGetRequest($order_id, $user_id) {
        // If order_id is provided, get specific order
        if ($order_id) {
            $result = $this->order->readOne($order_id);
            
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
                
                $this->sendJsonResponse(200, true, '', ['data' => $orderData]);
            } else {
                $this->sendJsonResponse(404, false, 'Order not found');
            }
        } 
        // If user_id is provided, get orders for that user
        else if ($user_id) {
            $results = $this->order->readByUser($user_id);
            $this->sendJsonResponse(200, true, '', ['data' => $results]);
        } 
        // Get all orders
        else {
            $results = $this->order->read();
            $this->sendJsonResponse(200, true, '', ['data' => $results]);
        }
    }
    
    /**
     * Handle PUT requests for orders API (update order)
     * 
     * @return void Outputs JSON response
     */
    private function handleApiPutRequest() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['order_id']) || !isset($data['status'])) {
            $this->sendJsonResponse(400, false, 'Missing required fields: order_id and status are required');
            return;
        }
        
        // Validate status value
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($data['status'], $validStatuses)) {
            $this->sendJsonResponse(400, false, 'Invalid status value. Must be one of: ' . implode(', ', $validStatuses));
            return;
        }
        
        // Check if order exists
        $existingOrder = $this->order->readOne($data['order_id']);
        if (!$existingOrder) {
            $this->sendJsonResponse(404, false, 'Order not found');
            return;
        }
        
        // Set order properties for update
        $this->order->order_id = $data['order_id'];
        $this->order->status = $data['status'];
        
        if ($this->order->updateStatus()) {
            $this->sendJsonResponse(200, true, 'Order status updated successfully');
        } else {
            $this->sendJsonResponse(500, false, 'Failed to update order status');
        }
    }
    
    /**
     * Handle POST requests for orders API (create order)
     * 
     * @return void Outputs JSON response
     */
    private function handleApiPostRequest() {
        // Call the placeOrder method which handles order creation
        $this->placeOrder();
    }
    
    /**
     * Handle order-related actions
     * 
     * @return void
     */
    public function handleRequest() {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        
        switch ($action) {
            case 'place_order':
                $this->placeOrder();
                break;
                
            case 'update_status':
                // Check if admin (you would need to implement admin check)
                $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : null;
                $status = isset($_POST['status']) ? $_POST['status'] : null;
                
                if ($order_id && $status) {
                    if ($this->updateOrderStatus($order_id, $status)) {
                        $_SESSION['flash_message'] = 'Order status updated successfully.';
                        $_SESSION['flash_type'] = 'success';
                    } else {
                        $_SESSION['flash_message'] = 'Failed to update order status.';
                        $_SESSION['flash_type'] = 'danger';
                    }
                } else {
                    $_SESSION['flash_message'] = 'Invalid order ID or status.';
                    $_SESSION['flash_type'] = 'danger';
                }
                
                redirect('/admin/orders');
                break;
                
            default:
                // Invalid action
                $_SESSION['flash_message'] = 'Invalid action.';
                $_SESSION['flash_type'] = 'danger';
                redirect('/');
                break;
        }
    }
    
    /**
     * Handle successful order creation
     * 
     * @param string $productName Product name
     * @return void
     */
    private function handleOrderSuccess($productName) {
        // Check if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_id' => $this->order->order_id,
                'product' => $productName,
                'redirect' => '/order_confirmation?id=' . $this->order->order_id
            ]);
        } else {
            // Set success message and redirect
            $_SESSION['flash_message'] = 'Order placed successfully!';
            $_SESSION['flash_type'] = 'success';
            redirect('/order_confirmation?id=' . $this->order->order_id);
        }
    }
    
    /**
     * Handle error in order operations
     * 
     * @param Exception $e Exception
     * @param string $logPrefix Log message prefix
     * @return void
     */
    private function handleError(Exception $e, $logPrefix) {
        ErrorLogger::log("$logPrefix: " . $e->getMessage(), "ERROR");
        
        // Check if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } else {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/products');
        }
    }
    
    /**
     * Handle not logged in error
     * 
     * @param string $message Error message
     * @param string $redirect Redirect URL
     * @return void
     */
    private function handleNotLoggedIn($message, $redirect) {
        // If AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => $redirect
            ]);
        } else {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = 'warning';
            redirect($redirect);
        }
    }
    
    /**
     * Validate manager access for sensitive operations
     * 
     * @return boolean Valid access
     */
    private function validateManagerAccess() {
        // In a real application, you would use a more secure authentication mechanism
        // For this assessment, we'll use a simple token-based approach
        $manager_token = isset($_GET['manager_token']) ? $_GET['manager_token'] : '';
        
        // Also check in JSON data for PUT requests
        if (empty($manager_token) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $manager_token = $data['manager_token'] ?? '';
        }
        
        // Validate the token (in production, this would be a secure token verification)
        if ($manager_token !== 'manager123') {
            $this->sendJsonResponse(403, false, 'Forbidden - Manager access required. Please provide a valid manager token.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Send JSON response with status code
     * 
     * @param int $statusCode HTTP status code
     * @param bool $success Success status
     * @param string $message Message
     * @param array $additionalData Additional data
     * @return void
     */
    private function sendJsonResponse($statusCode, $success, $message, $additionalData = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Merge additional data
        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }
        
        echo json_encode($response);
    }
}
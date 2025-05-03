<?php
/**
 * CartController.php - Shopping Cart Controller
 *
 * Handles all cart operations including adding, updating, removing items,
 * and checkout process. Manages cart data between client and server.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../config/errorlogger.php';

class CartController {
    /** @var PDO Database connection */
    private $db;
    
    /** @var Cart Cart model instance */
    private $cart;
    
    /** @var Product Product model instance */
    private $product;
    
    /**
     * Constructor initializes database connection and model instances
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->cart = new Cart($this->db);
        $this->product = new Product($this->db);
    }
    
    /**
     * Add item to cart
     * 
     * @return void Outputs JSON response
     */
    public function addToCart() {
        // Debug log
        error_log("addToCart method called");
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->handleNotLoggedIn('You must be logged in to add items to cart.', '/login');
            return;
        }
        
        try {
            // Get form/request data
            $data = $this->getRequestData();
            
            // Verify CSRF token for AJAX requests
            if (!$this->verifyCSRFToken($data)) {
                return;
            }
            
            // Extract product_id and quantity
            $product_id = $this->extractProductId($data);
            $quantity = $this->extractQuantity($data);
            
            if (!$product_id) {
                throw new Exception('Product ID is required.');
            }
            
            // Validate product exists
            $product = $this->product->readOne($product_id);
            if (!$product) {
                throw new Exception('Invalid product selected.');
            }
            
            // Validate quantity
            if (!is_numeric($quantity) || $quantity < 1) {
                throw new Exception('Invalid quantity.');
            }
            
            // Set cart properties
            $this->cart->user_id = $_SESSION['user_id'];
            $this->cart->product_id = $product_id;
            $this->cart->quantity = $quantity;
            
            error_log("Adding to cart: user_id={$this->cart->user_id}, product_id={$this->cart->product_id}, quantity={$this->cart->quantity}");
            
            // Add to cart
            if ($this->cart->add()) {
                // Product added to cart
                error_log("Product added to cart successfully");
                
                // Get updated cart data
                $cartData = $this->cart->getUserCart($_SESSION['user_id']);
                $_SESSION['cart_count'] = $cartData['item_count'];
                
                error_log("Updated cart count: {$_SESSION['cart_count']}");
                
                // Determine if it's an AJAX request
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
                
                // Check if it's an API request or AJAX request
                if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false || $isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product added to cart!',
                        'product' => $product['product_name'],
                        'cart_count' => $cartData['item_count'],
                        'cart' => $cartData,
                        'cart_total' => $cartData['formatted_total']
                    ]);
                } else {
                    // Only redirect for non-API/non-AJAX requests
                    $_SESSION['flash_message'] = 'Product added to cart!';
                    $_SESSION['flash_type'] = 'success';
                    redirect('/cart');
                }
            } else {
                throw new Exception('Failed to add product to cart. Please try again.');
            }
        } catch (Exception $e) {
            $this->handleError($e, "Cart error");
        }
    }
    
    /**
     * Update cart item quantity
     * 
     * @return void Outputs JSON response
     */
    public function updateCartItem() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, 'You must be logged in to update cart.', ['redirect' => '/login']);
            return;
        }
        
        try {
            // Get request data
            $data = json_decode(file_get_contents('php://input'));
            
            // Verify CSRF token
            if (!isset($data->csrf_token) || !verify_csrf_token($data->csrf_token)) {
                error_log("CSRF token verification failed for cart update");
                $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                return;
            }
            
            // Validate inputs
            if (!isset($data->cart_id) || !isset($data->quantity)) {
                throw new Exception('Missing required parameters.');
            }
            
            $cart_id = $data->cart_id;
            $quantity = $data->quantity;
            
            // Validate quantity
            if (!is_numeric($quantity) || $quantity < 1) {
                throw new Exception('Invalid quantity.');
            }
            
            // Update cart
            if ($this->cart->updateQuantity($cart_id, $quantity)) {
                // Get updated cart data
                $cartData = $this->cart->getUserCart($_SESSION['user_id']);
                $_SESSION['cart_count'] = $cartData['item_count'];
                
                $this->sendJsonResponse(true, 'Cart updated.', ['cart' => $cartData]);
            } else {
                throw new Exception('Failed to update cart.');
            }
        } catch (Exception $e) {
            ErrorLogger::log("Cart update error: " . $e->getMessage(), "ERROR");
            $this->sendJsonResponse(false, $e->getMessage());
        }
    }

    /**
     * Remove item from cart
     * 
     * @return void Outputs JSON response
     */
    public function removeCartItem() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, 'You must be logged in to remove items from cart.', ['redirect' => '/login']);
            return;
        }
        
        try {
            // Get request data
            $data = json_decode(file_get_contents('php://input'));
            
            // Verify CSRF token
            if (!isset($data->csrf_token) || !verify_csrf_token($data->csrf_token)) {
                error_log("CSRF token verification failed for cart item removal");
                $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                return;
            }
            
            // Validate input
            if (!isset($data->cart_id)) {
                throw new Exception('Missing cart item ID.');
            }
            
            $cart_id = $data->cart_id;
            
            // Remove from cart
            if ($this->cart->removeItem($cart_id)) {
                // Get updated cart data
                $cartData = $this->cart->getUserCart($_SESSION['user_id']);
                $_SESSION['cart_count'] = $cartData['item_count'];
                
                $this->sendJsonResponse(true, 'Item removed from cart.', ['cart' => $cartData]);
            } else {
                throw new Exception('Failed to remove item from cart.');
            }
        } catch (Exception $e) {
            ErrorLogger::log("Cart remove error: " . $e->getMessage(), "ERROR");
            $this->sendJsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * Process checkout
     * 
     * @return void Outputs JSON response
     */
    public function checkout() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            error_log("Checkout failed: User not logged in");
            $this->sendJsonResponse(false, 'You must be logged in to checkout.', ['redirect' => '/login']);
            return;
        }
        
        try {
            // Get request data
            $data = json_decode(file_get_contents('php://input'));
            
            // Verify CSRF token
            if (!isset($data->csrf_token) || !verify_csrf_token($data->csrf_token)) {
                error_log("CSRF token verification failed for checkout");
                $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                return;
            }
            
            // Check if cart is empty
            $cartData = $this->cart->getUserCart($_SESSION['user_id']);
            error_log("Checkout - Cart data: " . print_r($cartData, true));
            
            if (empty($cartData['items'])) {
                error_log("Checkout failed: Cart is empty for user ID: " . $_SESSION['user_id']);
                $this->sendJsonResponse(false, 'Your cart is empty. Please add some products before checkout.');
                return;
            }
            
            // Process checkout
            $order_ids = $this->cart->checkout($_SESSION['user_id']);
            
            if ($order_ids) {
                // Reset cart count in session
                $_SESSION['cart_count'] = 0;
                
                // Return JSON response
                $this->sendJsonResponse(true, 'Order placed successfully!', [
                    'order_ids' => $order_ids,
                    'redirect' => '/customer/dashboard'
                ]);
            } else {
                error_log("Checkout failed: Could not create orders for user ID: " . $_SESSION['user_id']);
                $this->sendJsonResponse(false, 'Failed to process checkout. Please try again.');
            }
        } catch (Exception $e) {
            error_log("Checkout exception: " . $e->getMessage());
            ErrorLogger::log("Checkout error: " . $e->getMessage(), "ERROR");
            $this->sendJsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * Get user's cart data
     * 
     * @param int $user_id User ID
     * @return array Cart data
     */
    public function getUserCart($user_id) {
        return $this->cart->getUserCart($user_id);
    }
    
    /**
     * Handle API cart requests
     * 
     * @return void Outputs JSON response
     */
    public function handleApiRequest() {
        // Get action from GET or look in the request body
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        // Log for debugging
        error_log("Cart API request - action: $action");
        
        // If no action in GET, try to get from the request body
        if (empty($action)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['action'])) {
                $action = $input['action'];
                error_log("Action from request body: $action");
            } else {
                // For GET requests with no action, assume we want cart data
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->getCartData();
                    return;
                }
                error_log("No action found in request");
            }
        }
        
        switch ($action) {
            case 'add':
                $this->addToCart();
                break;
                
            case 'update':
                $this->updateCartItem();
                break;
                
            case 'remove':
                $this->removeCartItem();
                break;
                
            case 'checkout':
                $this->checkout();
                break;
                
            default:
                // If no action but it's a GET request, return cart data
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->getCartData();
                } else {
                    $this->sendJsonResponse(false, 'Invalid action or no action specified: "' . $action . '"');
                }
                break;
        }
    }

    /**
     * Get cart data for API requests
     * 
     * @return void Outputs JSON response
     */
    public function getCartData() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, 'You must be logged in to view your cart.', ['redirect' => '/login']);
            return;
        }
        
        try {
            // Get cart data
            $cartData = $this->getUserCart($_SESSION['user_id']);
            
            // Update session cart count
            $_SESSION['cart_count'] = $cartData['item_count'];
            
            // Return cart data
            $this->sendJsonResponse(true, '', ['cart' => $cartData]);
        } catch (Exception $e) {
            error_log("Error getting cart data: " . $e->getMessage());
            $this->sendJsonResponse(false, 'An error occurred while getting cart data.');
        }
    }
    
    /**
     * Extract product ID from request data
     * 
     * @param object|null $data Request data
     * @return int|null Product ID
     */
    private function extractProductId($data) {
        if ($data && isset($data->product_id)) {
            // Use JSON data
            return $data->product_id;
        } elseif (isset($_POST['product_id'])) {
            // Use POST data
            return $_POST['product_id'];
        }
        
        return null;
    }
    
    /**
     * Extract quantity from request data
     * 
     * @param object|null $data Request data
     * @return int Quantity
     */
    private function extractQuantity($data) {
        if ($data && isset($data->quantity)) {
            // Use JSON data
            return $data->quantity;
        } elseif (isset($_POST['quantity'])) {
            // Use POST data
            return $_POST['quantity'];
        }
        
        // Default quantity
        return 1;
    }
    
    /**
     * Get request data from JSON or POST
     * 
     * @return object|null Request data
     */
    private function getRequestData() {
        // Check if we have JSON data
        $rawData = file_get_contents('php://input');
        if (!empty($rawData)) {
            error_log("Raw request data: " . $rawData);
            return json_decode($rawData);
        }
        
        // If no JSON data, return null (will use POST later)
        return null;
    }
    
    /**
     * Verify CSRF token in request data
     * 
     * @param object|null $data Request data
     * @return bool Token verified
     */
    private function verifyCSRFToken($data) {
        // Check if token exists in JSON data
        if ($data && isset($data->csrf_token)) {
            if (!verify_csrf_token($data->csrf_token)) {
                error_log("CSRF token verification failed");
                $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                return false;
            }
        }
        // Check if token exists in POST data
        elseif (isset($_POST['csrf_token'])) {
            if (!verify_csrf_token($_POST['csrf_token'])) {
                error_log("CSRF token verification failed from POST data");
                $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                return false;
            }
        }
        // No token found - CSRF protection violation
        else {
            // Log security warning
            error_log("CSRF protection violation: No CSRF token found in request");
            // Return error response
            $this->sendJsonResponse(false, 'Security token missing. Cross-site request forgery protection triggered.');
            return false;
        }
        
        // Token found and verified
        return true;
    }
    
    /**
     * Handle not logged in error
     * 
     * @param string $message Error message
     * @param string $redirect Redirect URL
     * @return void
     */
    private function handleNotLoggedIn($message, $redirect) {
        error_log("User not logged in - rejecting cart operation");
        
        // If AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->sendJsonResponse(false, $message, ['redirect' => $redirect]);
        } else {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = 'warning';
            redirect($redirect);
        }
    }
    
    /**
     * Handle error in cart operations
     * 
     * @param Exception $e Exception
     * @param string $logPrefix Log message prefix
     * @return void
     */
    private function handleError(Exception $e, $logPrefix) {
        error_log("$logPrefix: " . $e->getMessage());
        ErrorLogger::log("$logPrefix: " . $e->getMessage(), "ERROR");
        
        // Check if it's an AJAX request or API request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
                
        if ($isAjax || $isApi) {
            $this->sendJsonResponse(false, $e->getMessage());
        } else {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/products');
        }
    }
    
    /**
     * Send JSON response
     * 
     * @param bool $success Success status
     * @param string $message Message
     * @param array $data Additional data
     * @return void
     */
    private function sendJsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Merge additional data
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
    }
}
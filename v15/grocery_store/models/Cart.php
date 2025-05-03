<?php
/**
 * Cart.php - Shopping Cart Model
 *
 * This class handles shopping cart operations including adding, updating,
 * and retrieving cart items for users. It also provides checkout functionality.
 */

class Cart {
    /** @var PDO Database connection */
    private $conn;
    
    /** @var string Database table name */
    private $table_name = "cart";
    
    // Cart properties
    public $cart_id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $added_date;
    
    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection object
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Add item to cart
     * 
     * Adds a new product to the user's cart or updates the quantity
     * if the product already exists in the cart
     * 
     * @return boolean Success or failure
     */
    public function add() {
        try {
            // Debug
            error_log("Cart add() called - User: {$this->user_id}, Product: {$this->product_id}, Quantity: {$this->quantity}");
            
            // Check if product already exists in user's cart
            $existing = $this->getCartItem($this->user_id, $this->product_id);
            
            if ($existing) {
                // Update quantity instead of adding new item
                $newQuantity = $existing['quantity'] + $this->quantity;
                error_log("Existing cart item found with quantity: {$existing['quantity']}, updating to: {$newQuantity}");
                return $this->updateQuantity($existing['cart_id'], $newQuantity);
            }
            
            // Prepare query to insert new cart item
            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, product_id, quantity) 
                     VALUES 
                     (:user_id, :product_id, :quantity)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->product_id = htmlspecialchars(strip_tags($this->product_id));
            $this->quantity = htmlspecialchars(strip_tags($this->quantity));
            
            // Bind parameters
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":quantity", $this->quantity);
            
            // Execute query
            if ($stmt->execute()) {
                $this->cart_id = $this->conn->lastInsertId();
                error_log("New cart item added with ID: {$this->cart_id}");
                return true;
            }
            
            error_log("Failed to execute cart insert query");
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Cart add error: " . $e->getMessage(), "ERROR");
            error_log("Cart add exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all cart items for a user with product details
     * 
     * Retrieves all items in the user's cart with product information
     * 
     * @param int $user_id User ID
     * @return array Cart data including items, counts, and totals
     */
    public function getUserCart($user_id) {
        try {
            // Log for debugging
            error_log("Fetching cart for user ID: $user_id");
            
            // Query with JOIN to get product details
            $query = "SELECT c.cart_id, c.user_id, c.product_id, c.quantity, c.added_at,
                    p.product_name, p.price, p.image_path, p.category
                    FROM " . $this->table_name . " c
                    LEFT JOIN products p ON c.product_id = p.product_id
                    WHERE c.user_id = :user_id
                    ORDER BY c.added_at DESC";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $user_id = htmlspecialchars(strip_tags($user_id));
            
            // Bind parameter
            $stmt->bindParam(":user_id", $user_id);
            
            // Execute query
            $stmt->execute();
            
            // Log the count of results
            error_log("Cart query executed, found " . $stmt->rowCount() . " items");
            
            $cart_items = [];
            $total_price = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Calculate item total
                $item_total = $row['price'] * $row['quantity'];
                $total_price += $item_total;
                
                // Add formatted price and total
                $row['formatted_price'] = '£' . number_format($row['price'], 2);
                $row['formatted_total'] = '£' . number_format($item_total, 2);
                
                $cart_items[] = $row;
            }
            
            // Return structured cart data
            return [
                'items' => $cart_items,
                'total_price' => $total_price,
                'formatted_total' => '£' . number_format($total_price, 2),
                'item_count' => count($cart_items)
            ];
        } catch (Exception $e) {
            error_log("Error in getUserCart: " . $e->getMessage());
            ErrorLogger::log("Error getting user cart: " . $e->getMessage(), "ERROR");
            
            // Return empty cart on error
            return [
                'items' => [],
                'total_price' => 0,
                'formatted_total' => '£0.00',
                'item_count' => 0
            ];
        }
    }
    
    /**
     * Get specific cart item
     * 
     * Retrieves a specific cart item by user ID and product ID
     * 
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return array|boolean Cart item or false
     */
    public function getCartItem($user_id, $product_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE user_id = :user_id AND product_id = :product_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $user_id = htmlspecialchars(strip_tags($user_id));
        $product_id = htmlspecialchars(strip_tags($product_id));
        
        // Bind parameters
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":product_id", $product_id);
        
        // Execute query
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * Update cart item quantity
     * 
     * @param int $cart_id Cart item ID
     * @param int $quantity New quantity
     * @return boolean Success or failure
     */
    public function updateQuantity($cart_id, $quantity) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET quantity = :quantity 
                     WHERE cart_id = :cart_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $cart_id = htmlspecialchars(strip_tags($cart_id));
            $quantity = htmlspecialchars(strip_tags($quantity));
            
            // Validate quantity
            if (!is_numeric($quantity) || $quantity < 1) {
                throw new Exception("Invalid quantity value");
            }
            
            // Bind parameters
            $stmt->bindParam(":cart_id", $cart_id);
            $stmt->bindParam(":quantity", $quantity);
            
            // Execute query
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Cart update error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Remove item from cart
     * 
     * @param int $cart_id Cart item ID
     * @return boolean Success or failure
     */
    public function removeItem($cart_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE cart_id = :cart_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $cart_id = htmlspecialchars(strip_tags($cart_id));
            
            // Bind parameter
            $stmt->bindParam(":cart_id", $cart_id);
            
            // Execute query
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Cart remove error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Clear all items from user's cart
     * 
     * @param int $user_id User ID
     * @return boolean Success or failure
     */
    public function clearCart($user_id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize input
            $user_id = htmlspecialchars(strip_tags($user_id));
            
            // Bind parameter
            $stmt->bindParam(":user_id", $user_id);
            
            // Execute query
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Cart clear error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Convert cart to order (checkout)
     * 
     * Creates an order from the user's cart and clears the cart
     * 
     * @param int $user_id User ID
     * @return array|boolean Array of created order IDs or false
     */
    public function checkout($user_id) {
        // Get user's cart
        $cart = $this->getUserCart($user_id);
        
        if (empty($cart['items'])) {
            return false;
        }
        
        // Get user information - needed for customer details
        require_once __DIR__ . '/User.php';
        $user = new User($this->conn);
        $userData = $user->getUserById($user_id);
        
        if (!$userData) {
            ErrorLogger::log("Checkout error: Could not find user information for user ID: $user_id", "ERROR");
            return false;
        }
        
        // Begin transaction for data integrity
        $this->conn->beginTransaction();
        
        try {
            // Create Order object
            require_once __DIR__ . '/Order.php';
            $order = new Order($this->conn);
            
            // Create a single order
            $order->user_id = $user_id;
            $order->status = 'pending';
            
            // Set customer information from user data
            $order->customer_name = $userData['name'];
            $order->customer_email = $userData['email'];
            $order->customer_phone = $userData['phone'];
            
            if ($order->create()) {
                // Add all items to the order_items table
                foreach ($cart['items'] as $item) {
                    if (!$order->addItem($item['product_id'], $item['quantity'], $item['price'])) {
                        // If adding any item fails, roll back
                        $this->conn->rollBack();
                        ErrorLogger::log("Failed to add item to order", "ERROR");
                        return false;
                    }
                }
                
                // Clear the cart
                if (!$this->clearCart($user_id)) {
                    $this->conn->rollBack();
                    ErrorLogger::log("Failed to clear cart during checkout", "ERROR");
                    return false;
                }
                
                // Commit transaction
                $this->conn->commit();
                
                return [$order->order_id];
            } else {
                // If order creation fails, roll back
                $this->conn->rollBack();
                ErrorLogger::log("Failed to create order during checkout", "ERROR");
                return false;
            }
        } catch (Exception $e) {
            // Roll back transaction on error
            $this->conn->rollBack();
            ErrorLogger::log("Checkout error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Get cart item count for user
     * 
     * @param int $user_id User ID
     * @return int Item count
     */
    public function getCartItemCount($user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                     WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting cart count: " . $e->getMessage(), "ERROR");
            return 0;
        }
    }
}
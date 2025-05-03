<?php
/**
 * Order.php - Order Management Model
 *
 * This class handles order operations including creating, retrieving,
 * and updating orders. It manages both order headers and order items.
 */

class Order {
    /** @var PDO Database connection */
    private $conn;
    
    /** @var string Database table name */
    private $table_name = "orders";
    
    /** @var string Order items table name */
    private $items_table = "order_items";
    
    // Order properties
    public $order_id;
    public $user_id;
    public $order_date;
    public $status;
    public $customer_name;
    public $customer_email;
    public $customer_phone;
    
    /** @var array Valid order statuses */
    private $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    
    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection object
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new order
     * 
     * Creates a new order header record
     * 
     * @return boolean Success or failure
     */
    public function create() {
        try {
            // Validate order properties
            if (!$this->validateOrderData()) {
                return false;
            }
            
            // Set default status if not specified
            if (empty($this->status) || !in_array($this->status, $this->valid_statuses)) {
                $this->status = 'pending';
            }
            
            // Prepare query
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, status, customer_name, customer_email, customer_phone) 
                    VALUES 
                    (:user_id, :status, :customer_name, :customer_email, :customer_phone)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->customer_name = htmlspecialchars(strip_tags($this->customer_name));
            $this->customer_email = htmlspecialchars(strip_tags($this->customer_email));
            $this->customer_phone = htmlspecialchars(strip_tags($this->customer_phone));
            
            // Bind parameters
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":customer_name", $this->customer_name);
            $stmt->bindParam(":customer_email", $this->customer_email);
            $stmt->bindParam(":customer_phone", $this->customer_phone);
            
            // Execute query
            if ($stmt->execute()) {
                $this->order_id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Order creation error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Validate order data
     * 
     * Ensures required fields are provided and valid
     * 
     * @return boolean Valid or invalid
     */
    private function validateOrderData() {
        // Required fields
        if (empty($this->user_id) || !is_numeric($this->user_id)) {
            ErrorLogger::log("Invalid user_id for order", "ERROR");
            return false;
        }
        
        if (empty($this->customer_name)) {
            ErrorLogger::log("Missing customer name for order", "ERROR");
            return false;
        }
        
        if (empty($this->customer_email) || !filter_var($this->customer_email, FILTER_VALIDATE_EMAIL)) {
            ErrorLogger::log("Invalid email for order", "ERROR");
            return false;
        }
        
        if (empty($this->customer_phone)) {
            ErrorLogger::log("Missing phone for order", "ERROR");
            return false;
        }
        
        return true;
    }
    
    /**
     * Add an item to an order
     * 
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @param float $price Price
     * @return boolean Success or failure
     */
    public function addItem($product_id, $quantity, $price) {
        try {
            // Validate inputs
            if (empty($this->order_id) || !is_numeric($product_id) || !is_numeric($quantity) || !is_numeric($price)) {
                ErrorLogger::log("Invalid inputs for addItem: order_id={$this->order_id}, product_id={$product_id}, quantity={$quantity}, price={$price}", "ERROR");
                return false;
            }
            
            if ($quantity < 1) {
                ErrorLogger::log("Invalid quantity for addItem: {$quantity}", "ERROR");
                return false;
            }
            
            // Log for debugging
            error_log("Adding item to order: order_id={$this->order_id}, product_id={$product_id}, quantity={$quantity}, price={$price}");
            
            $query = "INSERT INTO " . $this->items_table . " (order_id, product_id, quantity, price)
                      VALUES (:order_id, :product_id, :quantity, :price)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $product_id = htmlspecialchars(strip_tags($product_id));
            $quantity = htmlspecialchars(strip_tags($quantity));
            $price = htmlspecialchars(strip_tags($price));
            
            // Bind parameters
            $stmt->bindParam(":order_id", $this->order_id);
            $stmt->bindParam(":product_id", $product_id);
            $stmt->bindParam(":quantity", $quantity);
            $stmt->bindParam(":price", $price);
            
            // Execute query
            $result = $stmt->execute();
            
            // Debug log
            error_log("Item added to order: " . ($result ? "Success" : "Failed"));
            
            return $result;
        } catch (PDOException $e) {
            ErrorLogger::log("Error adding item to order: " . $e->getMessage(), "ERROR");
            error_log("Error adding item to order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get items for an order
     * 
     * @param int $order_id Order ID
     * @return array Order items
     */
    public function getOrderItems($order_id) {
        try {
            $query = "SELECT oi.*, p.product_name, p.image_path
                    FROM " . $this->items_table . " oi
                    JOIN products p ON oi.product_id = p.product_id
                    WHERE oi.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":order_id", $order_id);
            $stmt->execute();
            
            $items = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['formatted_price'] = '£' . number_format($row['price'], 2);
                $row['formatted_total'] = '£' . number_format($row['price'] * $row['quantity'], 2);
                $items[] = $row;
            }
            
            return $items;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting order items: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get order total
     * 
     * @param int $order_id Order ID
     * @return float Order total
     */
    public function getOrderTotal($order_id) {
        try {
            $query = "SELECT SUM(price * quantity) as total
                    FROM " . $this->items_table . "
                    WHERE order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":order_id", $order_id);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            ErrorLogger::log("Error calculating order total: " . $e->getMessage(), "ERROR");
            return 0;
        }
    }
    
    /**
     * Read all orders
     * 
     * Retrieves all orders with user and item details
     * 
     * @return array Orders
     */
    public function read() {
        try {
            $query = "SELECT o.order_id, o.user_id, o.order_date, o.status,
                    o.customer_name as user_name, o.customer_email as email, o.customer_phone as phone
                    FROM " . $this->table_name . " o
                    ORDER BY o.order_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $orders = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['items'] = $this->getOrderItems($row['order_id']);
                $total = $this->getOrderTotal($row['order_id']);
                $row['total_price'] = $total;
                $row['formatted_total'] = '£' . number_format($total, 2);
                $orders[] = $row;
            }
            
            return $orders;
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading orders: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Read one order by ID
     * 
     * @param int $id Order ID
     * @return array|boolean Order data or false
     */
    public function readOne($id) {
        try {
            $query = "SELECT o.order_id, o.user_id, o.order_date, o.status,
                    o.customer_name as user_name, o.customer_email as email, o.customer_phone as phone
                    FROM " . $this->table_name . " o
                    WHERE o.order_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                $order['items'] = $this->getOrderItems($order['order_id']);
                $total = $this->getOrderTotal($order['order_id']);
                $order['total_price'] = $total;
                $order['formatted_total'] = '£' . number_format($total, 2);
                return $order;
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading order: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Read orders by user ID
     * 
     * @param int $user_id User ID
     * @return array Orders
     */
    public function readByUser($user_id) {
        try {
            $query = "SELECT o.order_id, o.user_id, o.order_date, o.status
                    FROM " . $this->table_name . " o
                    WHERE o.user_id = :user_id
                    ORDER BY o.order_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $orders = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['items'] = $this->getOrderItems($row['order_id']);
                $total = $this->getOrderTotal($row['order_id']);
                $row['total_price'] = $total;
                $row['formatted_total'] = '£' . number_format($total, 2);
                $orders[] = $row;
            }
            
            return $orders;
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading user orders: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Update order status
     * 
     * @return boolean Success or failure
     */
    public function updateStatus() {
        try {
            // Validate status
            if (!in_array($this->status, $this->valid_statuses)) {
                ErrorLogger::log("Invalid order status: {$this->status}", "ERROR");
                return false;
            }
            
            $query = "UPDATE " . $this->table_name . " 
                    SET status = :status 
                    WHERE order_id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->order_id = htmlspecialchars(strip_tags($this->order_id));
            
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":id", $this->order_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Update order status error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Get recent orders for dashboard
     * 
     * @param int $limit Number of orders to return
     * @return array Recent orders
     */
    public function getRecentOrders($limit = 5) {
        try {
            $query = "SELECT o.order_id, o.user_id, o.order_date, o.status,
                    o.customer_name, o.customer_email, o.customer_phone
                    FROM " . $this->table_name . " o
                    ORDER BY o.order_date DESC
                    LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $orders = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['items'] = $this->getOrderItems($row['order_id']);
                $total = $this->getOrderTotal($row['order_id']);
                $row['total_price'] = $total;
                $row['formatted_total'] = '£' . number_format($total, 2);
                $orders[] = $row;
            }
            
            return $orders;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting recent orders: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get order counts by status
     * 
     * @return array Order counts by status
     */
    public function getOrderCountsByStatus() {
        try {
            $query = "SELECT status, COUNT(*) as count
                    FROM " . $this->table_name . "
                    GROUP BY status";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[$row['status']] = $row['count'];
            }
            
            // Ensure all statuses have a count
            foreach ($this->valid_statuses as $status) {
                if (!isset($results[$status])) {
                    $results[$status] = 0;
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting order counts: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
}
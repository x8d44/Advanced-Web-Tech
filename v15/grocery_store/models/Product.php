<?php
/**
 * Product.php - Product Management Model
 *
 * This class handles product operations including retrieving,
 * filtering, and organizing product data for display.
 */

class Product {
    /** @var PDO Database connection */
    private $conn;
    
    /** @var string Database table name */
    private $table_name = "products";

    /** @var int Product ID */
    public $product_id;
    
    /** @var string Product category */
    public $category;
    
    /** @var string Product name */
    public $product_name;
    
    /** @var float Product price */
    public $price;
    
    /** @var string Image path */
    public $image_path;
    
    /** @var string Product description */
    public $description;

    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection object
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all products
     * 
     * Retrieves all products from the database
     * 
     * @return array Products or empty array
     */
    public function read() {
        try {
            $query = "SELECT * FROM " . $this->table_name;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $this->processResults($stmt);
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading products: " . $e->getMessage(), "ERROR");
            return [];
        }
    }

    /**
     * Get products by category
     * 
     * Retrieves products filtered by category
     * 
     * @param string $category Product category
     * @return array Filtered products or empty array
     */
    public function readByCategory($category) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE category = :category";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category", $category);
            $stmt->execute();
            
            return $this->processResults($stmt);
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading products by category: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get product by ID
     * 
     * Retrieves a single product by its ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function readOne($id) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE product_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return null;
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading product: " . $e->getMessage(), "ERROR");
            return null;
        }
    }
    
    /**
     * Get products by name
     * 
     * Retrieves products filtered by exact product name
     * 
     * @param string $productName Product name
     * @return array Filtered products or empty array
     */
    public function readByProductName($productName) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " WHERE product_name = :product_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":product_name", $productName);
            $stmt->execute();
            
            return $this->processResults($stmt);
        } catch (PDOException $e) {
            ErrorLogger::log("Error reading products by name: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Search products by keyword
     * 
     * Searches products by keyword in name or description
     * 
     * @param string $keyword Search keyword
     * @return array Matching products or empty array
     */
    public function searchProducts($keyword) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE product_name LIKE :keyword 
                     OR description LIKE :keyword";
            
            $search_term = "%" . $keyword . "%";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":keyword", $search_term);
            $stmt->execute();
            
            return $this->processResults($stmt);
        } catch (PDOException $e) {
            ErrorLogger::log("Error searching products: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get unique product categories
     * 
     * @return array Unique categories
     */
    public function getCategories() {
        try {
            $query = "SELECT DISTINCT category FROM " . $this->table_name . " ORDER BY category";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $categories = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = $row['category'];
            }
            
            return $categories;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting categories: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get product names by category
     * 
     * @param string $category Product category
     * @return array Product names
     */
    public function getProductNamesByCategory($category) {
        try {
            $query = "SELECT DISTINCT product_name FROM " . $this->table_name . " 
                     WHERE category = :category 
                     ORDER BY product_name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":category", $category);
            $stmt->execute();
            
            $productNames = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $productNames[] = $row['product_name'];
            }
            
            return $productNames;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting product names: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get product price range
     * 
     * @return array Min and max prices
     */
    public function getPriceRange() {
        try {
            $query = "SELECT MIN(price) as min_price, MAX(price) as max_price 
                     FROM " . $this->table_name;
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting price range: " . $e->getMessage(), "ERROR");
            return ['min_price' => 0, 'max_price' => 0];
        }
    }
    
    /**
     * Process database query results into product array
     * 
     * @param PDOStatement $stmt Executed PDO statement
     * @return array Processed products
     */
    private function processResults($stmt) {
        $products = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Add formatted price
            $row['formatted_price'] = '£' . number_format($row['price'], 2);
            $products[] = $row;
        }
        
        return $products;
    }
    
    /**
     * Create a new product
     * 
     * @return boolean Success or failure
     */
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (product_name, description, price, category, image_path) 
                    VALUES 
                    (:product_name, :description, :price, :category, :image_path)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->product_name = htmlspecialchars(strip_tags($this->product_name));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->price = htmlspecialchars(strip_tags($this->price));
            $this->category = htmlspecialchars(strip_tags($this->category));
            $this->image_path = htmlspecialchars(strip_tags($this->image_path));
            
            // Bind parameters
            $stmt->bindParam(":product_name", $this->product_name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":category", $this->category);
            $stmt->bindParam(":image_path", $this->image_path);
            
            // Execute query
            if ($stmt->execute()) {
                $this->product_id = $this->conn->lastInsertId();
                return true;
            }
            return false;
            
        } catch (PDOException $e) {
            ErrorLogger::log("Error creating product: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Update product
     * 
     * @return boolean Success or failure
     */
    public function update() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                    SET 
                    product_name = :product_name, 
                    description = :description, 
                    price = :price, 
                    category = :category, 
                    image_path = :image_path 
                    WHERE 
                    product_id = :product_id";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->product_name = htmlspecialchars(strip_tags($this->product_name));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->price = htmlspecialchars(strip_tags($this->price));
            $this->category = htmlspecialchars(strip_tags($this->category));
            $this->image_path = htmlspecialchars(strip_tags($this->image_path));
            $this->product_id = htmlspecialchars(strip_tags($this->product_id));
            
            // Bind parameters
            $stmt->bindParam(":product_name", $this->product_name);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":price", $this->price);
            $stmt->bindParam(":category", $this->category);
            $stmt->bindParam(":image_path", $this->image_path);
            $stmt->bindParam(":product_id", $this->product_id);
            
            // Execute query
            return $stmt->execute();
            
        } catch (PDOException $e) {
            ErrorLogger::log("Error updating product: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Delete product
     * 
     * @param int $id Product ID
     * @return boolean Success or failure
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE product_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Error deleting product: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
}
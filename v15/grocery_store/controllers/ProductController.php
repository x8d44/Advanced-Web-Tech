<?php
/**
 * ProductController.php - Product Management Controller
 *
 * Handles product-related operations including listing, filtering,
 * and retrieving product details. Implements category-based filtering
 * with AJAX support.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    /** @var PDO Database connection */
    private $db;
    
    /** @var Product Product model instance */
    private $product;
    
    /**
     * Constructor initializes database connection and product model
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new Product($this->db);
    }
    
    /**
     * Get all products
     * 
     * @return array All products
     */
    public function getAllProducts() {
        try {
            $products = $this->product->read();
            
            // Add SEO-friendly product URLs
            foreach ($products as &$product) {
                $product['product_url'] = $this->generateProductUrl($product);
            }
            
            return $products;
        } catch (Exception $e) {
            ErrorLogger::log("Error getting all products: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get products by category
     * 
     * @param string $category Product category
     * @return array Filtered products
     */
    public function getProductsByCategory($category) {
        try {
            $products = $this->product->readByCategory($category);
            
            // Add SEO-friendly product URLs
            foreach ($products as &$product) {
                $product['product_url'] = $this->generateProductUrl($product);
            }
            
            return $products;
        } catch (Exception $e) {
            ErrorLogger::log("Error getting products by category: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get products by name
     * 
     * @param string $productName Product name
     * @return array Filtered products
     */
    public function getProductsByName($productName) {
        try {
            $products = $this->product->readByProductName($productName);
            
            // Add SEO-friendly product URLs
            foreach ($products as &$product) {
                $product['product_url'] = $this->generateProductUrl($product);
            }
            
            return $products;
        } catch (Exception $e) {
            ErrorLogger::log("Error getting products by name: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product data or null
     */
    public function getProductById($id) {
        try {
            $product = $this->product->readOne($id);
            
            if ($product) {
                // Add SEO-friendly product URL
                $product['product_url'] = $this->generateProductUrl($product);
            }
            
            return $product;
        } catch (Exception $e) {
            ErrorLogger::log("Error getting product by ID: " . $e->getMessage(), "ERROR");
            return null;
        }
    }
    
    /**
     * Get unique product categories
     * 
     * @return array Unique categories
     */
    public function getCategories() {
        try {
            return $this->product->getCategories();
        } catch (Exception $e) {
            ErrorLogger::log("Error getting categories: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Get unique product names by category
     * 
     * @param string $category Product category
     * @return array Product names
     */
    public function getProductNamesByCategory($category) {
        try {
            return $this->product->getProductNamesByCategory($category);
        } catch (Exception $e) {
            ErrorLogger::log("Error getting product names by category: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Handle API product requests
     * 
     * @return void Outputs JSON response
     */
    public function handleApiRequest() {
        // Get query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
        $product_id = isset($_GET['id']) ? $_GET['id'] : '';
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        // Only support GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendJsonResponse(405, 'Method Not Allowed');
            return;
        }
        
        try {
            // Handle different actions
            switch ($action) {
                case 'categories':
                    // Get all categories
                    $categories = $this->getCategories();
                    $this->sendJsonResponse(200, '', $categories);
                    break;
                    
                case 'product_names':
                    // Get product names by category
                    if (empty($category)) {
                        $this->sendJsonResponse(400, 'Category parameter is required');
                        return;
                    }
                    
                    $productNames = $this->getProductNamesByCategory($category);
                    $this->sendJsonResponse(200, '', $productNames);
                    break;
                    
                default:
                    // Handle regular product queries
                    if (!empty($product_id)) {
                        // Get specific product
                        $product = $this->getProductById($product_id);
                        
                        if ($product) {
                            $this->sendJsonResponse(200, '', $product);
                        } else {
                            $this->sendJsonResponse(404, 'Product not found');
                        }
                    } 
                    else if (!empty($product_name)) {
                        // Get products by name
                        $products = $this->getProductsByName($product_name);
                        $this->sendJsonResponse(200, '', $products);
                    }
                    else if (!empty($category)) {
                        // Get products by category
                        $products = $this->getProductsByCategory($category);
                        $this->sendJsonResponse(200, '', $products);
                    } 
                    else {
                        // Get all products
                        $products = $this->getAllProducts();
                        $this->sendJsonResponse(200, '', $products);
                    }
                    break;
            }
        } catch (Exception $e) {
            ErrorLogger::log("Product API error: " . $e->getMessage(), "ERROR");
            $this->sendJsonResponse(500, 'Internal server error');
        }
    }
    
    /**
     * Generate SEO-friendly product URL
     * 
     * @param array $product Product data
     * @return string URL
     */
    private function generateProductUrl($product) {
        // Create URL-friendly slug from product name
        $slug = strtolower(str_replace(' ', '-', $product['product_name']));
        
        // Remove any non-alphanumeric characters except hyphens
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // Product detail URL pattern
        return "/product/{$product['product_id']}-{$slug}";
    }
    
    /**
     * Send JSON response
     * 
     * @param int $statusCode HTTP status code
     * @param string $message Message (optional)
     * @param mixed $data Data to send
     * @return void
     */
    private function sendJsonResponse($statusCode, $message = '', $data = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [];
        
        // Add message if provided
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        // If data is provided, include it
        if ($data !== null) {
            // If $data is already an array and has no 'message' key, add directly
            if (is_array($data) && !isset($response['message'])) {
                echo json_encode($data);
                return;
            }
            
            $response['data'] = $data;
        }
        
        echo json_encode($response);
    }
}
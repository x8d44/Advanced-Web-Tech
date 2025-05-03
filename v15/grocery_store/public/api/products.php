<?php
/**
 * products.php - Product API Endpoint
 *
 * Handles product-related requests including listing, filtering by category,
 * and retrieving individual product details. Supports AJAX-based filtering.
 */

header('Content-Type: application/json');

// Include necessary files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

// Initialize controller
$productController = new ProductController();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Process only GET requests
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// Get query parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
$product_id = isset($_GET['id']) ? $_GET['id'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'categories':
        // Get all categories
        $categories = $productController->getCategories();
        echo json_encode($categories);
        break;
        
    case 'product_names':
        // Get product names by category
        if (empty($category)) {
            http_response_code(400);
            echo json_encode(['message' => 'Category parameter is required']);
            exit;
        }
        
        $productNames = $productController->getProductNamesByCategory($category);
        echo json_encode($productNames);
        break;
        
    default:
        // Handle regular product queries
        if (!empty($product_id)) {
            // Get specific product
            $product = $productController->getProductById($product_id);
            
            if ($product) {
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Product not found']);
            }
        } 
        else if (!empty($product_name)) {
            // Get products by name
            $products = $productController->getProductsByName($product_name);
            echo json_encode($products);
        }
        else if (!empty($category)) {
            // Get products by category
            $products = $productController->getProductsByCategory($category);
            echo json_encode($products);
        } 
        else {
            // Get all products
            $products = $productController->getAllProducts();
            echo json_encode($products);
        }
        break;
}
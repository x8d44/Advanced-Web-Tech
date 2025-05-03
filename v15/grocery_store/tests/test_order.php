<?php
// Test script to verify Order class functionality

// Set error reporting for maximum debugging information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/config/errorlogger.php';

echo "=== Order Class Functionality Test ===\n\n";

// Initialize the database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connection established successfully\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Get the first product from the database
try {
    $product = new Product($db);
    $products = $product->read();
    
    if (empty($products)) {
        echo "✗ No products found in the database. Please add products first.\n";
        exit(1);
    }
    
    $test_product = $products[0];
    echo "✓ Found test product: {$test_product['product_name']} (ID: {$test_product['product_id']})\n";
} catch (Exception $e) {
    echo "✗ Error getting product: " . $e->getMessage() . "\n";
    exit(1);
}

// Get or create a test user
try {
    $user = new User($db);
    
    // Try to find an existing user
    $user->email = "test@example.com";
    $existing_user = $user->findByEmail();
    
    if ($existing_user) {
        $test_user_id = $existing_user['user_id'];
        echo "✓ Using existing test user (ID: {$test_user_id})\n";
    } else {
        // Create a test user if none exists
        $user->name = "Test User";
        $user->email = "test@example.com";
        $user->password = password_hash("Test123!", PASSWORD_DEFAULT);
        $user->phone = "1234567890";
        $user->role = "customer";
        
        if ($user->register()) {
            $test_user_id = $user->user_id;
            echo "✓ Created new test user (ID: {$test_user_id})\n";
        } else {
            echo "✗ Failed to create test user\n";
            exit(1);
        }
    }
} catch (Exception $e) {
    echo "✗ Error with test user: " . $e->getMessage() . "\n";
    exit(1);
}

// Create a test order
try {
    $order = new Order($db);
    
    // Set order properties
    $order->user_id = $test_user_id;
    $order->product_id = $test_product['product_id'];
    $order->quantity = 1;
    $order->status = "pending";
    
    echo "\nAttempting to create test order with:\n";
    echo "- User ID: {$order->user_id}\n";
    echo "- Product ID: {$order->product_id}\n";
    echo "- Quantity: {$order->quantity}\n";
    echo "- Status: {$order->status}\n\n";
    
    // Validate product exists before creating order
    $product_check = $product->readOne($order->product_id);
    if (!$product_check) {
        echo "✗ Product ID {$order->product_id} doesn't exist\n";
        exit(1);
    }
    
    // Create order
    if ($order->create()) {
        echo "✓ Test order created successfully! Order ID: {$order->order_id}\n";
        
        // Verify the order exists in the database
        $created_order = $order->readOne($order->order_id);
        if ($created_order) {
            echo "✓ Order verification successful - found in database\n";
        } else {
            echo "✗ Order verification failed - not found in database\n";
        }
    } else {
        echo "✗ Failed to create test order\n";
        
        // Check for any logged errors
        echo "\nPlease check logs for more details.\n";
        echo "The last error message from the Order class was likely caught and logged.\n";
    }
} catch (Exception $e) {
    echo "✗ Error creating order: " . $e->getMessage() . "\n";
    
    // Debug information
    echo "\nDebug Information:\n";
    echo "- Exception: " . get_class($e) . "\n";
    echo "- File: " . $e->getFile() . "\n";
    echo "- Line: " . $e->getLine() . "\n";
    echo "- Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Completed ===\n";


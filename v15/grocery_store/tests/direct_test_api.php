<?php
// Direct API test file - place in public directory
// Include necessary files directly instead of making HTTP requests
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/errorlogger.php';

echo "<h1>Order API Direct Test</h1>";

// Get order ID from URL parameter or find existing order
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Create order model
    $order = new Order($db);
    
    // If no ID specified, try to find one
    if (!$orderId) {
        $orders = $order->read();
        if (!empty($orders)) {
            $orderId = $orders[0]['order_id'];
            echo "<p>Found order ID: $orderId</p>";
        }
    }
    
    if ($orderId) {
        echo "<p>Testing with Order ID: $orderId</p>";
        
        // Get order directly from the database
        $orderData = $order->readOne($orderId);
        
        if ($orderData) {
            // Format order data for display
            echo "<h2>Order Details:</h2>";
            echo "<div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
            echo "<pre>";
            
            // Format response similar to API
            $response = [
                'success' => true,
                'data' => [
                    'order_id' => $orderData['order_id'],
                    'customer' => [
                        'name' => $orderData['user_name'] ?? $orderData['customer_name'],
                        'email' => $orderData['email'] ?? $orderData['customer_email'],
                        'phone' => $orderData['phone'] ?? $orderData['customer_phone']
                    ],
                    'product' => [
                        'id' => $orderData['product_id'],
                        'name' => $orderData['product_name'],
                        'price' => $orderData['price'],
                        'image' => $orderData['image_path']
                    ],
                    'quantity' => $orderData['quantity'],
                    'total' => $orderData['price'] * $orderData['quantity'],
                    'formatted_total' => '£' . number_format($orderData['price'] * $orderData['quantity'], 2),
                    'status' => $orderData['status'],
                    'order_date' => $orderData['order_date']
                ]
            ];
            
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "</pre>";
            echo "</div>";
            
            echo "<p style='color:green'>✓ Successfully retrieved order details directly from the database.</p>";
            echo "<p>This confirms that your Order model and database are working correctly.</p>";
            
            // Show what API response would look like
            echo "<h2>What the API Response Should Look Like:</h2>";
            echo "<p>If the API endpoint is working correctly, it would return the JSON shown above.</p>";
            
            // Show API URL for reference
            $apiUrl = "/api/orders.php?id=$orderId&manager_token=manager123";
            echo "<p>API URL: <code>$apiUrl</code></p>";
            
            // Add a link to try the API directly
            echo "<p><a href='$apiUrl' target='_blank' class='btn btn-primary'>Try API Directly</a></p>";
        } else {
            echo "<p style='color:red'>Order ID $orderId not found in the database.</p>";
        }
    } else {
        echo "<p style='color:orange'>No orders found in the database. Please create an order first.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Simple form to test specific order IDs
echo "<h2>Test Another Order ID</h2>";
echo "<form method='get'>";
echo "<div style='margin-bottom:10px'>";
echo "<label for='id'>Order ID: </label>";
echo "<input type='number' id='id' name='id' min='1' required style='margin-right:10px'>";
echo "<button type='submit'>Test Order ID</button>";
echo "</div>";
echo "</form>";

// Extra debugging information
echo "<h2>Debugging Information</h2>";
echo "<p>This section shows technical details that might help troubleshoot API issues.</p>";

echo "<h3>PHP Version and Settings:</h3>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "</li>";
echo "<li>max_execution_time: " . ini_get('max_execution_time') . " seconds</li>";
echo "</ul>";

echo "<h3>Server Information:</h3>";
echo "<ul>";
foreach ($_SERVER as $key => $value) {
    if (in_array($key, ['SERVER_SOFTWARE', 'SERVER_NAME', 'SERVER_ADDR', 'SERVER_PORT', 'REQUEST_SCHEME'])) {
        echo "<li>$key: " . htmlspecialchars($value) . "</li>";
    }
}
echo "</ul>";
?>
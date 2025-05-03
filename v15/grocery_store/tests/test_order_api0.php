<?php
// File: test_order_api.php - Place in project root for testing
require_once __DIR__ . '/config/config.php';

// Get recent order ID from database for testing
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Order.php';

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);
$orders = $order->read();

// If there are orders, get the most recent one
$testOrderId = !empty($orders) ? $orders[0]['order_id'] : null;

echo "<h1>Order API Test</h1>";

if ($testOrderId) {
    echo "<p>Testing with Order ID: {$testOrderId}</p>";
    
    // Test the API
    $apiUrl = "/api/orders.php?id={$testOrderId}";
    echo "<p>API URL: {$apiUrl}</p>";
    
    // Make the request using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Status Code: {$httpCode}</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p>No orders found for testing. Please create an order first.</p>";
}
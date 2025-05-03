<?php
// This is a diagnostic tool to debug cart functionality
// Place this file in the root directory of your project and access it via browser

// Include required files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/CartController.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/config/database.php';

// Enable detailed error display
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Cart Debugging Tool</h1>";

// Check session status
echo "<h2>Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>Session is active</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    echo "<h3>Session Variables:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>Session is not active</p>";
    session_start();
    echo "<p>Started a new session with ID: " . session_id() . "</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check cart table
echo "<h2>Cart Table Check</h2>";
try {
    $query = "DESCRIBE cart";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<p style='color:green'>✓ Cart table exists</p>";
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check for existing cart items
    $query = "SELECT COUNT(*) as count FROM cart";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Current cart items in database: " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        $query = "SELECT * FROM cart LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        echo "<h3>Sample cart items:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Cart ID</th><th>User ID</th><th>Product ID</th><th>Quantity</th><th>Added Date</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['cart_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['added_date'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Cart table check failed: " . $e->getMessage() . "</p>";
}

// Check product table
echo "<h2>Products Table Check</h2>";
try {
    $query = "SELECT COUNT(*) as count FROM products";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total products: " . $result['count'] . "</p>";
    
    $query = "SELECT * FROM products LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h3>Sample products:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['price']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Products table check failed: " . $e->getMessage() . "</p>";
}

// Test add to cart functionality
echo "<h2>Add to Cart Test</h2>";

// Only run the test if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Get the first product from the database
        $product = new Product($db);
        $products = $product->read();
        
        if (!empty($products)) {
            $testProduct = $products[0];
            echo "<p>Test product: " . htmlspecialchars($testProduct['product_name']) . " (ID: " . htmlspecialchars($testProduct['product_id']) . ")</p>";
            
            // Create cart object
            $cart = new Cart($db);
            $cart->user_id = $_SESSION['user_id'];
            $cart->product_id = $testProduct['product_id'];
            $cart->quantity = 1;
            
            // Try to add to cart
            if ($cart->add()) {
                echo "<p style='color:green'>✓ Test product added to cart successfully!</p>";
                
                // Check if the item is in the cart
                $cartItem = $cart->getCartItem($_SESSION['user_id'], $testProduct['product_id']);
                if ($cartItem) {
                    echo "<p style='color:green'>✓ Product found in cart with quantity: " . htmlspecialchars($cartItem['quantity']) . "</p>";
                } else {
                    echo "<p style='color:red'>✗ Product not found in cart even though add() returned true</p>";
                }
            } else {
                echo "<p style='color:red'>✗ Failed to add test product to cart</p>";
                echo "<p>Check error logs for details</p>";
            }
        } else {
            echo "<p style='color:red'>✗ No products found in the database to test with</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Test failed with error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠ User not logged in, skipping cart test</p>";
    echo "<p>Please log in and then run this test again</p>";
}

// Check cart.js implementation
echo "<h2>JavaScript Implementation Check</h2>";
echo "<p>Check if the 'Add to Cart' button in products.js is properly calling the API:</p>";

echo "<pre>
// Correct implementation should look like:
fetch('/api/cart?action=add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        product_id: productId,
        quantity: 1
    }),
})
</pre>";

echo "<p>Make sure that when products are loaded via AJAX, the event listeners for 'Add to Cart' buttons are properly initialized.</p>";

echo "<h2>Common Issues</h2>";
echo "<ul>";
echo "<li>Event listeners not attached to dynamically created 'Add to Cart' buttons</li>";
echo "<li>AJAX requests not properly formatted</li>";
echo "<li>Session variables not correctly set</li>";
echo "<li>Database connection issues</li>";
echo "<li>Missing error handling in Cart model's add() method</li>";
echo "</ul>";

echo "<h2>Recommended Fixes</h2>";
echo "<ol>";
echo "<li>Update products.js to properly initialize add-to-cart buttons after products are loaded</li>";
echo "<li>Add console logging to track AJAX requests in the browser</li>";
echo "<li>Check server logs for any PHP errors during cart operations</li>";
echo "<li>Verify that the Cart model's add() method is handling existing cart items correctly</li>";
echo "</ol>";
?>
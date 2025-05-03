<?php
// File: test_tables.php
include_once __DIR__ . "/config/database.php";
$database = new Database();
$conn = $database->getConnection();

// Test functions
function testTable($conn, $tableName) {
    try {
        $query = "SELECT * FROM " . $tableName . " LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "✅ Table '" . $tableName . "' exists and is " . ($count > 0 ? "populated" : "empty") . ".<br>";
        return true;
    } catch(PDOException $e) {
        echo "❌ Error with table '" . $tableName . "': " . $e->getMessage() . "<br>";
        return false;
    }
}

// Test each table
echo "<h2>Database Table Test</h2>";
testTable($conn, "products");
testTable($conn, "users");
testTable($conn, "orders");
testTable($conn, "captcha");

// Show sample products
echo "<h3>Sample Products:</h3>";
try {
    $query = "SELECT * FROM products LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Category</th><th>Name</th><th>Price</th><th>Image</th><th>Description</th></tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['price'] . "</td>";
        echo "<td>" . $row['image_path'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch(PDOException $e) {
    echo "Error querying products: " . $e->getMessage();
}

// Test the new status column in orders table
echo "<h3>Testing Orders Table Structure:</h3>";
try {
    // Use DESCRIBE statement to get column information
    $query = "DESCRIBE orders";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasStatusColumn = false;
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
        
        if ($row['Field'] === 'status') {
            $hasStatusColumn = true;
        }
    }
    
    echo "</table>";
    
    if ($hasStatusColumn) {
        echo "<p>✅ Status column exists in the orders table.</p>";
    } else {
        echo "<p>❌ Status column is missing from the orders table.</p>";
    }
    
} catch(PDOException $e) {
    echo "Error testing orders table: " . $e->getMessage();
}
?>
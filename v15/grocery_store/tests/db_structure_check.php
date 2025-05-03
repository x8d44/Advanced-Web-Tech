<?php
// db_structure_check.php
// Place this in your public directory for testing

echo "<h1>Database Structure Verification</h1>";

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color:green'>✓ Database connection successful</p>";
    
    // Check each required table
    $tables = ['products', 'users', 'orders', 'captcha', 'cart'];
    
    foreach ($tables as $table) {
        $query = "DESCRIBE " . $table;
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Table: $table ✓</h2>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Count records
            $query = "SELECT COUNT(*) as count FROM " . $table;
            $stmt = $db->prepare($query);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p>Records in table: $count</p>";
            
            // If it's the products table, show sample data
            if ($table == 'products' && $count > 0) {
                $query = "SELECT * FROM products LIMIT 5";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Sample Products:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Image</th></tr>";
                
                foreach ($products as $product) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['category']) . "</td>";
                    echo "<td>£" . htmlspecialchars(number_format($product['price'], 2)) . "</td>";
                    echo "<td>" . htmlspecialchars($product['image_path']) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>✗ Table '$table' does not exist or cannot be accessed</p>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
<?php
// security_test.php
// Place this in your public directory for testing

echo "<h1>Security Test Suite</h1>";

// Test SQL Injection
echo "<h2>SQL Injection Test</h2>";

// Create an array of SQL injection test cases
$sql_injection_tests = [
    "' OR 1=1 --",
    "'; DROP TABLE users; --",
    "admin' --",
    "' UNION SELECT 1, username, password FROM users --"
];

echo "<h3>Testing Login Form:</h3>";
echo "<ul>";
foreach ($sql_injection_tests as $injection) {
    echo "<li>Testing: " . htmlspecialchars($injection) . "</li>";
    
    // Create form data
    $data = [
        'email' => $injection,
        'password' => 'test',
        'captcha' => 'test'
    ];
    
    // You'd need to properly test these by submitting to the login form
    // This is just a placeholder for manual testing
    echo "<p>Manual test required: Submit '" . htmlspecialchars($injection) . "' to login form</p>";
}
echo "</ul>";

// Test XSS
echo "<h2>XSS Test</h2>";

// Create an array of XSS test cases
$xss_tests = [
    "<script>alert('XSS')</script>",
    "<img src='x' onerror='alert(\"XSS\")'>",
    "<svg onload='alert(\"XSS\")'>",
    "javascript:alert('XSS')"
];

echo "<h3>Testing Form Inputs:</h3>";
echo "<ul>";
foreach ($xss_tests as $xss) {
    echo "<li>Testing: " . htmlspecialchars($xss) . "</li>";
    
    // Create form data
    $data = [
        'name' => $xss,
        'email' => 'test@example.com',
        'phone' => '12345678901',
        'password' => 'Test1234'
    ];
    
    // You'd need to properly test these by submitting to the registration form
    // This is just a placeholder for manual testing
    echo "<p>Manual test required: Submit '" . htmlspecialchars($xss) . "' to registration form</p>";
}
echo "</ul>";

// Test CSRF
echo "<h2>CSRF Protection Test</h2>";
echo "<p>To test CSRF protection:</p>";
echo "<ol>";
echo "<li>Log in to the application</li>";
echo "<li>Try to submit the following form:</li>";
echo "</ol>";

// Generate a simple form that attempts to submit without CSRF token
echo '<form action="/api/cart.php?action=add" method="POST">';
echo '<input type="hidden" name="product_id" value="1">';
echo '<input type="hidden" name="quantity" value="1">';
echo '<input type="submit" value="Test CSRF Protection">';
echo '</form>';

echo "<p>The request should fail with a CSRF token error if protection is working.</p>";

// Session security
echo "<h2>Session Security Test</h2>";
echo "<p>To test session security:</p>";
echo "<ol>";
echo "<li>Log in to the application</li>";
echo "<li>Copy your session cookie</li>";
echo "<li>Log out</li>";
echo "<li>Manually set the session cookie to the copied value</li>";
echo "<li>Refresh the page</li>";
echo "</ol>";

echo "<p>You should still be logged out if session security is properly implemented.</p>";
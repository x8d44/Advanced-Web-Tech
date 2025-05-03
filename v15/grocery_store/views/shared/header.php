<?php
// Calculate the base path dynamically
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// Ensure base_path is not empty if script is at root, though unlikely here
if ($base_path === '') {
    $base_path = '/';
} else {
    // Add trailing slash if it's not the root
     $base_path .= '/';
}

/**
 * Header Template for Grocery Store Web Application
 *
 * Renders the top navigation, handles user session management,
 * and provides dynamic cart functionality across the application.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Initialize cart data early to reduce redundant database calls
function initializeCartData() {
    static $cartData = null;
    
    if ($cartData === null && isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../../controllers/CartController.php';
        $cartController = new CartController();
        $cartData = $cartController->getUserCart($_SESSION['user_id']);
        $_SESSION['cart_count'] = $cartData['item_count'] ?? 0;
    }
    
    return $cartData ?? ['items' => [], 'item_count' => 0];
}

// Get current page path for active navigation highlighting
function getCurrentPagePath() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    $path = trim(str_replace($base_path, '', $request_uri), '/');
    return str_replace('.php', '', $path);
}

// Fetch cart data if user is logged in
$cart_count = 0;
$cart_items = [];
if (isset($_SESSION['user_id'])) {
    $cartData = initializeCartData();
    $cart_count = $cartData['item_count'];
    $cart_items = $cartData['items'];
}

// Current page path for navigation
$current_path = getCurrentPagePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Security Headers -->
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <!-- SEO Optimization -->
    <meta name="description" content="Fresh groceries delivered to your door. Shop for vegetables and meat products online.">
    <meta name="keywords" content="grocery, online shopping, vegetables, meat, fresh produce, delivery">
    <meta name="author" content="Grocery Store">
    
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle . ' - ' . APP_NAME) : APP_NAME ?></title>
    
    <!-- Performance Optimized Asset Loading -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <!-- CSS Resources -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    
    <!-- Security: Generate CSRF Token for JavaScript -->
    <script>
        window.csrfToken = "<?= generate_csrf_token() ?>";
    </script>
<!-- Make the base URL available to JavaScript -->
    <script>
      // Make the base URL available to JavaScript
      window.APP_URL = "<?php echo rtrim(APP_URL, '/'); ?>";
    </script>
</head>
<body class="<?= isset($_SESSION['user_id']) ? 'logged-in' : '' ?>">
    <!-- Optimized Navigation with Enhanced Accessibility -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success" aria-label="Main Navigation">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>" aria-label="Home">
                <i class="fas fa-shopping-basket me-2" aria-hidden="true"></i>
                <?= htmlspecialchars(APP_NAME) ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Left Navigation Menu -->
                <ul class="navbar-nav me-auto mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_path === '' ? 'active' : '' ?>" href="<?php echo $base_path; ?>">
                            <i class="fas fa-home me-1" aria-hidden="true"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_path === 'products' ? 'active' : '' ?>" href="<?php echo $base_path; ?>products">
                            <i class="fas fa-apple-alt me-1" aria-hidden="true"></i> Products
                        </a>
                    </li>
                </ul>
                
                <!-- Right Navigation Menu -->
                <ul class="navbar-nav ms-auto mb-lg-0 align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Cart Dropdown -->
                        <li class="nav-item dropdown cart-dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="cartDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count-mobile <?= $cart_count === 0 ? 'd-none' : '' ?>" id="cart-count">
                                    <?= $cart_count ?>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="cartDropdown" style="width: 320px;">
                                <div class="card border-0">
                                    <div class="card-header cart-preview-header bg-success text-white py-2">
                                        <h6 class="m-0">Your Cart (<?= $cart_count ?> items)</h6>
                                    </div>
                                    <div class="card-body cart-preview-body p-2" style="max-height: 280px; overflow-y: auto;">
                                        <?php if (empty($cart_items)): ?>
                                            <div class="text-center p-3">
                                                <p class="text-muted mb-2">Your cart is empty</p>
                                                <a href="<?php echo $base_path; ?>products" class="btn btn-sm btn-outline-success">Start Shopping</a>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach(array_slice($cart_items, 0, 3) as $item): ?>
                                                <div class="cart-preview-item d-flex align-items-center mb-2 p-1 border-bottom">
                                                    <img src="<?php echo $base_path; ?>images/products/<?= htmlspecialchars($item['image_path']) ?>"
                                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                        class="cart-preview-img me-2" width="40" height="40">
                                                    <div class="cart-preview-details">
                                                        <p class="m-0 small"><?= htmlspecialchars($item['product_name']) ?></p>
                                                        <small class="text-muted"><?= htmlspecialchars($item['quantity']) ?> × <?= htmlspecialchars($item['formatted_price']) ?></small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php if (count($cart_items) > 3): ?>
                                                <div class="text-center my-1">
                                                    <small class="text-muted">and <?= count($cart_items) - 3 ?> more items...</small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer cart-preview-footer bg-light p-2">
                                        <a href="<?php echo $base_path; ?>cart" class="btn btn-success btn-sm w-100">
                                            <?= empty($cart_items) ? 'Go to Cart' : 'View Cart' ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1" aria-hidden="true"></i>
                                <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_path; ?>customer/dashboard">
                                        <i class="fas fa-tachometer-alt me-2" aria-hidden="true"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_path; ?>customer/edit_profile">
                                        <i class="fas fa-user-edit me-2" aria-hidden="true"></i>Edit Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $base_path; ?>logout">
                                        <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>login" class="btn btn-outline-light me-2">
                                <i class="fas fa-sign-in-alt me-1" aria-hidden="true"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>register" class="btn btn-light">
                                <i class="fas fa-user-plus me-1" aria-hidden="true"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="container mt-4">
        <?php
        // Render flash messages with enhanced security
        if (isset($_SESSION['flash_message'])) {
            $message = htmlspecialchars($_SESSION['flash_message']);
            $type = htmlspecialchars($_SESSION['flash_type'] ?? 'success');
            echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
            echo $message;
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
            echo "</div>";
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        ?>
<?php
/**
 * Cart Page - User Shopping Cart Management
 * 
 * Handles display and interaction with user's shopping cart,
 * providing a comprehensive view of selected products.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title
$pageTitle = "Shopping Cart";

// Include header
require_once __DIR__ . '/shared/header.php';

// Security: Redirect unauthenticated users
if (!isset($_SESSION['user_id'])) {
    // Enhanced flash message with security context
    $_SESSION['flash_message'] = "Please login to view your cart";
    $_SESSION['flash_type'] = "warning";
    
    // Secure redirect
    header('Location: /login');
    exit;
}

// Include cart controller with error handling
try {
    require_once __DIR__ . '/../controllers/CartController.php';
    
    // Initialize cart data retrieval
    $cartController = new CartController();
    $cartData = $cartController->getUserCart($_SESSION['user_id']);
} catch (Exception $e) {
    // Log error for debugging
    error_log("Cart Page Error: " . $e->getMessage());
    
    // User-friendly error handling
    $_SESSION['flash_message'] = "An error occurred while loading your cart. Please try again.";
    $_SESSION['flash_type'] = "danger";
    header('Location: /products');
    exit;
}

// Optimize cart data processing
$cartItems = $cartData['items'] ?? [];
$cartTotal = $cartData['formatted_total'] ?? '£0.00';
$itemCount = $cartData['item_count'] ?? 0;
?>

<div class="card mb-4 cart-page">
    <div class="card-header bg-success text-white">
        <h5 class="m-0">Shopping Cart (<?= $itemCount ?> Items)</h5>
    </div>
    <div class="card-body p-0">
        <!-- Empty Cart Message with Accessibility -->
        <div id="empty-cart-message" role="alert" 
             class="<?= empty($cartItems) ? 'alert alert-info m-3' : 'd-none' ?>">
            <h4 class="alert-heading">Your cart is empty!</h4>
            <p>You haven't added any products to your cart yet.</p>
            <hr>
            <p class="mb-0">
                <a href="/products" class="alert-link">Browse our products</a> to start shopping.
            </p>
        </div>

        <!-- Cart Content with Responsive Design -->
        <div id="cart-content" class="<?= empty($cartItems) ? 'd-none' : '' ?>">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <tr data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/images/products/<?= htmlspecialchars($item['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                             class="cart-item-image me-2">
                                        <div>
                                            <h6 class="m-0"><?= htmlspecialchars($item['product_name']) ?></h6>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($item['category']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['formatted_price']) ?></td>
                                <td>
                                    <div class="quantity-control">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary btn-decrement" type="button" 
                                                    aria-label="Decrease Quantity">
                                                <i class="fas fa-minus" aria-hidden="true"></i>
                                            </button>
                                            <input type="number" 
                                                   class="form-control text-center cart-quantity" 
                                                   value="<?= htmlspecialchars($item['quantity']) ?>" 
                                                   min="1" 
                                                   data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>">
                                            <button class="btn btn-outline-secondary btn-increment" type="button" 
                                                    aria-label="Increase Quantity">
                                                <i class="fas fa-plus" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?= htmlspecialchars($item['formatted_total']) ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger remove-item" 
                                            data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>"
                                            aria-label="Remove Item">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end" id="cart-total">
                                <?= htmlspecialchars($cartTotal) ?>
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between">
            <a href="/products" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2" aria-hidden="true"></i>Continue Shopping
            </a>
            <button id="checkout-button" class="btn btn-success" 
                    aria-label="Proceed to Checkout">
                Proceed to Checkout
                <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</div>

<!-- Performance Optimized Script Loading -->
<script src="/js/cart.js" defer></script>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
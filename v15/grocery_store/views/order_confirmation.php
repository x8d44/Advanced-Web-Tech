<?php
/**
 * Order Confirmation Page for Grocery Store Web Application
 * 
 * Displays detailed order information after successful purchase,
 * providing customers with a summary of their recent order.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title securely
$pageTitle = htmlspecialchars("Order Confirmation");

// Include header
require_once __DIR__ . '/shared/header.php';

// Security: Check user authentication
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users
    $_SESSION['flash_message'] = "Please login to view your orders";
    $_SESSION['flash_type'] = "warning";
    
    header('Location: /login');
    exit;
}

// Retrieve order ID with input validation
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Error handling for order retrieval
try {
    // Include order controller
    require_once __DIR__ . '/../controllers/OrderController.php';

    // Initialize order controller
    $orderController = new OrderController();

    // Retrieve order details with error checking
    $order = $order_id ? $orderController->getOrderById($order_id) : null;

    // Additional security check
    if (!$order || $order['user_id'] != $_SESSION['user_id']) {
        throw new Exception("Order not found or access denied");
    }

    // Debug logging
    error_log("Order Confirmation - Order ID: $order_id");
    error_log("Order Items Count: " . count($order['items'] ?? []));

} catch (Exception $e) {
    // Log error details
    error_log("Order Confirmation Error: " . $e->getMessage());
    
    // User-friendly error handling
    $_SESSION['flash_message'] = "Unable to retrieve order details. Please try again.";
    $_SESSION['flash_type'] = "danger";
    
    header('Location: /customer/dashboard');
    exit;
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card mt-4 mb-4">
            <div class="card-header bg-success text-white">
                <h1 class="m-0 h3">Order Confirmation</h1>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle text-success fa-5x mb-3" aria-hidden="true"></i>
                    <h2 class="mb-3">Thank You for Your Order!</h2>
                    <p class="lead">Your order has been placed successfully.</p>
                </div>
                
                <div class="alert alert-info">
                    <h3 class="h5">Order Details</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                <strong>Order ID:</strong> 
                                <?= htmlspecialchars($order['order_id']) ?>
                            </p>
                            <p>
                                <strong>Date:</strong> 
                                <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($order['order_date']))) ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p>
                                <strong>Status:</strong> 
                                <span class="badge bg-info">
                                    <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <h3 class="h4 mb-3">Order Items</h3>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($order['items'] as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/images/products/<?= htmlspecialchars($item['image_path']) ?>" 
                                                 alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                 width="50" class="me-3">
                                            <span><?= htmlspecialchars($item['product_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>£<?= number_format($item['price'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td class="text-end">£<?= number_format($item_total, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th colspan="3" class="text-end">Order Total:</th>
                                <th class="text-end"><?= htmlspecialchars($order['formatted_total']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <h3 class="h4 mb-3">Customer Information</h3>
                <div class="row">
                    <div class="col-md-6">
                        <p>
                            <strong>Name:</strong> 
                            <?= htmlspecialchars($order['customer_name'] ?? $order['user_name']) ?>
                        </p>
                        <p>
                            <strong>Email:</strong> 
                            <?= htmlspecialchars($order['email']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p>
                            <strong>Phone:</strong> 
                            <?= htmlspecialchars($order['phone']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="/products" class="btn btn-outline-secondary">
                        <i class="fas fa-shopping-cart me-2" aria-hidden="true"></i>Continue Shopping
                    </a>
                    <a href="/customer/dashboard" class="btn btn-primary">
                        <i class="fas fa-user me-2" aria-hidden="true"></i>My Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
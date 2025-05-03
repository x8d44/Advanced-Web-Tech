<?php
/**
 * Customer Dashboard Page for Grocery Store Web Application
 * 
 * Provides users with an overview of their account information
 * and order history with comprehensive management features.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title securely
$pageTitle = htmlspecialchars("My Account");

// Include header
require_once __DIR__ . '/../shared/header.php';

// Security: Authenticate user
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users
    $_SESSION['flash_message'] = "Please login to access your account";
    $_SESSION['flash_type'] = "warning";
    
    header('Location: /login');
    exit;
}

// Error handling for order retrieval
try {
    // Include controllers
    require_once __DIR__ . '/../../controllers/OrderController.php';

    // Initialize order controller
    $orderController = new OrderController();

    // Retrieve user's orders
    $orders = $orderController->getOrdersByUser($_SESSION['user_id']);

} catch (Exception $e) {
    // Log error for debugging
    error_log("Dashboard Page Error: " . $e->getMessage());
    
    // Fallback to empty orders array
    $orders = [];
    
    $_SESSION['flash_message'] = "Unable to retrieve order history. Please try again later.";
    $_SESSION['flash_type'] = "warning";
}

// Prepare status color mapping for better UX
$statusColorMap = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h2 class="m-0 h5">Account Information</h2>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-circle fa-4x text-muted mb-3"></i>
                    <h3 class="h4 mb-2">
                        <?= htmlspecialchars($_SESSION['name']) ?>
                    </h3>
                    <p class="text-muted">
                        <?= htmlspecialchars($_SESSION['email']) ?>
                    </p>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="/customer/edit_profile" class="btn btn-outline-primary">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </a>
                    <a href="/logout" class="btn btn-outline-danger">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h2 class="m-0 h5">Order History</h2>
                <a href="/cart" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-shopping-cart me-1"></i> View Cart
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info m-3">
                        <p class="mb-0">
                            You haven't placed any orders yet. 
                            <a href="/products" class="alert-link">Browse products</a> to start shopping.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order</th>
                                    <th>Products</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= htmlspecialchars($order['order_id']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($order['order_date'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php foreach($order['items'] as $index => $item): ?>
                                                <div class="d-flex align-items-center 
                                                    <?= $index > 0 ? 'mt-2 border-top pt-2' : '' ?>">
                                                    <img src="/images/products/<?= htmlspecialchars($item['image_path']) ?>" 
                                                        alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                        class="img-thumbnail me-2" 
                                                        style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <?= htmlspecialchars($item['product_name']) ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Qty: <?= htmlspecialchars($item['quantity']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><?= htmlspecialchars($order['formatted_total']) ?></td>
                                        <td>
                                            <?php 
                                            $status = $order['status'];
                                            $color = $statusColorMap[$status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                <?= ucfirst(htmlspecialchars($status)) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="/order_confirmation?id=<?= htmlspecialchars($order['order_id']) ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once __DIR__ . '/../shared/footer.php';
?>
<?php
/**
 * Products Page for Grocery Store Web Application
 * 
 * Manages product browsing, filtering, and display 
 * with dynamic AJAX-driven interactions.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title securely
$pageTitle = htmlspecialchars("Products");

// Include header
require_once __DIR__ . '/shared/header.php';

// Include controllers with error handling
try {
    require_once __DIR__ . '/../controllers/ProductController.php';

    // Initialize product controller
    $productController = new ProductController();

    // Get categories for dropdown with error fallback
    $categories = $productController->getCategories() ?: ['Vegetables', 'Meat'];

} catch (Exception $e) {
    // Log error for debugging
    error_log("Products Page Error: " . $e->getMessage());
    
    // Fallback categories
    $categories = ['Vegetables', 'Meat'];
}

// Prepare filter parameters with security
$selected_category = isset($_GET['category']) 
    ? htmlspecialchars(trim($_GET['category'])) 
    : '';
$selected_product = isset($_GET['product_name']) 
    ? htmlspecialchars(trim($_GET['product_name'])) 
    : '';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4" aria-label="Our Grocery Products">Our Products</h1>
        
        <!-- Category Selection Form with Enhanced Accessibility -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="category-filter-form" 
                      class="row g-3 align-items-end" 
                      aria-label="Product Filter Options">
                    <div class="col-md-5">
                        <label for="category" class="form-label">Category:</label>
                        <select id="category" 
                                name="category" 
                                class="form-select" 
                                aria-describedby="category-help">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" 
                                        <?= $selected_category == $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="category-help" class="form-text text-muted">
                            Select a category to filter products
                        </small>
                    </div>
                    <div class="col-md-5">
                        <label for="product_name" class="form-label">Product:</label>
                        <select id="product_name" 
                                name="product_name" 
                                class="form-select" 
                                <?= empty($selected_category) ? 'disabled' : '' ?>
                                aria-describedby="product-help">
                            <option value="">All Products</option>
                            <?php if(!empty($selected_category)): 
                                $productNames = $productController->getProductNamesByCategory($selected_category);
                                foreach($productNames as $productName): ?>
                                    <option value="<?= htmlspecialchars($productName) ?>" 
                                            <?= $selected_product == $productName ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($productName) ?>
                                    </option>
                                <?php endforeach;
                            endif; ?>
                        </select>
                        <small id="product-help" class="form-text text-muted">
                            Select a specific product within the category
                        </small>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" 
                                class="btn btn-success w-100" 
                                aria-label="Apply Product Filters">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div id="products-container" class="row" aria-live="polite">
            <!-- Products will be loaded here via AJAX -->
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading products...</span>
                </div>
                <p class="mt-2">Loading products...</p>
            </div>
        </div>
    </div>
</div>

<!-- Performance Optimized Script Loading -->
<script src="<?php echo rtrim(APP_URL, '/'); ?>/js/products.js" defer></script>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
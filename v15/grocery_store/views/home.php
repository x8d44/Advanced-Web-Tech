<?php
/**
 * Home Page for Grocery Store Web Application
 * 
 * Serves as the landing page, showcasing key features, 
 * product categories, and value propositions.
 * 
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title with security
$pageTitle = htmlspecialchars("Welcome");

// Include header
require_once __DIR__ . '/shared/header.php';

// Prepare featured categories with error handling
try {
    require_once __DIR__ . '/../controllers/ProductController.php';
    $productController = new ProductController();
    $categories = $productController->getCategories();
} catch (Exception $e) {
    // Log error for debugging
    error_log("Home Page Category Fetch Error: " . $e->getMessage());
    $categories = ['Vegetables', 'Meat']; // Fallback categories
}

// Define page sections as a structured array for easier maintenance
$pageSections = [
    'hero' => [
        'title' => 'Fresh Groceries Delivered to Your Door',
        'description' => 'Browse our selection of fresh vegetables and quality meat products.',
        'cta_text' => 'Shop Now',
        'cta_link' => '/products'
    ],
    'categories' => [
        ['name' => 'Vegetables', 'description' => 'Fresh, organic vegetables sourced from local farms.'],
        ['name' => 'Meat', 'description' => 'Premium quality meat products for your family.']
    ],
    'features' => [
        [
            'icon' => 'truck', 
            'title' => 'Fast Delivery', 
            'description' => 'We deliver your groceries within 24 hours of ordering.'
        ],
        [
            'icon' => 'leaf', 
            'title' => 'Fresh Products', 
            'description' => 'All our products are fresh and sourced from local providers.'
        ],
        [
            'icon' => 'pound-sign', 
            'title' => 'Best Prices', 
            'description' => 'We offer competitive prices for all our grocery products.'
        ]
    ]
];
?>

<!-- Hero Section with Accessibility and Performance Optimization -->
<div class="row mb-5">
    <div class="col-md-12">
        <div class="p-5 mb-4 bg-light rounded-3 hero-section" role="banner">
            <div class="container-fluid py-5">
                <h1 class="display-5 fw-bold" aria-level="1">
                    <?= htmlspecialchars($pageSections['hero']['title']) ?>
                </h1>
                <p class="col-md-8 fs-4">
                    <?= htmlspecialchars($pageSections['hero']['description']) ?>
                </p>
                <a href="<?= htmlspecialchars($pageSections['hero']['cta_link']) ?>" 
                   class="btn btn-success btn-lg" 
                   aria-label="<?= htmlspecialchars($pageSections['hero']['cta_text']) ?>">
                    <?= htmlspecialchars($pageSections['hero']['cta_text']) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Featured Categories Section -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Featured Categories</h2>
    </div>
    <?php foreach ($pageSections['categories'] as $category): ?>
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <img src="/images/products/<?= strtolower($category['name']) ?>-category.jpg" 
                     alt="<?= htmlspecialchars($category['name']) ?> Category" 
                     class="card-img-top category-img" 
                     loading="lazy">
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($category['name']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($category['description']) ?></p>
                    <a href="/products?category=<?= urlencode($category['name']) ?>" 
                       class="btn btn-outline-success" 
                       aria-label="Browse <?= htmlspecialchars($category['name']) ?>">
                        Browse <?= htmlspecialchars($category['name']) ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Why Choose Us Section -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Why Choose Us</h2>
    </div>
    <?php foreach ($pageSections['features'] as $feature): ?>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-<?= htmlspecialchars($feature['icon']) ?> fa-3x mb-3 text-success" 
                   aria-hidden="true"></i>
                <h3><?= htmlspecialchars($feature['title']) ?></h3>
                <p><?= htmlspecialchars($feature['description']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
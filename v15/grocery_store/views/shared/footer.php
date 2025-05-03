</div>
    
    <!-- Footer with Performance and Accessibility Optimization -->
    <footer class="bg-dark text-white mt-5 py-4" aria-label="Site Footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Your trusted grocery store providing fresh vegetables and quality meat products.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <?php
                        // Dynamic link generation with security and performance
                        $quickLinks = [
                            ['url' => '/', 'label' => 'Home'],
                            ['url' => '/products', 'label' => 'Products'],
                            ['url' => '/register', 'label' => 'Register'],
                            ['url' => '/login', 'label' => 'Login']
                        ];
                        
                        foreach ($quickLinks as $link) {
                            printf(
                                '<li><a href="%s" class="text-white" aria-label="%s">%s</a></li>',
                                htmlspecialchars($link['url']),
                                htmlspecialchars($link['label']),
                                htmlspecialchars($link['label'])
                            );
                        }
                        ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i> 123 Grocery St, Keele<br>
                        <i class="fas fa-phone me-2" aria-hidden="true"></i> (123) 456-7890<br>
                        <i class="fas fa-envelope me-2" aria-hidden="true"></i> info@grocerystore.com
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Performance Optimized Script Loading -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="/js/main.js" defer></script>
    
    <script>
    // Accessibility and Mobile Handling
    document.addEventListener('DOMContentLoaded', function() {
        const cartLink = document.getElementById('cartDropdown');
        if (cartLink) {
            cartLink.addEventListener('click', function(e) {
                if (window.innerWidth < 992) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = '/cart';
                }
            });
        }
    });
    </script>
</body>
</html>
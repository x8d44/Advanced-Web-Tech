/**
 * products.js - Product Display and Filtering
 *
 * Handles product-related functionality including category filtering,
 * product display, "Add to Cart" and "Buy Now" operations.
 */

document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    const productNameSelect = document.getElementById('product_name');
    const productsContainer = document.getElementById('products-container');
    
    // Function to load products
    function loadProducts(category = '', productName = '') {
        // Show loading indicator
        productsContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading products...</p>
            </div>
        `;
        
        // Prepare URL
        let url = window.APP_URL + '/api/products.php';
        const params = new URLSearchParams();
        
        if (category) {
            params.append('category', category);
        }
        
        if (productName) {
            params.append('product_name', productName);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        // Fetch products
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Clear loading indicator
                productsContainer.innerHTML = '';
                
                if (data.length === 0) {
                    productsContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-info">
                                No products found. Please try a different category.
                            </div>
                        </div>
                    `;
                    return;
                }
                
                // Create product cards
                data.forEach(product => {
                    const productCard = `
                        <div class="col-lg-4 col-md-6 col-12 mb-4">
                            <div class="card product-card h-100">
                                <img src="${window.APP_URL}/images/products/${product.image_path}"
                                    class="card-img-top product-image" 
                                    alt="${product.product_name}">
                                <div class="card-body">
                                    <h5 class="card-title">${product.product_name}</h5>
                                    <p class="card-text">${product.description || 'No description available'}</p>
                                    <p class="product-price">£${parseFloat(product.price).toFixed(2)}</p>
                                </div>
                                <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center bg-transparent border-top-0">
                                    <button class="btn btn-success add-to-cart mb-2 mb-md-0 me-md-2" 
                                        data-product-id="${product.product_id}">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                    <button class="btn btn-outline-success buy-now" 
                                        data-product-id="${product.product_id}">
                                        <i class="fas fa-bolt"></i> Buy Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    productsContainer.innerHTML += productCard;
                });
                
                // Initialize add to cart and buy now buttons
                initAddToCartButtons();
                initBuyNowButtons();
            })
            .catch(error => {
                console.error('Error loading products:', error);
                productsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            Error loading products. Please try again later.
                        </div>
                    </div>
                `;
            });
    }
    
    // Function to initialize add to cart buttons
    function initAddToCartButtons() {
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        if (addToCartButtons.length === 0) {
            console.log('No add to cart buttons found');
            return;
        }
        
        console.log(`Initializing ${addToCartButtons.length} add to cart buttons`);
        
        addToCartButtons.forEach(button => {
            // Remove existing event listeners to avoid duplicates
            button.replaceWith(button.cloneNode(true));
        });
        
        // Get fresh references after cloning
        const freshButtons = document.querySelectorAll('.add-to-cart');
        
        freshButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if user is logged in
                const isLoggedIn = document.body.classList.contains('logged-in');
                
                if (!isLoggedIn) {
                    // If not logged in, redirect to login
                    alert('Please login to add items to your cart');
                    window.location.href = window.APP_URL + '/login';
                    return;
                }
                
                // Add visual feedback
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                this.disabled = true;
                
                const productId = this.dataset.productId;
                
                // Send AJAX request
                fetch(window.APP_URL + '/api/cart.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1,
                        csrf_token: window.csrfToken
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Add to cart response:', data);
                    
                    if (data.success) {
                        // Update cart count
                        updateCartCount(data.cart_count);
                        
                        // Show success feedback
                        this.innerHTML = '<i class="fas fa-check"></i> Added';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-success');
                        
                        // Show success toast
                        showToast(`${data.product} added to cart!`, 'success');
                        
                        // Update cart preview with the returned cart data
                        if (data.cart) {
                            updateCartPreview(data.cart);
                        }
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                            this.classList.remove('btn-outline-success');
                            this.classList.add('btn-success');
                            this.disabled = false;
                        }, 2000);
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Show error message
                        this.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                        
                        // Show error toast
                        showToast(data.message || 'Error adding to cart', 'danger');
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                            this.disabled = false;
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Reset button with error indicator
                    this.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                    
                    // Show error toast
                    showToast('Network error. Please try again.', 'danger');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                        this.disabled = false;
                    }, 2000);
                });
            });
        });
    }
    
    function initBuyNowButtons() {
        const buyNowButtons = document.querySelectorAll('.buy-now');
        
        if (buyNowButtons.length === 0) {
            console.log('No buy now buttons found');
            return;
        }
        
        console.log(`Initializing ${buyNowButtons.length} buy now buttons`);
        
        buyNowButtons.forEach(button => {
            // Remove existing event listeners to avoid duplicates
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if user is logged in
                const isLoggedIn = document.body.classList.contains('logged-in');
                
                if (!isLoggedIn) {
                    // If not logged in, redirect to login
                    alert('Please login to place an order');
                    window.location.href = window.APP_URL + '/login';
                    return;
                }
                
                const productId = this.getAttribute('data-product-id');
                console.log(`Buy Now clicked for product ID: ${productId}`);
                
                // Add visual feedback
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Log the data being sent
                const requestData = {
                    product_id: productId,
                    quantity: 1,
                    csrf_token: window.csrfToken
                };
                
                console.log("Sending Buy Now request:", requestData);
                
                // Send AJAX request to place order directly
                fetch(window.APP_URL + '/api/orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Order response:', data);
                    
                    if (data.success) {
                        // Show success toast
                        showToast('Order placed successfully!', 'success');
                        
                        // Redirect to order confirmation page
                        setTimeout(() => {
                            window.location.href = `${window.APP_URL}/order_confirmation?id=${data.order_id}`;
                        }, 1500);
                    } else {
                        // Show error message
                        this.innerHTML = '<i class="fas fa-bolt"></i> Buy Now';
                        this.disabled = false;
                        
                        showToast(data.message || 'Failed to place order', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Reset button
                    this.innerHTML = '<i class="fas fa-bolt"></i> Buy Now';
                    this.disabled = false;
                    
                    showToast('An error occurred. Please try again.', 'danger');
                });
            });
        });
    }

    // Update product options when category changes
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            
            // Reset product name select
            productNameSelect.innerHTML = '<option value="">All Products</option>';
            
            if (selectedCategory) {
                // Enable product name select
                productNameSelect.disabled = false;
                
                // Fetch product names for this category
                fetch(`${window.APP_URL}/api/products.php?action=product_names&category=${encodeURIComponent(selectedCategory)}`)
                    .then(response => response.json())
                    .then(productNames => {
                        productNames.forEach(productName => {
                            const option = document.createElement('option');
                            option.value = productName;
                            option.textContent = productName;
                            productNameSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching product names:', error);
                    });
            } else {
                // Disable product name select if no category selected
                productNameSelect.disabled = true;
            }
        });
    }
    
    // Handle form submission
    const filterForm = document.getElementById('category-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const category = categorySelect.value;
            const productName = productNameSelect.value;
            
            // Update URL for bookmarkability without reloading page
            const url = new URL(window.location);
            
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            
            if (productName) {
                url.searchParams.set('product_name', productName);
            } else {
                url.searchParams.delete('product_name');
            }
            
            window.history.pushState({}, '', url);
            
            // Load filtered products
            loadProducts(category, productName);
        });
    }
    
    // Initial load of products
    const urlParams = new URLSearchParams(window.location.search);
    loadProducts(
        urlParams.get('category') || '',
        urlParams.get('product_name') || ''
    );
    
    // Helper functions for cart updates
    
    /**
     * Update cart count in header
     */
    function updateCartCount(count) {
        const cartCountBadge = document.getElementById('cart-count');
        
        if (cartCountBadge) {
            cartCountBadge.textContent = count;
            
            // Show or hide badge based on count
            if (count > 0) {
                cartCountBadge.classList.remove('d-none');
            } else {
                cartCountBadge.classList.add('d-none');
            }
        }
    }
    
    /**
     * Update cart preview dropdown
     */
    function updateCartPreview(cartData) {
        // Look for cart preview elements
        const cartPreviewBody = document.querySelector('.cart-preview-body');
        const cartPreviewHeader = document.querySelector('.cart-preview-header h6');
        const cartPreviewFooterBtn = document.querySelector('.cart-preview-footer .btn');
        
        if (cartPreviewBody) {
            cartPreviewBody.innerHTML = '';
            
            if (cartData.items.length === 0) {
                cartPreviewBody.innerHTML = `
                    <div class="text-center p-3">
                        <p class="text-muted mb-2">Your cart is empty</p>
                        <a href="${window.APP_URL}/products" class="btn btn-sm btn-outline-success">Start Shopping</a>
                    </div>
                `;
                
                // Update header
                if (cartPreviewHeader) {
                    cartPreviewHeader.textContent = 'Your Cart (0 items)';
                }
                
                // Update footer button
                if (cartPreviewFooterBtn) {
                    cartPreviewFooterBtn.textContent = 'Go to Cart';
                    cartPreviewFooterBtn.href = window.APP_URL + '/cart';
                }
                
                return;
            }
            
            // Show up to 3 items
            const itemsToShow = cartData.items.slice(0, 3);
            
            // Add items to preview
            itemsToShow.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'cart-preview-item d-flex align-items-center mb-2 p-1 border-bottom';
                
                itemElement.innerHTML = `
                    <img src="${window.APP_URL}/images/products/${item.image_path}"
                         alt="${item.product_name}"
                         class="cart-preview-img me-2" width="40" height="40">
                    <div class="cart-preview-details">
                        <p class="m-0 small">${item.product_name}</p>
                        <small class="text-muted">${item.quantity} × ${item.formatted_price}</small>
                    </div>
                `;
                
                cartPreviewBody.appendChild(itemElement);
            });
            
            // Show "and X more items" if there are more than 3 items
            if (cartData.items.length > 3) {
                const moreItems = document.createElement('div');
                moreItems.className = 'text-center my-1';
                moreItems.innerHTML = `
                    <small class="text-muted">and ${cartData.items.length - 3} more items...</small>
                `;
                cartPreviewBody.appendChild(moreItems);
            }
            
            // Update header with item count
            if (cartPreviewHeader) {
                cartPreviewHeader.textContent = `Your Cart (${cartData.item_count} items)`;
            }
            
            // Ensure footer button says "View Cart"
            if (cartPreviewFooterBtn) {
                cartPreviewFooterBtn.textContent = 'View Cart';
                cartPreviewFooterBtn.href = window.APP_URL + '/cart';
            }
        }
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        try {
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast
            const toastId = 'toast-' + Date.now();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.id = toastId;
            
            // Ensure message is a string
            const safeMessage = String(message || 'An operation completed');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${safeMessage}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            // Add toast to container
            toastContainer.appendChild(toast);
            
            // Initialize and show toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 3000
            });
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        } catch (error) {
            console.error('Error showing toast:', error);
            // Fallback to alert for critical messages
            if (type === 'danger') {
                alert(message || 'An error occurred');
            }
        }
    }
});
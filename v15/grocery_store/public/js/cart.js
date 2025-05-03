/**
 * cart.js - Shopping Cart Management
 *
 * Handles cart operations including adding, updating, removing items, 
 * and the checkout process. Manages cart UI updates and animations.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all cart functionality
    initAddToCartButtons();
    initQuantityHandlers();
    initRemoveItemHandlers();
    initCheckoutButton();
    initCartPreview();
});

/**
 * Initialize cart preview dropdown functionality
 */
function initCartPreview() {
    // Get cart dropdown element
    const cartDropdown = document.querySelector('.cart-dropdown');
    
    if (cartDropdown) {
        const cartDropdownToggle = cartDropdown.querySelector('#cartDropdown');
        const dropdownMenu = cartDropdown.querySelector('.dropdown-menu');
        
        // Reinitialize dropdown if it fails
        function safeDropdownInitialize() {
            try {
                // Force dropdown close
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
                
                // Remove any existing event listeners
                ['click', 'keydown'].forEach(event => {
                    cartDropdownToggle.removeEventListener(event, handleDropdownToggle);
                });
                
                // Add new event listeners
                cartDropdownToggle.addEventListener('click', handleDropdownToggle);
                
                // Prevent dropdown from closing when clicking inside
                dropdownMenu.addEventListener('click', function(e) {
                    // Only prevent default if not clicking the View Cart button
                    if (!e.target.closest('.cart-preview-footer .btn')) {
                        e.stopPropagation();
                    }
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!cartDropdown.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                        cartDropdownToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            } catch (error) {
                console.error('Error initializing cart dropdown:', error);
            }
        }
        
        // Dropdown toggle handler
        function handleDropdownToggle(e) {
            e.preventDefault();
            
            try {
                const isOpen = dropdownMenu.classList.contains('show');
                
                // Close any open dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle this dropdown
                if (!isOpen) {
                    dropdownMenu.classList.add('show');
                    cartDropdownToggle.setAttribute('aria-expanded', 'true');
                    
                    // Fetch latest cart data when opening dropdown
                    if (document.body.classList.contains('logged-in')) {
                        fetch(window.APP_URL + '/api/cart.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    updateCartPreview(data.cart);
                                    updateCartCount(data.cart.item_count);
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching cart data:', error);
                            });
                    }
                } else {
                    dropdownMenu.classList.remove('show');
                    cartDropdownToggle.setAttribute('aria-expanded', 'false');
                }
            } catch (error) {
                console.error('Error in dropdown toggle:', error);
                safeDropdownInitialize();
            }
        }
        
        // Attempt safe initialization
        safeDropdownInitialize();
    }
}

/**
 * Initialize Add to Cart buttons throughout the site
 */
function initAddToCartButtons() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    
    console.log(`Found ${addToCartButtons.length} add to cart buttons`);
    
    if (addToCartButtons.length > 0) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get the product ID from the button's data attribute
                const productId = this.getAttribute('data-product-id');
                
                console.log(`Add to Cart clicked - Product ID: ${productId}`);
                
                // Check if user is logged in
                const isLoggedIn = document.body.classList.contains('logged-in');
                
                console.log(`User logged in: ${isLoggedIn}`);
                
                if (!isLoggedIn) {
                    // If not logged in, redirect to login
                    alert('Please login to add items to your cart');
                    window.location.href = window.APP_URL + '/login';
                    return;
                }
                
                // Disable the button and show loading state
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                
                // Send AJAX request to add item to cart
                fetch(window.APP_URL + '/api/cart.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1,
                        csrf_token: window.csrfToken
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Server response:', data);
                    
                    if (data.success) {
                        // Update cart count
                        updateCartCount(data.cart_count);
                        
                        // Show success toast
                        showToast(`Product added to cart!`, 'success');
                        
                        // Refresh page to update cart preview (for simplicity)
                        // In a more advanced implementation, we would update the cart preview with AJAX
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                        
                        // Reset button
                        this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    } else {
                        // Show error toast
                        showToast(data.message || 'Failed to add product', 'danger');
                        console.error('Failed to add product:', data.message);
                        
                        // Reset button
                        this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    }
                    
                    // Re-enable the button
                    this.disabled = false;
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    
                    // Show error toast
                    showToast('An error occurred', 'danger');
                    
                    // Reset button
                    this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    this.disabled = false;
                });
            });
        });
    }
}

/**
 * Initialize quantity handlers on cart page
 */
function initQuantityHandlers() {
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    
    if (quantityInputs.length > 0) {
        quantityInputs.forEach(input => {
            // Handle direct input changes
            input.addEventListener('change', updateItemQuantity);
            
            // Handle quantity buttons
            const decrementBtn = input.previousElementSibling;
            const incrementBtn = input.nextElementSibling;
            
            if (decrementBtn && decrementBtn.classList.contains('btn-decrement')) {
                decrementBtn.addEventListener('click', function() {
                    if (input.value > 1) {
                        input.value = parseInt(input.value) - 1;
                        updateItemQuantity.call(input);
                    }
                });
            }
            
            if (incrementBtn && incrementBtn.classList.contains('btn-increment')) {
                incrementBtn.addEventListener('click', function() {
                    input.value = parseInt(input.value) + 1;
                    updateItemQuantity.call(input);
                });
            }
        });
    }
}

/**
 * Update cart item quantity via AJAX
 */
function updateItemQuantity() {
    const cartId = this.dataset.cartId;
    const quantity = parseInt(this.value);
    const row = this.closest('tr');
    
    // Show loading state
    row.classList.add('bg-light');
    
    // Update quantity on server
    fetch(window.APP_URL + '/api/cart.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cart_id: cartId,
            quantity: quantity,
            csrf_token: window.csrfToken
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart display
            updateCartDisplay(data.cart);
            
            // Remove loading state
            row.classList.remove('bg-light');
            
            // Show success message
            showToast('Cart updated', 'success');
        } else {
            // Show error message
            showToast(data.message, 'danger');
            
            // Remove loading state
            row.classList.remove('bg-light');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Show error message
        showToast('An error occurred. Please try again.', 'danger');
        
        // Remove loading state
        row.classList.remove('bg-light');
    });
}

/**
 * Initialize remove item handlers on cart page
 */
function initRemoveItemHandlers() {
    const removeButtons = document.querySelectorAll('.remove-item');
    
    if (removeButtons.length > 0) {
        removeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const cartId = this.dataset.cartId;
                const row = this.closest('tr');
                
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Remove item from cart
                fetch(window.APP_URL + '/api/cart.php?action=remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_id: cartId,
                        csrf_token: window.csrfToken
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fade out row
                        row.style.transition = 'opacity 0.5s';
                        row.style.opacity = '0';
                        
                        setTimeout(() => {
                            // Update cart display
                            updateCartDisplay(data.cart);
                            
                            // Show success message
                            showToast('Item removed from cart', 'success');
                        }, 500);
                    } else {
                        // Reset button
                        this.innerHTML = '<i class="fas fa-trash"></i>';
                        this.disabled = false;
                        
                        // Show error message
                        showToast(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Reset button
                    this.innerHTML = '<i class="fas fa-trash"></i>';
                    this.disabled = false;
                    
                    // Show error message
                    showToast('An error occurred. Please try again.', 'danger');
                });
            });
        });
    }
}

/**
 * Initialize checkout button on cart page
 */
function initCheckoutButton() {
    const checkoutButton = document.getElementById('checkout-button');
    
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            this.disabled = true;
            
            console.log('Checkout button clicked - sending request to /api/cart.php?action=checkout');
            
            // Send checkout request
            fetch(window.APP_URL + '/api/cart.php?action=checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'checkout',
                    csrf_token: window.csrfToken
                })
            })
            .then(response => {
                console.log('Checkout response status:', response.status);
                return response.text().then(text => {
                    // Try to parse as JSON, but handle text responses too
                    try {
                        return text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.log('Response text:', text);
                        return { 
                            success: false, 
                            message: 'Invalid response from server'
                        };
                    }
                });
            })
            .then(data => {
                console.log('Checkout response data:', data);
                
                if (data.success) {
                    // Show success message
                    showToast('Order placed successfully!', 'success');
                    
                    // Redirect to order confirmation or dashboard
                    setTimeout(() => {
                        window.location.href = data.redirect || window.APP_URL + '/customer/dashboard';
                    }, 1000);
                } else {
                    // Reset button
                    this.innerHTML = 'Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>';
                    this.disabled = false;
                    
                    // Show error message
                    showToast(data.message || 'Failed to process checkout', 'danger');
                }
            })
            .catch(error => {
                console.error('Checkout error:', error);
                
                // Reset button
                this.innerHTML = 'Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>';
                this.disabled = false;
                
                // Show error message
                showToast('An error occurred. Please try again.', 'danger');
            });
        });
    }
}

/**
 * Update cart count in header
 * 
 * @param {number} count - Number of items in cart
 */
function updateCartCount(count) {
    const cartCountBadge = document.getElementById('cart-count');
    
    if (cartCountBadge) {
        cartCountBadge.textContent = count;
        cartCountBadge.classList.toggle('d-none', count === 0);
        
        // Mobile-specific positioning reset
        cartCountBadge.classList.add('cart-count-mobile');
    }
}

/**
 * Update cart display with new cart data
 * 
 * @param {Object} cartData - Cart data from server
 */
function updateCartDisplay(cartData) {
    const cartBody = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const emptyCartMessage = document.getElementById('empty-cart-message');
    const cartContent = document.getElementById('cart-content');
    
    if (cartBody && cartTotal) {
        // Update cart count in header
        updateCartCount(cartData.item_count);
        
        // Update cart total
        cartTotal.textContent = cartData.formatted_total;
        
        // Check if cart is empty
        if (cartData.items.length === 0) {
            if (emptyCartMessage && cartContent) {
                emptyCartMessage.classList.remove('d-none');
                cartContent.classList.add('d-none');
            }
            return;
        }
        
        // Cart has items
        if (emptyCartMessage && cartContent) {
            emptyCartMessage.classList.add('d-none');
            cartContent.classList.remove('d-none');
        }
        
        // Clear current cart items
        cartBody.innerHTML = '';

        // Add cart items
        cartData.items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${window.APP_URL}/images/products/${item.image_path}" alt="${item.product_name}" class="cart-item-image" width="50">
                        <div class="ms-3">
                            <h6 class="mb-0">${item.product_name}</h6>
                            <small class="text-muted">${item.category}</small>
                        </div>
                    </div>
                </td>
                <td>${item.formatted_price}</td>
                <td>
                    <div class="input-group input-group-sm quantity-control" style="width: 130px;">
                        <button class="btn btn-outline-secondary btn-decrement" type="button">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center cart-quantity" value="${item.quantity}" min="1" data-cart-id="${item.cart_id}">
                        <button class="btn btn-outline-secondary btn-increment" type="button">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </td>
                <td class="text-end">${item.formatted_total}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger remove-item" data-cart-id="${item.cart_id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            cartBody.appendChild(row);
        });
        
        // Reinitialize handlers for new elements
        initQuantityHandlers();
        initRemoveItemHandlers();
        
        // After updating the cart, we should also update the cart preview if it exists
        updateCartPreview(cartData);
    }
}

/**
 * Update cart preview dropdown
 * 
 * @param {Object} cartData - Cart data from server
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
 * 
 * @param {string} message - Message to display
 * @param {string} type - Bootstrap alert type (success, danger, etc.)
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
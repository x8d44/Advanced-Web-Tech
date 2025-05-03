/**
 * captcha.js - CAPTCHA Management
 *
 * Handles CAPTCHA functionality including refreshing the image and validating user input.
 * Enhances security by preventing automated form submissions.
 */

document.addEventListener('DOMContentLoaded', function() {
    const refreshButton = document.getElementById('refresh-captcha');
    const captchaImage = document.querySelector('.captcha-image');
    
    if (refreshButton && captchaImage) {
        refreshButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            this.disabled = true;
            
            // Fetch new CAPTCHA with proper error handling
            fetch(window.APP_URL + '/api/captcha.php?action=refresh')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server responded with status: ${response.status}`);
                    }
                    return response.text(); // Get as text first to check if valid JSON
                })
                .then(text => {
                    try {
                        if (!text) {
                            throw new Error('Empty response received');
                        }
                        return JSON.parse(text); // Now parse as JSON
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid response format from server');
                    }
                })
                .then(data => {
                    if (data && data.success && data.captcha) {
                        // Update image source
                        captchaImage.src = window.APP_URL + '/images/captcha/' + data.captcha.image_path;
                        
                        // Store CAPTCHA ID in hidden field
                        const captchaIdField = document.getElementById('captcha_id');
                        if (captchaIdField) {
                            captchaIdField.value = data.captcha.captcha_id;
                        }
                        
                        // Clear any previous input
                        const captchaInput = document.querySelector('input[name="captcha"]');
                        if (captchaInput) {
                            captchaInput.value = '';
                        }
                    } else {
                        throw new Error(data && data.message ? data.message : 'Invalid CAPTCHA response');
                    }
                })
                .catch(error => {
                    console.error('Error refreshing CAPTCHA:', error);
                    // Display error message to user
                    alert('Could not refresh CAPTCHA. Please reload the page and try again.');
                })
                .finally(() => {
                    // Reset button state
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh CAPTCHA';
                    this.disabled = false;
                });
        });
    }
    
    // Form validation for login
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const captchaInput = document.querySelector('input[name="captcha"]');
            
            if (captchaInput && captchaInput.value.trim() === '') {
                e.preventDefault();
                
                // Create or update error message
                let errorMessage = document.getElementById('captcha-error');
                
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.id = 'captcha-error';
                    errorMessage.className = 'invalid-feedback d-block';
                    captchaInput.parentNode.appendChild(errorMessage);
                }
                
                errorMessage.textContent = 'Please enter the CAPTCHA text.';
                captchaInput.classList.add('is-invalid');
            }
        });
    }
});
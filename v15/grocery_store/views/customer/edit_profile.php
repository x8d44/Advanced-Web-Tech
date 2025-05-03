<?php
/**
 * Edit Profile Page for Grocery Store Web Application
 * 
 * Allows users to update their personal information
 * with comprehensive validation and security measures.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set page title securely
$pageTitle = htmlspecialchars("Edit Profile");

// Security: Authenticate user
if (!isset($_SESSION['user_id'])) {
    // Redirect unauthenticated users
    $_SESSION['flash_message'] = "Please login to edit your profile";
    $_SESSION['flash_type'] = "warning";
    
    header('Location: /login');
    exit;
}

// Include header
require_once __DIR__ . '/../shared/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card mt-4 mb-4">
            <div class="card-header bg-success text-white">
                <h1 class="m-0 h3">Edit Profile</h1>
            </div>
            <div class="card-body">
                <form id="edit-profile-form" action="/api/users.php" method="post" novalidate>
                    <!-- Security Tokens -->
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <!-- Full Name Input with Enhanced Validation -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="name" 
                            name="name" 
                            value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" 
                            required
                            pattern="^[A-Za-z\s]+$"
                            maxlength="50"
                            aria-describedby="name-help"
                        >
                        <small id="name-help" class="form-text text-muted">
                            Letters and spaces only. Maximum 50 characters.
                        </small>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <!-- Phone Number Input with Validation -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input 
                            type="tel" 
                            class="form-control" 
                            id="phone" 
                            name="phone" 
                            value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" 
                            required
                            pattern="^\d{10}$"
                            maxlength="10"
                            aria-describedby="phone-help"
                        >
                        <small id="phone-help" class="form-text text-muted">
                            10 digits only. No spaces or special characters.
                        </small>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <!-- Email Address Input with Validation -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" 
                            required
                            aria-describedby="email-help"
                        >
                        <small id="email-help" class="form-text text-muted">
                            Enter a valid email address
                        </small>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
                
                <!-- Additional Navigation -->
                <div class="text-center mt-3">
                    <p>
                        <a href="/customer/dashboard" class="text-muted">
                            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Fixed Profile Update Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-profile-form');
    
    // Helper function to find feedback element for an input
    function getFeedbackElement(input) {
        // Find the parent .mb-3 div
        const formGroup = input.closest('.mb-3');
        if (!formGroup) return null;
        
        // Find the .invalid-feedback element within this group
        return formGroup.querySelector('.invalid-feedback');
    }
    
    // Comprehensive validation functions
    const validationRules = {
        name: function(value) {
            value = value.trim();
            if (value.length === 0) return 'Name cannot be empty.';
            if (value.length < 2) return 'Name must be at least 2 characters long.';
            if (!/^[A-Za-z\s]+$/.test(value)) return 'Name should only contain letters and spaces.';
            return '';
        },
        phone: function(value) {
            // Remove non-digit characters
            const digits = value.replace(/\D/g, '');
            if (digits.length === 0) return 'Phone number is required.';
            if (digits.length !== 10) return 'Phone number must be exactly 10 digits.';
            return '';
        },
        email: function(value) {
            value = value.trim();
            if (value.length === 0) return 'Email address is required.';
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(value)) return 'Please enter a valid email address.';
            return '';
        }
    };
    
    // Live input validation
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const value = this.value;
            let errorMessage = '';

            // Validate based on input ID
            switch(this.id) {
                case 'name':
                    errorMessage = validationRules.name(value);
                    break;
                case 'phone':
                    errorMessage = validationRules.phone(value);
                    break;
                case 'email':
                    errorMessage = validationRules.email(value);
                    break;
            }

            // Update error display
            const feedbackElement = getFeedbackElement(this);
            if (feedbackElement) {
                if (errorMessage) {
                    this.classList.add('is-invalid');
                    feedbackElement.textContent = errorMessage;
                    feedbackElement.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    feedbackElement.textContent = '';
                    feedbackElement.style.display = 'none';
                }
            }
        });
    });
    
    // Form submission handler with improved error handling
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submission started');
        
        // Reset all error states
        const inputs = form.querySelectorAll('input');
        let hasErrors = false;
        
        inputs.forEach(input => {
            const value = input.value;
            let errorMessage = '';

            // Validate each input
            switch(input.id) {
                case 'name':
                    errorMessage = validationRules.name(value);
                    break;
                case 'phone':
                    errorMessage = validationRules.phone(value);
                    break;
                case 'email':
                    errorMessage = validationRules.email(value);
                    break;
            }

            // Display errors
            const feedbackElement = getFeedbackElement(input);
            if (feedbackElement) {
                if (errorMessage) {
                    input.classList.add('is-invalid');
                    feedbackElement.textContent = errorMessage;
                    feedbackElement.style.display = 'block';
                    hasErrors = true;
                } else {
                    input.classList.remove('is-invalid');
                    feedbackElement.textContent = '';
                    feedbackElement.style.display = 'none';
                }
            }
        });

        // Prevent submission if errors exist
        if (hasErrors) return;

        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        submitButton.disabled = true;
        
        // Disable inputs during submission to prevent double-submit
        inputs.forEach(input => {
            input.disabled = true;
        });

        // Get current CSRF token from the form
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        console.log('CSRF Token:', csrfToken);
        
        // Create form data manually instead of FormData(form)
        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('csrf_token', csrfToken);
        formData.append('name', document.getElementById('name').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('email', document.getElementById('email').value);
        
        // Log all form data entries for debugging
        const formDataObj = {};
        formData.forEach((value, key) => {
            formDataObj[key] = value;
        });
        console.log('Form data prepared', formDataObj);
        
        // Submit via AJAX
        fetch('/api/users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received:', response.status);
            return response.json().catch(error => {
                console.error('Error parsing JSON response:', error);
                throw new Error('Invalid server response format');
            });
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Show success message
                alert(data.message || 'Profile updated successfully!');
                
                // Redirect if specified
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                // Handle server-side validation errors
                if (data.errors) {
                    console.log('Server validation errors:', data.errors);
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedbackElement = getFeedbackElement(input);
                            if (feedbackElement) {
                                feedbackElement.textContent = data.errors[field];
                                feedbackElement.style.display = 'block';
                            }
                        }
                    });
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
            }
        })
        .catch(error => {
            console.error('Submission Error:', error);
            alert('An unexpected error occurred. Please try again.');
        })
        .finally(() => {
            // Always restore form state
            submitButton.innerHTML = originalButtonText;
            submitButton.disabled = false;
            
            // Re-enable all form fields
            inputs.forEach(input => {
                input.disabled = false;
                input.style.display = '';
            });
            
            // Make sure all form groups are visible
            const formGroups = form.querySelectorAll('.mb-3');
            formGroups.forEach(group => {
                group.style.display = '';
            });
            
            console.log('Form submission complete - form restored');
        });
    });
});
</script>


<?php
// Include footer
require_once __DIR__ . '/../shared/footer.php';
?>
<?php
/**
 * Login Page for Grocery Store Web Application
 * 
 * Manages user authentication with enhanced security features
 * including CAPTCHA verification and secure login process.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title securely
$pageTitle = htmlspecialchars("Login");

// Include necessary files with error handling
try {
    require_once __DIR__ . '/shared/header.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../models/Captcha.php';

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate CAPTCHA model
    $captcha = new Captcha($db);

    // Retrieve a secure, random CAPTCHA
    $randomCaptcha = $captcha->getRandomCaptcha();

    // Verify and store CAPTCHA securely
    if ($randomCaptcha) {
        $_SESSION['captcha_id'] = $randomCaptcha['captcha_id'];
        
        // Secure logging with minimal exposure
        error_log("Login CAPTCHA generated: ID={$randomCaptcha['captcha_id']}");
    } else {
        // Log CAPTCHA generation failure
        error_log("Failed to generate CAPTCHA for login page");
        
        // Fallback mechanism
        $_SESSION['flash_message'] = "Authentication system temporarily unavailable.";
        $_SESSION['flash_type'] = "warning";
    }
} catch (Exception $e) {
    // Comprehensive error handling
    error_log("Login Page Initialization Error: " . $e->getMessage());
    
    $_SESSION['flash_message'] = "An unexpected error occurred. Please try again later.";
    $_SESSION['flash_type'] = "danger";
    
    // Redirect to a safe page
    header('Location: ' . rtrim(APP_URL, '/') . '/');
    exit;
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card mt-4 mb-4">
            <div class="card-header bg-success text-white">
                <h1 class="m-0 h3">Login to Your Account</h1>
            </div>
            <div class="card-body">
                <form id="login-form" action="<?php echo rtrim(APP_URL, '/'); ?>/login-process" method="post" novalidate>
                    <!-- Security Tokens -->
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" id="captcha_id" name="captcha_id" 
                           value="<?= htmlspecialchars($randomCaptcha['captcha_id'] ?? '') ?>">
                    
                    <!-- Email Input -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               aria-describedby="emailHelp">
                        <div class="invalid-feedback" style="display: none;">
                            Please enter a valid email address.
                        </div>
                        <div id="emailHelp" class="form-text">
                            We'll never share your email with anyone else.
                        </div>
                    </div>
                    
                    <!-- Password Input with Show/Hide Toggle -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password">
                            <button class="btn btn-outline-secondary toggle-password" 
                                    type="button" 
                                    aria-label="Show/Hide Password">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" style="display: none;">
                            Password is required.
                        </div>
                    </div>
                    
                    <!-- CAPTCHA Section -->
                    <div class="mb-3 captcha-container">
                        <label class="form-label">CAPTCHA Verification</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <?php if ($randomCaptcha): ?>
                                    <img src="<?php echo rtrim(APP_URL, '/'); ?>/images/captcha/<?= htmlspecialchars($randomCaptcha['image_path']) ?>"
                                         alt="CAPTCHA Image" 
                                         class="img-fluid captcha-image" 
                                         aria-label="CAPTCHA Challenge">
                                    <button id="refresh-captcha" 
                                            class="btn btn-sm btn-outline-secondary mt-2" 
                                            type="button" 
                                            aria-label="Refresh CAPTCHA">
                                        <i class="fas fa-sync-alt" aria-hidden="true"></i> Refresh
                                    </button>
                                <?php else: ?>
                                    <div class="alert alert-warning" role="alert">
                                        CAPTCHA unavailable. Please try again later.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <input type="text" 
                                       class="form-control" 
                                       name="captcha" 
                                       id="captcha"
                                       placeholder="Enter CAPTCHA" 
                                       aria-label="Enter CAPTCHA text">
                                <small class="form-text text-muted">Case sensitive</small>
                                <div class="invalid-feedback" style="display: none;">
                                    Please enter the CAPTCHA text.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            Login
                        </button>
                    </div>
                </form>
                
                <!-- Registration Link -->
                <div class="text-center mt-3">
                    <p>
                        Don't have an account? 
                        <a href="<?php echo rtrim(APP_URL, '/'); ?>/register" aria-label="Go to Registration Page">
                            Register here
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simplified Form Validation Script -->
<script>
/**
 * Login Form Validation
 * 
 * Handles password visibility toggle and form validation
 * for the login form with streamlined code.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const loginForm = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const captchaInput = document.getElementById('captcha');
    const togglePasswordButton = document.querySelector('.toggle-password');
    
    // Password visibility toggle
    if (togglePasswordButton) {
        togglePasswordButton.addEventListener('click', function() {
            // Toggle password visibility
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }
    
    /**
     * Validate email format
     * @param {string} email - Email to validate
     * @return {boolean} True if valid
     */
    function isValidEmail(email) {
        return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email);
    }
    
    /**
     * Show or hide validation feedback
     * @param {HTMLElement} input - Input element
     * @param {boolean} isValid - Whether input is valid
     */
    function updateValidation(input, isValid) {
        // Get the feedback element
        let feedback;
        if (input === passwordInput) {
            feedback = input.parentElement.nextElementSibling;
        } else {
            feedback = input.nextElementSibling;
        }
        
        // Update classes and visibility
        input.classList.toggle('is-invalid', !isValid);
        feedback.style.display = isValid ? 'none' : 'block';
    }
    
    // Live validation for email
    emailInput.addEventListener('input', function() {
        const value = this.value.trim();
        updateValidation(this, value !== '' && isValidEmail(value));
    });
    
    // Form submission validation
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate email
            const emailValue = emailInput.value.trim();
            const isEmailValid = emailValue !== '' && isValidEmail(emailValue);
            updateValidation(emailInput, isEmailValid);
            isValid = isValid && isEmailValid;
            
            // Validate password
            const isPasswordValid = passwordInput.value !== '';
            updateValidation(passwordInput, isPasswordValid);
            isValid = isValid && isPasswordValid;
            
            // Validate CAPTCHA
            if (captchaInput) {
                const isCaptchaValid = captchaInput.value.trim() !== '';
                updateValidation(captchaInput, isCaptchaValid);
                isValid = isValid && isCaptchaValid;
            }
            
            // Prevent submission if invalid
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
</script>

<!-- CAPTCHA Refresh Script -->
<script src="<?php echo rtrim(APP_URL, '/'); ?>/js/captcha.js" defer></script>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
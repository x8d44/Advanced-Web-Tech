<?php
/**
 * User Registration Page for Grocery Store Web Application
 * 
 * Provides a secure and user-friendly registration interface
 * with client-side and server-side validation.
 */

// Prevent direct file access
defined('APP_NAME') or die('Unauthorized access');

// Set page title securely
$pageTitle = htmlspecialchars("Register");

// Include header
require_once __DIR__ . '/shared/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card mt-4 mb-4">
            <div class="card-header bg-success text-white">
                <h1 class="m-0 h3">Create an Account</h1>
            </div>
            <div class="card-body">
                <!-- CSRF Token for React Form -->
                <script>
                    // Make the CSRF token available to the React component
                    window.csrfToken = "<?= generate_csrf_token() ?>";
                </script>
                
                <div id="registration-form-container">
                    <!-- React will render the form here -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading registration form...</span>
                        </div>
                        <p>Loading registration form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Optimized Script Loading -->
<script src="https://unpkg.com/react@17/umd/react.production.min.js" defer></script>
<script src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js" defer></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js" defer></script>

<!-- Registration Form Component -->
<script src="/js/components/RegistrationForm.js" type="text/babel" defer></script>

<?php
// Include footer
require_once __DIR__ . '/shared/footer.php';
?>
<?php
/**
 * UserController.php - User Management Controller
 *
 * Handles user-related operations including registration, login,
 * profile management, and authentication.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Captcha.php';
require_once __DIR__ . '/../config/inputsanitizer.php';

class UserController {
    /** @var PDO Database connection */
    private $db;
    
    /** @var User User model instance */
    private $user;
    
    /** @var Captcha Captcha model instance */
    private $captcha;
    
    /**
     * Constructor initializes database connection and model instances
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->captcha = new Captcha($this->db);
    }
    
    /**
     * Process user registration
     * 
     * @return void Outputs JSON response
     */
    public function register() {
        // Check if it's an AJAX request
        $isAjax = $this->isAjaxRequest();
        
        try {
            // Get form data
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            // For AJAX requests, try to get data from request body
            if ($isAjax && empty($name)) {
                $data = json_decode(file_get_contents('php://input'), true);
                $name = $data['name'] ?? '';
                $phone = $data['phone'] ?? '';
                $email = $data['email'] ?? '';
                $password = $data['password'] ?? '';
                
                // Verify CSRF token for AJAX requests
                if (isset($data['csrf_token']) && !verify_csrf_token($data['csrf_token'])) {
                    $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                    return;
                }
            } else {
                // Verify CSRF token for form submissions
                if (isset($_POST['csrf_token']) && !verify_csrf_token($_POST['csrf_token'])) {
                    $this->sendJsonResponse(false, 'Invalid security token. Please refresh the page and try again.');
                    return;
                }
            }
            
            // Validate inputs server-side
            $errors = $this->validateRegistrationInput($name, $phone, $email, $password);
            
            // If there are validation errors
            if (!empty($errors)) {
                $this->sendJsonResponse(false, 'Validation failed', ['errors' => $errors]);
                return;
            }
            
            // Set user properties
            $this->user->name = $name;
            $this->user->phone = $phone;
            $this->user->email = $email;
            $this->user->password = $password;
            
            // Register the user
            if ($this->user->register()) {
                // Registration successful
                $this->sendJsonResponse(true, 'Registration successful! You can now login.', ['redirect' => '/login']);
            } else {
                // Registration failed (likely email already exists)
                $this->sendJsonResponse(false, 'Registration failed. Email may already be registered.');
            }
        } catch (Exception $e) {
            // Log the error
            ErrorLogger::log("Registration error: " . $e->getMessage(), "ERROR");
            
            $this->sendJsonResponse(false, 'An error occurred during registration. Please try again.', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Process user login
     * 
     * @return void Redirects on success or failure
     */
    public function login() {
        try {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                // Set error message in session and redirect back to login
                $this->setFlashMessage('Invalid request. Please try again.', 'danger');
                header('Location: /login');
                exit;
            }
            
            // Get form data
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $captchaInput = isset($_POST['captcha']) ? $_POST['captcha'] : '';
            $captchaId = isset($_SESSION['captcha_id']) ? $_SESSION['captcha_id'] : null;
            
            // Debug data
            $debugCaptchaId = isset($_POST['debug_captcha_id']) ? $_POST['debug_captcha_id'] : 'not set';
            $debugCaptchaValue = isset($_POST['debug_captcha_value']) ? $_POST['debug_captcha_value'] : 'not set';
            
            // Debug log
            error_log("Login attempt - Email: $email, Session CaptchaID: $captchaId, User Input: '$captchaInput'");
            error_log("Debug info - Form CaptchaID: $debugCaptchaId, Expected Value: $debugCaptchaValue");
            
            // CAPTCHA validation
            $captchaValid = $this->validateCaptcha($captchaId, $captchaInput);
            
            // If CAPTCHA validation fails
            if (!$captchaValid) {
                // Set error message in session and redirect back to login
                $this->setFlashMessage('Invalid CAPTCHA. Please try again.', 'danger');
                header('Location: /login');
                exit;
            }
            
            // Clear used CAPTCHA from session
            unset($_SESSION['captcha_id']);
            
            // Validate email and password
            if (empty($email) || empty($password)) {
                // Set error message in session and redirect back to login
                $this->setFlashMessage('Email and password are required.', 'danger');
                header('Location: /login');
                exit;
            }
            
            // Sanitize inputs
            $email = InputSanitizer::sanitizeString($email);
            
            // Attempt login
            $user = $this->user->login($email, $password);
            
            if ($user) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone'] = $user['phone'];
                
                // Initialize cart count
                $_SESSION['cart_count'] = 0;
                
                // Set success message and redirect to products page
                $this->setFlashMessage('Login successful!', 'success');
                header('Location: /products');
                exit;
            } else {
                // Login failed
                // Set error message in session and redirect back to login
                $this->setFlashMessage('Invalid email or password.', 'danger');
                header('Location: /login');
                exit;
            }
        } catch (Exception $e) {
            // Log the error
            ErrorLogger::log("Login error: " . $e->getMessage(), "ERROR");
            
            // Set error message in session and redirect back to login
            $this->setFlashMessage('An error occurred during login. Please try again.', 'danger');
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Process user logout
     * 
     * @return void Redirects to home page
     */
    public function logout() {
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Start a new session for flash message
        session_start();
        
        // Set logout message
        $this->setFlashMessage('You have been logged out successfully.', 'success');
        
        // Redirect to home page
        header('Location: /');
        exit;
    }
    
    /**
     * Process profile update
     * 
     * @return void Outputs JSON response
     */
    public function updateProfile() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->sendJsonResponse(false, 'You must be logged in to update your profile.', ['redirect' => '/login']);
            exit;
        }
        
        try {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
                throw new Exception('Invalid security token');
            }
            
            // Get form data
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            
            // Validate inputs
            $errors = $this->validateProfileUpdateInput($name, $phone, $email);
            
            // If there are validation errors
            if (!empty($errors)) {
                $this->sendJsonResponse(false, 'Validation failed', ['errors' => $errors]);
                return;
            }
            
            // Set user properties
            $this->user->user_id = $_SESSION['user_id'];
            $this->user->name = $name;
            $this->user->phone = $phone;
            $this->user->email = $email;
            
            // Update the profile
            if ($this->user->updateProfile()) {
                // Update session values
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                
                // Update successful
                $this->sendJsonResponse(true, 'Profile updated successfully!', ['redirect' => '/customer/dashboard']);
            } else {
                // Update failed (likely email already exists)
                throw new Exception('Profile update failed. Email may already be registered.');
            }
        } catch (Exception $e) {
            // Log the error
            ErrorLogger::log("Profile update error: " . $e->getMessage(), "ERROR");
            
            $this->sendJsonResponse(false, 'An error occurred during profile update. Please try again.', 
                ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle API user requests
     * 
     * @return void Outputs JSON response
     */
    public function handleApiRequest() {
        // Check the request method and action
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                // Handle the action
                switch ($_POST['action']) {
                    case 'register':
                        $this->register();
                        break;
                        
                    case 'login':
                        $this->login();
                        break;
                        
                    case 'update_profile':
                        $this->updateProfile();
                        break;
                        
                    default:
                        // Invalid action
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid action'
                        ]);
                        break;
                }
            } else {
                // Missing action parameter
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing action parameter'
                ]);
            }
        } else {
            // Method not allowed
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
        }
    }
    
    /**
     * Validate registration input
     * 
     * @param string $name User name
     * @param string $phone User phone
     * @param string $email User email
     * @param string $password User password
     * @return array Validation errors
     */
    private function validateRegistrationInput($name, $phone, $email, $password) {
        $errors = [];
        
        // Validate name (letters and spaces only)
        if (empty($name)) {
            $errors['name'] = 'Name is required.';
        } elseif (!preg_match('/^[A-Za-z\s]+$/', $name)) {
            $errors['name'] = 'Name should only contain letters and spaces.';
        }
        
        // Validate phone (10 digits)
        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            $errors['phone'] = 'Phone number should be 10 digits.';
        }
        
        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        // Validate password (at least 8 characters)
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/', $password)) {
            $errors['password'] = 'Password must include at least one uppercase letter, one lowercase letter, and one number.';
        }
        
        return $errors;
    }
    
    /**
     * Validate profile update input
     * 
     * @param string $name User name
     * @param string $phone User phone
     * @param string $email User email
     * @return array Validation errors
     */
    private function validateProfileUpdateInput($name, $phone, $email) {
        $errors = [];
        
        // Validate name (letters and spaces only)
        if (empty($name)) {
            $errors['name'] = 'Name is required.';
        } elseif (!preg_match('/^[A-Za-z\s]+$/', $name)) {
            $errors['name'] = 'Name should only contain letters and spaces.';
        }
        
        // Validate phone (10 digits)
        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^\d{10}$/', $phone)) {
            $errors['phone'] = 'Phone number should be 10 digits.';
        }
        
        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        
        return $errors;
    }
    
    /**
     * Validate CAPTCHA
     * 
     * @param int $captchaId CAPTCHA ID
     * @param string $captchaInput User input for CAPTCHA
     * @return boolean Valid or invalid
     */
    private function validateCaptcha($captchaId, $captchaInput) {
        // SIMPLIFIED CAPTCHA VALIDATION FOR THIS EXAMPLE
        // In a real application, we would validate against the database
        
        // Hard-coded CAPTCHA values (matches database)
        $captchaValues = [
            1 => 'Aeik2',
            2 => 'ecb4f',
            3 => '7plBJ8',
            4 => '24qVg'
        ];
        
        // Direct check against hard-coded values
        if (isset($captchaValues[$captchaId]) && $captchaInput === $captchaValues[$captchaId]) {
            error_log("CAPTCHA validation successful using direct check");
            return true;
        }
        
        // For better security, use database validation in production
        if ($captchaId && !empty($captchaInput)) {
            return $this->captcha->verifyCaptcha($captchaId, $captchaInput);
        }
        
        error_log("CAPTCHA validation failed: ID=$captchaId, Input=$captchaInput");
        return false;
    }
    
    /**
     * Set flash message for one-time display
     * 
     * @param string $message Message text
     * @param string $type Message type (success, info, warning, danger)
     * @return void
     */
    private function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return boolean Is AJAX request
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     * 
     * @param boolean $success Success status
     * @param string $message Message text
     * @param array $data Additional data
     * @return void
     */
    private function sendJsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Add additional data if provided
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
        exit;
    }
}
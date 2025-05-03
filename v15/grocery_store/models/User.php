<?php
/**
 * User.php - User Management Model
 *
 * This class handles user operations including registration, authentication,
 * and user profile management. It implements secure password handling and
 * validation for user data.
 */

class User {
    /** @var PDO Database connection */
    private $conn;
    
    /** @var string Database table name */
    private $table_name = "users";
    
    // User properties
    public $user_id;
    public $name;
    public $phone;
    public $email;
    public $password;
    public $registration_date;
    public $role;
    
    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection object
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Register new user
     * 
     * Creates a new user account with validation
     * 
     * @return boolean Success or failure
     */
    public function register() {
        try {
            // Validate user data
            if (!$this->validateUserData()) {
                return false;
            }
            
            // Check if email already exists
            if ($this->emailExists()) {
                ErrorLogger::log("Registration attempt with existing email: {$this->email}", "INFO");
                return false;
            }
            
            // Prepare insert query
            $query = "INSERT INTO " . $this->table_name . " 
                     (name, phone, email, password, role) 
                     VALUES 
                     (:name, :phone, :email, :password, :role)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            // Set default role if not specified
            $this->role = isset($this->role) ? htmlspecialchars(strip_tags($this->role)) : 'customer';
            
            // Hash the password - use PASSWORD_DEFAULT for best future-proof algorithm
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
            
            // Bind parameters
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);
            $stmt->bindParam(":role", $this->role);
            
            // Execute query
            if ($stmt->execute()) {
                $this->user_id = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            ErrorLogger::log("Registration error: " . $e->getMessage(), "ERROR");
            return false;
        }
        
        return false;
    }
    
    /**
     * Validate user data before registration or update
     * 
     * @return boolean Valid or invalid
     */
    private function validateUserData() {
        // Name validation
        if (empty($this->name) || !preg_match('/^[A-Za-z\s]+$/', $this->name)) {
            ErrorLogger::log("Invalid name format: {$this->name}", "WARNING");
            return false;
        }
        
        // Phone validation
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (empty($phone) || strlen($phone) !== 10) {
            ErrorLogger::log("Invalid phone format: {$this->phone}", "WARNING");
            return false;
        }
        $this->phone = $phone; // Store sanitized phone
        
        // Email validation
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            ErrorLogger::log("Invalid email format: {$this->email}", "WARNING");
            return false;
        }
        
        // Password validation (if not already hashed)
        if (!empty($this->password) && strpos($this->password, '$2y$') !== 0) {
            if (strlen($this->password) < 8 || 
                !preg_match('/[A-Z]/', $this->password) || 
                !preg_match('/[a-z]/', $this->password) || 
                !preg_match('/[0-9]/', $this->password)) {
                ErrorLogger::log("Password does not meet complexity requirements", "WARNING");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Login user
     * 
     * Authenticates user credentials
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|boolean User data or false if login fails
     */
    public function login($email, $password) {
        try {
            // Log login attempt
            ErrorLogger::log("Login attempt for email: {$email}", "INFO");
            
            // Query to check if email exists
            $query = "SELECT user_id, name, phone, email, password, role FROM " . $this->table_name . " 
                     WHERE email = :email";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $email = htmlspecialchars(strip_tags($email));
            
            // Bind parameters
            $stmt->bindParam(":email", $email);
            
            // Execute query
            $stmt->execute();
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($password, $row['password'])) {
                    // Return user data without password
                    unset($row['password']);
                    
                    // Log successful login
                    ErrorLogger::log("Successful login for user ID: {$row['user_id']}", "INFO");
                    
                    return $row;
                }
                
                // Log failed password verification
                ErrorLogger::log("Failed password verification for email: {$email}", "WARNING");
            } else {
                // Log email not found
                ErrorLogger::log("Login attempt with non-existent email: {$email}", "WARNING");
            }
        } catch (PDOException $e) {
            ErrorLogger::log("Login error: " . $e->getMessage(), "ERROR");
        }
        
        return false;
    }
    
    /**
     * Check if email already exists
     * 
     * @return boolean True if email exists, false otherwise
     */
    public function emailExists() {
        try {
            $query = "SELECT user_id FROM " . $this->table_name . " 
                     WHERE email = :email";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            // Bind parameters
            $stmt->bindParam(":email", $this->email);
            
            // Execute query
            $stmt->execute();
            
            // Check if email exists
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            ErrorLogger::log("Email check error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|boolean User data or false
     */
    public function getUserById($id) {
        try {
            $query = "SELECT user_id, name, phone, email, role FROM " . $this->table_name . " 
                     WHERE user_id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":id", $id);
            
            // Execute query
            $stmt->execute();
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Get user error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Update user information
     * 
     * @return boolean Success or failure
     */
    public function update() {
        try {
            // Validate user data
            if (!$this->validateUserData()) {
                return false;
            }
            
            // Check if trying to update to an existing email
            if ($this->emailExistsExcludingSelf()) {
                ErrorLogger::log("Update attempt with existing email: {$this->email}", "WARNING");
                return false;
            }
            
            // Prepare update query
            $query = "UPDATE " . $this->table_name . " 
                     SET name = :name, phone = :phone, email = :email 
                     WHERE user_id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            
            // Bind parameters
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->user_id);
            
            // Execute query
            if ($stmt->execute()) {
                ErrorLogger::log("User updated: {$this->user_id}", "INFO");
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Update user error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Check if email exists excluding the current user
     * 
     * @return boolean True if email exists for another user, false otherwise
     */
    private function emailExistsExcludingSelf() {
        try {
            $query = "SELECT user_id FROM " . $this->table_name . " 
                     WHERE email = :email AND user_id != :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            
            // Bind parameters
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->user_id);
            
            // Execute query
            $stmt->execute();
            
            // Check if email exists
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            ErrorLogger::log("Email check error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Change user password
     * 
     * @param string $current_password Current password
     * @param string $new_password New password
     * @return boolean Success or failure
     */
    public function changePassword($current_password, $new_password) {
        try {
            // Validate new password
            if (strlen($new_password) < 8 || 
                !preg_match('/[A-Z]/', $new_password) || 
                !preg_match('/[a-z]/', $new_password) || 
                !preg_match('/[0-9]/', $new_password)) {
                ErrorLogger::log("New password does not meet complexity requirements", "WARNING");
                return false;
            }
            
            // First verify current password
            $query = "SELECT password FROM " . $this->table_name . " 
                     WHERE user_id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":id", $this->user_id);
            
            // Execute query
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify current password
                if (password_verify($current_password, $row['password'])) {
                    // Update to new password
                    $query = "UPDATE " . $this->table_name . " 
                             SET password = :password 
                             WHERE user_id = :id";
                    
                    // Prepare statement
                    $stmt = $this->conn->prepare($query);
                    
                    // Hash the new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Bind parameters
                    $stmt->bindParam(":password", $hashed_password);
                    $stmt->bindParam(":id", $this->user_id);
                    
                    // Execute query
                    return $stmt->execute();
                }
                
                // Log failed password verification
                ErrorLogger::log("Failed password verification for password change, user ID: {$this->user_id}", "WARNING");
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Password change error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Update user profile
     * 
     * @return boolean Success or failure
     */
    public function updateProfile() {
        try {
            // Check if trying to update to an existing email (excluding current user)
            if ($this->emailExistsExcludingSelf()) {
                return false;
            }
            
            // Validate user data
            if (!$this->validateUserData()) {
                return false;
            }
            
            // Prepare update query
            $query = "UPDATE " . $this->table_name . " 
                     SET name = :name, phone = :phone, email = :email 
                     WHERE user_id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            
            // Bind parameters
            $stmt->bindParam(":name", $this->name);
            $stmt->bindParam(":phone", $this->phone);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":id", $this->user_id);
            
            // Execute query
            if ($stmt->execute()) {
                ErrorLogger::log("Profile updated for user ID: {$this->user_id}", "INFO");
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("Update profile error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Get all users (admin function)
     * 
     * @return array Users or empty array
     */
    public function readAll() {
        try {
            $query = "SELECT user_id, name, email, phone, role, registration_date 
                     FROM " . $this->table_name . " 
                     ORDER BY registration_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $users = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $row;
            }
            
            return $users;
        } catch (PDOException $e) {
            ErrorLogger::log("Reading all users error: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
    
    /**
     * Delete user (admin function)
     * 
     * @param int $id User ID
     * @return boolean Success or failure
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " 
                     WHERE user_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Deleting user error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
}
<?php
/**
 * database.php - Database Connection Manager
 *
 * This file provides database connection functionality for the application.
 * It implements a singleton pattern to ensure efficient database connection management.
 * 
 *
 */

/**
 * Database Connection Class
 * 
 * Handles database connection establishment and management
 */
class Database {
    /**
     * Database credentials and connection
     */
    private $host = "localhost";
    private $db_name = "x8d44";
    private $username = "x8d44";
    private $password = "x8d44x8d44"; // Stored password for MySQL
    public $conn;
    
    // Connection instance for singleton pattern
    private static $instance = null;
    
    /**
     * Get database connection instance (singleton pattern)
     * 
     * Creates a new connection if none exists, or returns the existing one
     * 
     * @return PDO Active database connection
     * @throws PDOException If connection fails
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // Create a new PDO instance for database connection
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Set character encoding
            $this->conn->exec("set names utf8");
            
            // Configure PDO to throw exceptions on errors
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Use prepared statements by default
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Return associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Log error and re-throw for proper handling upstream
            ErrorLogger::log("Database Connection Error: " . $exception->getMessage(), 'CRITICAL');
            throw $exception;
        }
        
        return $this->conn;
    }
    
    /**
     * Get database instance using singleton pattern
     * 
     * @return Database Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Begin transaction
     * 
     * @return boolean Success or failure
     */
    public function beginTransaction() {
        try {
            if ($this->conn === null) {
                $this->getConnection();
            }
            return $this->conn->beginTransaction();
        } catch (PDOException $e) {
            ErrorLogger::log("Failed to begin transaction: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Commit transaction
     * 
     * @return boolean Success or failure
     */
    public function commit() {
        try {
            return $this->conn->commit();
        } catch (PDOException $e) {
            ErrorLogger::log("Failed to commit transaction: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Roll back transaction
     * 
     * @return boolean Success or failure
     */
    public function rollBack() {
        try {
            return $this->conn->rollBack();
        } catch (PDOException $e) {
            ErrorLogger::log("Failed to roll back transaction: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Check if transaction is active
     * 
     * @return boolean True if transaction is active
     */
    public function inTransaction() {
        return $this->conn !== null && $this->conn->inTransaction();
    }
}
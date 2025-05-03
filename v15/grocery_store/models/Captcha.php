<?php
/**
 * Captcha.php - CAPTCHA Management Model
 *
 * This class handles CAPTCHA operations including retrieval, verification, 
 * and management of CAPTCHA records for user authentication security.
 */

class Captcha {
    /** @var PDO Database connection */
    private $conn;
    
    /** @var string Database table name */
    private $table_name = "captcha";
    
    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection object
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get a random, unused CAPTCHA
     * 
     * Retrieves a random CAPTCHA from the database that hasn't been used
     * and hasn't expired. If no valid CAPTCHA exists, attempts to regenerate.
     * 
     * @param bool $isRecursiveCall Flag to prevent infinite recursion
     * @return array|null CAPTCHA data or null
     */
    public function getRandomCaptcha($isRecursiveCall = false) {
        try {
            // Clean up expired CAPTCHAs
            $this->cleanupExpiredCaptchas();
            
            // Get a random, unused CAPTCHA that hasn't expired
            $query = "SELECT captcha_id, captcha_text, image_path 
                      FROM " . $this->table_name . " 
                      WHERE used = 0 
                      AND (expires_at IS NULL OR expires_at > NOW())
                      ORDER BY RAND() 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // If no unused CAPTCHAs and not already a recursive call, regenerate
            if (!$isRecursiveCall) {
                // Reset all CAPTCHAs and try again
                return $this->regenerateCaptcha();
            }
            
            // If we're already in a recursive call and still no valid CAPTCHA, return null
            return null;
            
        } catch (PDOException $e) {
            ErrorLogger::log("CAPTCHA retrieval error: " . $e->getMessage(), "ERROR");
            return null;
        }
    }
    
    /**
     * Verify CAPTCHA input
     * 
     * Checks if the user-provided CAPTCHA input matches the stored CAPTCHA text
     * 
     * @param int $captchaId CAPTCHA ID
     * @param string $userInput User's input
     * @return boolean Whether the input matches the CAPTCHA
     */
    public function verifyCaptcha($captchaId, $userInput) {
        try {
            // Check if CAPTCHA exists and is valid
            $query = "SELECT captcha_text 
                      FROM " . $this->table_name . " 
                      WHERE captcha_id = :id 
                      AND used = 0 
                      AND (expires_at IS NULL OR expires_at > NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $captchaId, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Mark as used regardless of match for security
                $this->markCaptchaAsUsed($captchaId);
                
                // Case-sensitive comparison
                return $userInput === $result['captcha_text'];
            }
            
            return false;
        } catch (PDOException $e) {
            ErrorLogger::log("CAPTCHA verification error: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Mark CAPTCHA as used
     * 
     * Updates the CAPTCHA record to prevent reuse
     * 
     * @param int $captchaId CAPTCHA ID
     * @return void
     */
    private function markCaptchaAsUsed($captchaId) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET used = 1 
                      WHERE captcha_id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $captchaId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("CAPTCHA mark as used error: " . $e->getMessage(), "ERROR");
        }
    }
    
    /**
     * Clean up expired CAPTCHAs
     * 
     * Removes used or expired CAPTCHAs from the database
     * 
     * @return void
     */
    private function cleanupExpiredCaptchas() {
        try {
            // Only delete CAPTCHAs that have been used or have expired
            $query = "DELETE FROM " . $this->table_name . " 
                      WHERE used = 1 
                      OR (expires_at IS NOT NULL AND expires_at < NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("CAPTCHA cleanup error: " . $e->getMessage(), "ERROR");
        }
    }
    
    /**
     * Regenerate CAPTCHA records
     * 
     * Resets used CAPTCHAs to be available again when all are used
     * 
     * @return array|null New CAPTCHA data or null on failure
     */
    private function regenerateCaptcha() {
        try {
            // Reset used status on all existing CAPTCHAs
            $resetQuery = "UPDATE " . $this->table_name . " 
                          SET used = 0, 
                          expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)";
            
            $resetStmt = $this->conn->prepare($resetQuery);
            $resetStmt->execute();
            
            // Check if any CAPTCHAs exist at all
            $countQuery = "SELECT COUNT(*) as count FROM " . $this->table_name;
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute();
            $result = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            // If no CAPTCHAs exist, create default ones
            if ($result['count'] == 0) {
                $this->createDefaultCaptchas();
            }
            
            // Then get a fresh CAPTCHA with recursion prevention flag
            return $this->getRandomCaptcha(true);
        } catch (PDOException $e) {
            ErrorLogger::log("CAPTCHA regeneration error: " . $e->getMessage(), "ERROR");
            return null;
        }
    }
    
    /**
     * Create default CAPTCHA records
     * 
     * Inserts initial CAPTCHA data into an empty table
     * 
     * @return boolean Success or failure
     */
    private function createDefaultCaptchas() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (captcha_text, image_path, expires_at, used) 
                     VALUES 
                     ('Aeik2', 'image1.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                     ('ecb4f', 'image2.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                     ('7plBJ8', 'image3.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                     ('24qVg', 'image4.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorLogger::log("Creating default CAPTCHAs failed: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * Get all active CAPTCHAs
     * 
     * For administrative purposes
     * 
     * @return array CAPTCHAs or empty array
     */
    public function getAllActiveCaptchas() {
        try {
            $query = "SELECT captcha_id, captcha_text, image_path, created_at, expires_at 
                      FROM " . $this->table_name . " 
                      WHERE used = 0 
                      AND (expires_at IS NULL OR expires_at > NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $captchas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $captchas[] = $row;
            }
            
            return $captchas;
        } catch (PDOException $e) {
            ErrorLogger::log("Error getting active CAPTCHAs: " . $e->getMessage(), "ERROR");
            return [];
        }
    }
}
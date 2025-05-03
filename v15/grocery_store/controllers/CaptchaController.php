<?php
/**
 * CaptchaController.php - CAPTCHA Management Controller
 *
 * Handles CAPTCHA-related operations including generation, verification,
 * and management for secure user authentication.
 */

require_once __DIR__ . '/../models/Captcha.php';

class CaptchaController {
    /** @var PDO Database connection */
    private $db;
    
    /** @var Captcha Captcha model instance */
    private $captcha;
    
    /**
     * Constructor initializes the database connection and captcha model
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->captcha = new Captcha($db);
    }
    
    /**
     * Handle CAPTCHA-related requests based on HTTP method and action
     */
    public function handleRequest() {
        // Determine request type
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGetRequest($action);
                    break;
                case 'POST':
                    $this->handlePostRequest($action);
                    break;
                default:
                    $this->sendErrorResponse('Invalid request method');
                    break;
            }
        } catch (Exception $e) {
            ErrorLogger::log("CAPTCHA controller error: " . $e->getMessage(), "ERROR");
            $this->sendErrorResponse('An error occurred processing your request');
        }
    }
    
    /**
     * Handle GET requests for CAPTCHA
     * 
     * @param string $action Action to perform
     */
    private function handleGetRequest($action) {
        switch ($action) {
            case 'refresh':
                $this->refreshCaptcha();
                break;
            default:
                $this->sendErrorResponse('Invalid GET action');
                break;
        }
    }
    
    /**
     * Handle POST requests for CAPTCHA
     * 
     * @param string $action Action to perform
     */
    private function handlePostRequest($action) {
        switch ($action) {
            case 'verify':
                $this->verifyCaptcha();
                break;
            case 'update_session':
                $this->updateCaptchaSession();
                break;
            default:
                $this->sendErrorResponse('Invalid POST action');
                break;
        }
    }
    
    /**
     * Refresh CAPTCHA image
     * Gets a new random CAPTCHA and stores its ID in session
     */
    private function refreshCaptcha() {
        try {
            $randomCaptcha = $this->captcha->getRandomCaptcha();
            
            if ($randomCaptcha) {
                // Store CAPTCHA ID in session
                $_SESSION['captcha_id'] = $randomCaptcha['captcha_id'];
                
                // Return CAPTCHA info as JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'captcha' => [
                        'captcha_id' => $randomCaptcha['captcha_id'],
                        'image_path' => $randomCaptcha['image_path']
                    ]
                ]);
            } else {
                // If we couldn't get a CAPTCHA, reset all CAPTCHAs and try again
                $this->resetAllCaptchas();
                $randomCaptcha = $this->captcha->getRandomCaptcha();
                
                if ($randomCaptcha) {
                    // Store CAPTCHA ID in session
                    $_SESSION['captcha_id'] = $randomCaptcha['captcha_id'];
                    
                    // Return CAPTCHA info as JSON
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'captcha' => [
                            'captcha_id' => $randomCaptcha['captcha_id'],
                            'image_path' => $randomCaptcha['image_path']
                        ]
                    ]);
                } else {
                    ErrorLogger::log("Failed to generate CAPTCHA even after reset", "ERROR");
                    $this->sendErrorResponse('CAPTCHA service unavailable. Please try again later.');
                }
            }
        } catch (Exception $e) {
            ErrorLogger::log("CAPTCHA refresh exception: " . $e->getMessage(), "ERROR");
            $this->sendErrorResponse('An error occurred during CAPTCHA generation');
        }
    }
    
    /**
     * Reset all CAPTCHAs in the database
     * Makes all CAPTCHAs available again for use
     */
    private function resetAllCaptchas() {
        try {
            $query = "UPDATE captcha SET used = 0, expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            // If no existing CAPTCHAs, insert new ones
            $query = "SELECT COUNT(*) as count FROM captcha";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                // Insert fresh CAPTCHA data
                $query = "INSERT INTO captcha (captcha_text, image_path, expires_at, used) VALUES
                        ('Aeik2', 'image1.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                        ('ecb4f', 'image2.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                        ('7plBJ8', 'image3.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0),
                        ('24qVg', 'image4.jpg', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            ErrorLogger::log("Failed to reset CAPTCHAs: " . $e->getMessage(), "ERROR");
            throw $e;
        }
    }
    
    /**
     * Verify submitted CAPTCHA
     * Validates user input against stored CAPTCHA
     */
    private function verifyCaptcha() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['captcha_id']) || !isset($data['captcha_input'])) {
            $this->sendErrorResponse('Missing CAPTCHA parameters');
            return;
        }
        
        $captchaId = $data['captcha_id'];
        $userInput = $data['captcha_input'];
        
        // Debug log to help trace issues
        error_log("Verifying CAPTCHA: ID=$captchaId, Input=$userInput");
        
        $isValid = $this->captcha->verifyCaptcha($captchaId, $userInput);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $isValid,
            'message' => $isValid ? 'CAPTCHA verified' : 'Invalid CAPTCHA'
        ]);
    }
    
    /**
     * Update CAPTCHA session when refreshed
     * Stores the current CAPTCHA ID in session
     */
    private function updateCaptchaSession() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['captcha_id'])) {
            $_SESSION['captcha_id'] = $data['captcha_id'];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Session updated'
            ]);
        } else {
            $this->sendErrorResponse('Missing CAPTCHA ID');
        }
    }
    
    /**
     * Send error response as JSON
     * 
     * @param string $message Error message
     */
    private function sendErrorResponse($message) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
    
    /**
     * Get new CAPTCHA for login/registration forms
     * 
     * @return array|null CAPTCHA data or null on error
     */
    public function getNewCaptcha() {
        try {
            $captcha = $this->captcha->getRandomCaptcha();
            
            if ($captcha) {
                $_SESSION['captcha_id'] = $captcha['captcha_id'];
                return $captcha;
            } else {
                // Try to reset CAPTCHAs and get a new one
                $this->resetAllCaptchas();
                $captcha = $this->captcha->getRandomCaptcha();
                
                if ($captcha) {
                    $_SESSION['captcha_id'] = $captcha['captcha_id'];
                    return $captcha;
                }
            }
            
            return null;
        } catch (Exception $e) {
            ErrorLogger::log("Error getting new CAPTCHA: " . $e->getMessage(), "ERROR");
            return null;
        }
    }
}
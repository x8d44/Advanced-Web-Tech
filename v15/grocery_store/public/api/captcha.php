<?php
/**
 * captcha.php - CAPTCHA API Endpoint
 *
 * Handles CAPTCHA requests including generation, verification, 
 * and session management for secure user authentication.
 */

// Include necessary files with correct paths - Database class must be included before controller
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Captcha.php';
require_once __DIR__ . '/../../config/errorlogger.php';
require_once __DIR__ . '/../../controllers/CaptchaController.php';

// Do not start a session if one is already active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Handle the CAPTCHA request
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $controller = new CaptchaController($db);
    $controller->handleRequest();
} catch (Exception $e) {
    // Log any unexpected errors
    ErrorLogger::log("CAPTCHA API Error: " . $e->getMessage(), "ERROR");
    
    // Send generic error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
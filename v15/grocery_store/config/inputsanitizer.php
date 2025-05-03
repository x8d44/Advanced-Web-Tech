<?php
/**
 * inputsanitizer.php - Input Validation and Sanitization
 *
 * This file provides comprehensive input sanitization functionality to prevent
 * common web vulnerabilities like XSS, SQL Injection, and other injection attacks.
 * All user inputs throughout the application should be processed through these methods.
 *
 */

/**
 * InputSanitizer Class
 * 
 * Provides static methods for sanitizing and validating various types of input data
 */
class InputSanitizer {
    /**
     * Sanitize general input string
     * 
     * @param string $input Raw input string
     * @param bool $allow_html Whether to allow HTML tags
     * @return string Sanitized string
     */
    public static function sanitizeString($input, $allow_html = false) {
        // NULL check
        if ($input === null) {
            return '';
        }
        
        // Ensure input is string
        $input = (string)$input;
        
        // Remove whitespace from beginning and end
        $input = trim($input);
        
        // If HTML is not allowed, strip all HTML tags
        if (!$allow_html) {
            $input = strip_tags($input);
        }
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate and sanitize email address
     * 
     * @param string $email Raw email input
     * @return string|false Sanitized email or false if invalid
     */
    public static function sanitizeEmail($email) {
        // NULL check
        if ($email === null) {
            return false;
        }
        
        // Ensure input is string
        $email = (string)$email;
        
        // Remove all characters except letters, digits, and !#$%&'*+-/=?^_`{|}~@.[]
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // Validate email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        
        return false;
    }
    
    /**
     * Validate and sanitize phone number
     * 
     * @param string $phone Raw phone number input
     * @return string|false Sanitized phone number or false if invalid
     */
    public static function sanitizePhone($phone) {
        // NULL check
        if ($phone === null) {
            return false;
        }
        
        // Ensure input is string
        $phone = (string)$phone;
        
        // Remove any non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's exactly 10 digits (for standard phone numbers)
        if (strlen($digits) !== 10) {
            return false;
        }
        
        return $digits;
    }
    
    /**
     * Sanitize numeric input
     * 
     * @param mixed $input Raw numeric input
     * @param bool $allow_float Allow floating-point numbers
     * @return int|float|false Sanitized number or false if invalid
     */
    public static function sanitizeNumeric($input, $allow_float = false) {
        // Handle null or empty input
        if ($input === null || $input === '') {
            return false;
        }
        
        // Remove any non-numeric characters except decimal point if floats are allowed
        if ($allow_float) {
            $input = preg_replace('/[^0-9.]/', '', (string)$input);
            
            // Validate as float
            $sanitized = filter_var($input, FILTER_VALIDATE_FLOAT);
        } else {
            $input = preg_replace('/[^0-9]/', '', (string)$input);
            
            // Validate as integer
            $sanitized = filter_var($input, FILTER_VALIDATE_INT);
        }
        
        return $sanitized !== false ? $sanitized : false;
    }
    
    /**
     * Sanitize password
     * Ensures password meets basic security requirements
     * 
     * @param string $password Raw password input
     * @return string|false Validated password or false
     */
    public static function sanitizePassword($password) {
        // NULL check
        if ($password === null) {
            return false;
        }
        
        // Remove leading/trailing whitespace
        $password = trim((string)$password);
        
        // Check password strength
        // At least 8 characters, one uppercase, one lowercase, one number
        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            return $password;
        }
        
        return false;
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url Raw URL input
     * @return string|false Sanitized URL or false if invalid
     */
    public static function sanitizeUrl($url) {
        // NULL check
        if ($url === null) {
            return false;
        }
        
        // Ensure input is string
        $url = (string)$url;
        
        // Sanitize URL
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Validate URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        return false;
    }
    
    /**
     * Sanitize array of input values
     * 
     * @param array $input_array Array of input values
     * @param array $rules Array of sanitization rules (key => rule type)
     * @return array Sanitized array
     */
    public static function sanitizeArray($input_array, $rules) {
        if (!is_array($input_array) || !is_array($rules)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($rules as $key => $rule) {
            if (!isset($input_array[$key])) {
                continue;
            }
            
            switch ($rule) {
                case 'string':
                    $sanitized[$key] = self::sanitizeString($input_array[$key]);
                    break;
                    
                case 'html':
                    $sanitized[$key] = self::sanitizeString($input_array[$key], true);
                    break;
                    
                case 'email':
                    $sanitized[$key] = self::sanitizeEmail($input_array[$key]);
                    break;
                    
                case 'phone':
                    $sanitized[$key] = self::sanitizePhone($input_array[$key]);
                    break;
                    
                case 'int':
                    $sanitized[$key] = self::sanitizeNumeric($input_array[$key], false);
                    break;
                    
                case 'float':
                    $sanitized[$key] = self::sanitizeNumeric($input_array[$key], true);
                    break;
                    
                case 'password':
                    $sanitized[$key] = self::sanitizePassword($input_array[$key]);
                    break;
                    
                case 'url':
                    $sanitized[$key] = self::sanitizeUrl($input_array[$key]);
                    break;
                    
                default:
                    $sanitized[$key] = self::sanitizeString($input_array[$key]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize data for database insertion
     * 
     * @param mixed $data Data to sanitize
     * @param PDO $db Database connection
     * @return mixed Sanitized data
     */
    public static function sanitizeForDatabase($data, $db) {
        if (is_string($data)) {
            return $db->quote($data);
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeForDatabase($value, $db);
            }
            return $data;
        } elseif (is_null($data)) {
            return 'NULL';
        }
        
        return $data;
    }
}
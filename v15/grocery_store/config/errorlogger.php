<?php
/**
 * errorlogger.php - Application Error Logging System
 *
 * This file provides comprehensive error logging functionality for the application.
 * It handles errors, exceptions, and custom log messages with different severity levels.
 * Log files are organized by date to facilitate troubleshooting and monitoring.
 *
 */

/**
 * ErrorLogger Class
 * 
 * Provides static methods for logging errors, exceptions, and messages
 */
class ErrorLogger {
    /** @var string Log directory path */
    private static $log_dir;
    
    /** @var array Valid log levels */
    private static $valid_levels = ['INFO', 'WARNING', 'ERROR', 'CRITICAL', 'SECURITY'];
    
    /** @var boolean Whether the logger has been initialized */
    private static $initialized = false;
    
    /**
     * Initialize log directory
     * 
     * @param string|null $log_directory Custom log directory path
     * @return void
     */
    public static function init($log_directory = null) {
        // If no directory provided, use default in project root
        if ($log_directory === null) {
            $log_directory = __DIR__ . '/../logs';
        }
        
        // Create log directory if it doesn't exist
        if (!is_dir($log_directory)) {
            if (!mkdir($log_directory, 0755, true)) {
                // If directory creation fails, fallback to system temp directory
                $log_directory = sys_get_temp_dir() . '/grocery_store_logs';
                if (!is_dir($log_directory)) {
                    mkdir($log_directory, 0755, true);
                }
            }
        }
        
        self::$log_dir = $log_directory;
        self::$initialized = true;
    }
    
    /**
     * Log error message with context information
     * 
     * @param string $message Error message
     * @param string $level Error level (INFO, WARNING, ERROR, CRITICAL, SECURITY)
     * @param array $context Additional context information
     * @return boolean Success or failure
     */
    public static function log($message, $level = 'ERROR', $context = []) {
        // Ensure log directory is set
        if (!self::$initialized) {
            self::init();
        }
        
        // Validate log level
        $level = strtoupper($level);
        if (!in_array($level, self::$valid_levels)) {
            $level = 'ERROR';
        }
        
        // Generate log filename based on current date
        $log_file = self::$log_dir . '/app_' . date('Y-m-d') . '.log';
        
        // Add request information to context
        if (!isset($context['request_uri']) && isset($_SERVER['REQUEST_URI'])) {
            $context['request_uri'] = $_SERVER['REQUEST_URI'];
        }
        
        if (!isset($context['user_id']) && isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }
        
        // Prepare log entry
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' ' . json_encode($context) : '';
        
        // Construct log message
        $log_entry = "[{$timestamp}] [{$level}] {$message}{$context_str}" . PHP_EOL;
        
        // Attempt to write to log file
        try {
            if (file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX) === false) {
                // If file write fails, try system error log as fallback
                error_log($log_entry);
                return false;
            }
            return true;
        } catch (Exception $e) {
            // Last resort fallback to PHP's error_log
            error_log("Failed to write to log file: " . $e->getMessage());
            error_log($log_entry);
            return false;
        }
    }
    
    /**
     * Log exception details
     * 
     * @param Throwable $exception Exception to log
     * @return boolean Success or failure
     */
    public static function logException($exception) {
        $message = "Exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . 
                   " on line " . $exception->getLine();
        
        return self::log($message, 'CRITICAL', [
            'exception_class' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    /**
     * Handle and log PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line number of error
     * @return boolean True to prevent standard PHP error handler from running
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        // Check if error reporting is disabled for this error type
        if (!(error_reporting() & $errno)) {
            return true;
        }
        
        // Map PHP error levels to our log levels
        $levels = [
            E_ERROR => 'CRITICAL',
            E_WARNING => 'WARNING',
            E_PARSE => 'CRITICAL',
            E_NOTICE => 'INFO',
            E_CORE_ERROR => 'CRITICAL',
            E_CORE_WARNING => 'WARNING',
            E_COMPILE_ERROR => 'CRITICAL',
            E_COMPILE_WARNING => 'WARNING',
            E_USER_ERROR => 'ERROR',
            E_USER_WARNING => 'WARNING',
            E_USER_NOTICE => 'INFO',
            // E_STRICT is deprecated in PHP 8.4.5, so we'll check if it exists before using it
            E_RECOVERABLE_ERROR => 'ERROR',
            E_DEPRECATED => 'INFO',
            E_USER_DEPRECATED => 'INFO'
        ];

        // Add E_STRICT to the levels array only if it's defined
        if (defined('E_STRICT')) {
            $levels[E_STRICT] = 'INFO';
        }
        
        $level = $levels[$errno] ?? 'ERROR';
        
        self::log($errstr, $level, [
            'file' => $errfile,
            'line' => $errline,
            'error_number' => $errno
        ]);
        
        // Fatal errors should throw exceptions in development for easier debugging
        if ($level === 'CRITICAL' && ini_get('display_errors')) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        
        // Don't execute PHP's internal error handler
        return true;
    }
    
    /**
     * Get path to latest log file
     * 
     * @return string|null Path to log file or null if not found
     */
    public static function getLatestLogFile() {
        if (!self::$initialized) {
            self::init();
        }
        
        $today_log = self::$log_dir . '/app_' . date('Y-m-d') . '.log';
        
        if (file_exists($today_log)) {
            return $today_log;
        }
        
        return null;
    }
    
    /**
     * Clear all log files
     * 
     * @param boolean $keepToday Whether to keep today's log file
     * @return int Number of log files deleted
     */
    public static function clearLogs($keepToday = true) {
        if (!self::$initialized) {
            self::init();
        }
        
        $count = 0;
        $today_log = 'app_' . date('Y-m-d') . '.log';
        
        foreach (glob(self::$log_dir . '/app_*.log') as $file) {
            if ($keepToday && basename($file) === $today_log) {
                continue;
            }
            
            if (unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
}

// Initialize error logging
ErrorLogger::init();
<?php
/**
 * Security Functions for Wedding Invitation Platform
 * Contains security-related functions and middleware
 */

/**
 * CSRF Protection
 */
class CSRFProtection {
    private static $token_name = 'csrf_token';
    
    public static function generateToken() {
        if (!isset($_SESSION[self::$token_name])) {
            $_SESSION[self::$token_name] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$token_name];
    }
    
    public static function validateToken($token) {
        return isset($_SESSION[self::$token_name]) && hash_equals($_SESSION[self::$token_name], $token);
    }
    
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

/**
 * Rate Limiting
 */
class RateLimiter {
    private $db;
    private $max_attempts;
    private $time_window;
    
    public function __construct($db, $max_attempts = 5, $time_window = 900) { // 15 minutes
        $this->db = $db;
        $this->max_attempts = $max_attempts;
        $this->time_window = $time_window;
        
        // Create rate limit table if not exists
        $this->createTable();
    }
    
    private function createTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                action VARCHAR(50) NOT NULL,
                attempts INT DEFAULT 1,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ip_action (ip_address, action),
                INDEX idx_last_attempt (last_attempt)
            )
        ");
    }
    
    public function checkLimit($action, $ip_address = null) {
        if (!$ip_address) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        // Clean old records
        $this->cleanOldRecords();
        
        // Check current attempts
        $current = $this->db->fetch("
            SELECT attempts FROM rate_limits 
            WHERE ip_address = ? AND action = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ", [$ip_address, $action, $this->time_window]);
        
        return !$current || $current['attempts'] < $this->max_attempts;
    }
    
    public function recordAttempt($action, $ip_address = null) {
        if (!$ip_address) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        $existing = $this->db->fetch("
            SELECT id, attempts FROM rate_limits 
            WHERE ip_address = ? AND action = ? AND last_attempt > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ", [$ip_address, $action, $this->time_window]);
        
        if ($existing) {
            $this->db->query("
                UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() 
                WHERE id = ?
            ", [$existing['id']]);
        } else {
            $this->db->query("
                INSERT INTO rate_limits (ip_address, action, attempts) 
                VALUES (?, ?, 1)
            ", [$ip_address, $action]);
        }
    }
    
    private function cleanOldRecords() {
        $this->db->query("
            DELETE FROM rate_limits 
            WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ", [$this->time_window * 2]);
    }
    
    public function getRemainingTime($action, $ip_address = null) {
        if (!$ip_address) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        
        $record = $this->db->fetch("
            SELECT last_attempt FROM rate_limits 
            WHERE ip_address = ? AND action = ? AND attempts >= ?
        ", [$ip_address, $action, $this->max_attempts]);
        
        if ($record) {
            $elapsed = time() - strtotime($record['last_attempt']);
            return max(0, $this->time_window - $elapsed);
        }
        
        return 0;
    }
}

/**
 * Input Validation and Sanitization
 */
class InputValidator {
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    public static function validatePassword($password) {
        // At least 8 characters, contains letters and numbers
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function sanitizeFilename($filename) {
        // Remove unsafe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        // Prevent directory traversal
        $filename = str_replace(['../', '.\\', '..\\'], '', $filename);
        return $filename;
    }
    
    public static function validateFileUpload($file, $allowed_types, $max_size) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded or invalid upload'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error: ' . $file['error']];
        }
        
        if ($file['size'] > $max_size) {
            return ['valid' => false, 'error' => 'File size exceeds limit'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_types)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg'
        ];
        
        if (!isset($allowed_mimes[$extension]) || $allowed_mimes[$extension] !== $mime_type) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        return ['valid' => true];
    }
}

/**
 * XSS Protection
 */
class XSSProtection {
    
    public static function clean($input, $allow_html = false) {
        if (is_array($input)) {
            return array_map([self::class, 'clean'], $input);
        }
        
        if ($allow_html) {
            // Allow only safe HTML tags
            $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
            return strip_tags($input, $allowed_tags);
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function cleanOutput($output) {
        return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

/**
 * SQL Injection Protection (additional layer)
 */
class SQLProtection {
    
    public static function escapeString($string) {
        return addslashes($string);
    }
    
    public static function validateTableName($table_name) {
        // Only allow alphanumeric characters and underscores
        return preg_match('/^[a-zA-Z0-9_]+$/', $table_name);
    }
    
    public static function validateColumnName($column_name) {
        // Only allow alphanumeric characters and underscores
        return preg_match('/^[a-zA-Z0-9_]+$/', $column_name);
    }
}

/**
 * Security Headers
 */
class SecurityHeaders {
    
    public static function setSecurityHeaders() {
        // Prevent XSS attacks
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self';");
        
        // Strict Transport Security (if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Remove server information
        header_remove('X-Powered-By');
    }
}

/**
 * Session Security
 */
class SessionSecurity {
    
    public static function secureSession() {
        // Configure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check for session hijacking
        $current_fingerprint = self::generateFingerprint();
        if (isset($_SESSION['fingerprint'])) {
            if ($_SESSION['fingerprint'] !== $current_fingerprint) {
                // Possible session hijacking
                session_destroy();
                return false;
            }
        } else {
            $_SESSION['fingerprint'] = $current_fingerprint;
        }
        
        return true;
    }
    
    private static function generateFingerprint() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $user_agent . $accept_language . $accept_encoding);
    }
    
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}

/**
 * Password Security
 */
class PasswordSecurity {
    
    public static function hash($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    public static function verify($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
    
    public static function generateSecurePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
}

/**
 * Logging Security Events
 */
class SecurityLogger {
    
    public static function logSecurityEvent($event, $details = '', $level = 'INFO') {
        $log_file = '../logs/security.log';
        
        if (!file_exists('../logs')) {
            mkdir('../logs', 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $user_id = $_SESSION['user_id'] ?? 'anonymous';
        
        $log_entry = "[$timestamp] [$level] Event: $event | User: $user_id | IP: $ip | Details: $details | User-Agent: $user_agent" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public static function logFailedLogin($username, $ip) {
        self::logSecurityEvent('FAILED_LOGIN', "Username: $username from IP: $ip", 'WARNING');
    }
    
    public static function logSuccessfulLogin($username, $ip) {
        self::logSecurityEvent('SUCCESSFUL_LOGIN', "Username: $username from IP: $ip", 'INFO');
    }
    
    public static function logSuspiciousActivity($activity, $details) {
        self::logSecurityEvent('SUSPICIOUS_ACTIVITY', "$activity - $details", 'ALERT');
    }
}

/**
 * IP Whitelist/Blacklist
 */
class IPFilter {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->createTable();
    }
    
    private function createTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS ip_filters (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                type ENUM('whitelist', 'blacklist') NOT NULL,
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_type (ip_address, type)
            )
        ");
    }
    
    public function isBlacklisted($ip) {
        $result = $this->db->fetch("
            SELECT id FROM ip_filters 
            WHERE ip_address = ? AND type = 'blacklist'
        ", [$ip]);
        
        return (bool)$result;
    }
    
    public function isWhitelisted($ip) {
        $result = $this->db->fetch("
            SELECT id FROM ip_filters 
            WHERE ip_address = ? AND type = 'whitelist'
        ", [$ip]);
        
        return (bool)$result;
    }
    
    public function addToBlacklist($ip, $reason = '') {
        return $this->db->query("
            INSERT INTO ip_filters (ip_address, type, reason) 
            VALUES (?, 'blacklist', ?)
            ON DUPLICATE KEY UPDATE reason = VALUES(reason)
        ", [$ip, $reason]);
    }
    
    public function addToWhitelist($ip, $reason = '') {
        return $this->db->query("
            INSERT INTO ip_filters (ip_address, type, reason) 
            VALUES (?, 'whitelist', ?)
            ON DUPLICATE KEY UPDATE reason = VALUES(reason)
        ", [$ip, $reason]);
    }
}

// Initialize security
function initializeSecurity() {
    // Set security headers
    SecurityHeaders::setSecurityHeaders();
    
    // Secure session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    SessionSecurity::secureSession();
}

// Auto-initialize security if not in CLI mode
if (php_sapi_name() !== 'cli') {
    initializeSecurity();
}
?>
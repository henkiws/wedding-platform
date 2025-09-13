<?php
// config.php - Main configuration file

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Database@123.');
define('DB_NAME', 'wedding_invitation_platform');

// Site configuration
define('SITE_URL', 'http://localhost/wedding-platform');
define('SITE_NAME', 'Wevitation');
define('SITE_DESCRIPTION', 'Platform Undangan Pernikahan Digital');

// Upload directories
define('UPLOAD_DIR', 'uploads/');
define('THEME_DIR', 'assets/themes/');
define('GALLERY_DIR', 'uploads/gallery/');
define('MUSIC_DIR', 'uploads/music/');

// File upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'avi', 'mov']);

// Subscription limits
define('FREE_GUEST_LIMIT', 50);
define('FREE_GALLERY_LIMIT', 10);
define('FREE_DURATION_DAYS', 30);

// Database connection class
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    public $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4",
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

// Utility functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($text) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
    return trim($slug, '-');
}

function formatDate($date, $format = 'd M Y') {
    return date($format, strtotime($date));
}

function uploadFile($file, $directory, $allowed_types) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        throw new Exception('No file uploaded');
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('File type not allowed');
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size too large');
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $directory . $filename;
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    } else {
        throw new Exception('Failed to upload file');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = new Database();
    return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function hasPermission($required_plan) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $plans = ['free' => 1, 'premium' => 2, 'business' => 3];
    $user_level = $plans[$user['subscription_plan']] ?? 0;
    $required_level = $plans[$required_plan] ?? 0;
    
    return $user_level >= $required_level;
}

// Start session
session_start();

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
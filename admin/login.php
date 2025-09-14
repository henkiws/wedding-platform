<?php
// admin/login.php - Admin Login
require_once '../config.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Create admin table and default admin if not exists
$db = new Database();

// Create admin table
$db->query("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('super_admin', 'admin') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Create default admin if no admin exists
$admin_count = $db->fetch("SELECT COUNT(*) as count FROM admins")['count'];
if ($admin_count == 0) {
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $db->query("
        INSERT INTO admins (username, email, password, full_name, role, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ", ['admin', 'admin@wevitation.com', $default_password, 'Administrator', 'super_admin', 'active']);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Check admin credentials
        $admin = $db->fetch("
            SELECT * FROM admins 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ", [$username, $username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Update last login
            $db->query("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
            
            // Redirect to dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ec4899',
                        secondary: '#8b5cf6'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-primary/10 via-secondary/10 to-primary/20 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-heart text-primary text-3xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Admin Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Sign in to access the <?php echo SITE_NAME; ?> admin panel
                </p>
            </div>

            <!-- Login Form -->
            <div class="bg-white rounded-xl shadow-xl p-8">
                <?php if ($error): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2"></i>Username or Email
                        </label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                               placeholder="Enter your username or email">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition"
                                   placeholder="Enter your password">
                            <button type="button" onclick="togglePassword()" 
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-primary hover:text-primary/80 transition">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-secondary hover:from-primary/90 hover:to-secondary/90 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </button>
                </form>

                <!-- Default Credentials Info -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Default Admin Credentials
                    </h4>
                    <div class="text-sm text-blue-700 space-y-1">
                        <p><strong>Username:</strong> admin</p>
                        <p><strong>Password:</strong> admin123</p>
                        <p class="text-xs text-blue-600 mt-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Please change these credentials after first login
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    © 2024 <?php echo SITE_NAME; ?>. All rights reserved.
                </p>
                <div class="mt-2">
                    <a href="../" class="text-sm text-primary hover:text-primary/80 transition">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back to Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Animation -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-secondary/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-primary/3 rounded-full blur-2xl animate-ping"></div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Add loading state to login button
        document.querySelector('form').addEventListener('submit', function() {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            button.disabled = true;
        });

        // Add some nice animations
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.bg-white');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.5s ease-out';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
<?php
/**
 * Wedding Invitation Platform - Admin Installation Script
 * This script sets up the admin system and database
 */

// Security check - remove this file after installation
if (file_exists('admin_installed.lock')) {
    die('Admin system is already installed. Remove admin_installed.lock file to reinstall.');
}

require_once '../config.php';

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle installation steps
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check_requirements':
            $step = 2;
            break;
            
        case 'setup_database':
            try {
                $db = new Database();
                
                // Read and execute database schema
                $schema_file = '../database/wedding_database.sql';
                if (file_exists($schema_file)) {
                    $schema = file_get_contents($schema_file);
                    $statements = array_filter(array_map('trim', explode(';', $schema)));
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement) && !preg_match('/^(--|\#)/', $statement)) {
                            $db->pdo->exec($statement);
                        }
                    }
                    
                    $success = 'Database setup completed successfully!';
                    $step = 3;
                } else {
                    $error = 'Database schema file not found!';
                }
            } catch (Exception $e) {
                $error = 'Database setup failed: ' . $e->getMessage();
            }
            break;
            
        case 'create_admin':
            try {
                $db = new Database();
                
                $username = sanitize($_POST['admin_username']);
                $email = sanitize($_POST['admin_email']);
                $password = $_POST['admin_password'];
                $full_name = sanitize($_POST['admin_name']);
                
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('All fields are required');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address');
                }
                
                if (strlen($password) < 8) {
                    throw new Exception('Password must be at least 8 characters long');
                }
                
                // Check if admin already exists
                $existing = $db->fetch("SELECT id FROM admins WHERE username = ? OR email = ?", [$username, $email]);
                if ($existing) {
                    throw new Exception('Admin with this username or email already exists');
                }
                
                // Create admin account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $result = $db->query("
                    INSERT INTO admins (username, email, password, full_name, role, status) 
                    VALUES (?, ?, ?, ?, 'super_admin', 'active')
                ", [$username, $email, $hashed_password, $full_name]);
                
                if ($result) {
                    $success = 'Admin account created successfully!';
                    $step = 4;
                } else {
                    throw new Exception('Failed to create admin account');
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'setup_directories':
            try {
                $directories = [
                    '../uploads',
                    '../uploads/covers',
                    '../uploads/gallery', 
                    '../uploads/music',
                    '../uploads/qrcodes',
                    '../uploads/themes',
                    '../logs',
                    '../cache',
                    '../backups'
                ];
                
                foreach ($directories as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    chmod($dir, 0755);
                }
                
                // Create .htaccess for uploads
                $upload_htaccess = '../uploads/.htaccess';
                if (!file_exists($upload_htaccess)) {
                    file_put_contents($upload_htaccess, "Options -Indexes\nOptions -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n");
                }
                
                $success = 'Directories created successfully!';
                $step = 5;
            } catch (Exception $e) {
                $error = 'Failed to create directories: ' . $e->getMessage();
            }
            break;
            
        case 'finish_installation':
            // Create installation lock file
            file_put_contents('admin_installed.lock', date('Y-m-d H:i:s'));
            
            // Redirect to admin login
            header('Location: login.php?installed=1');
            exit;
            break;
    }
}

// Check system requirements
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'FileInfo Extension' => extension_loaded('fileinfo'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'Directory Writable' => is_writable('../'),
        'Uploads Directory' => is_dir('../uploads') ? is_writable('../uploads') : is_writable('../'),
        'Logs Directory' => is_dir('../logs') ? is_writable('../logs') : is_writable('../')
    ];
    
    return $requirements;
}

$requirements = checkRequirements();
$all_requirements_met = !in_array(false, $requirements);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Installation - <?php echo SITE_NAME; ?></title>
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
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-heart text-primary text-3xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Admin Installation
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Set up your <?php echo SITE_NAME; ?> admin panel
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="bg-white rounded-xl shadow-xl p-8">
                <div class="flex items-center justify-between mb-8">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full <?php echo $step >= $i ? 'bg-primary text-white' : 'bg-gray-300 text-gray-600'; ?>">
                            <?php echo $i; ?>
                        </div>
                        <?php if ($i < 5): ?>
                        <div class="w-16 h-1 <?php echo $step > $i ? 'bg-primary' : 'bg-gray-300'; ?> ml-2"></div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- Messages -->
                <?php if ($error): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step Content -->
                <?php if ($step == 1): ?>
                <!-- Step 1: Welcome -->
                <div class="text-center">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Welcome to Admin Installation</h3>
                    <p class="text-gray-600 mb-6">
                        This installation wizard will help you set up the admin panel for your wedding invitation platform.
                        Please make sure you have your database credentials ready.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="check_requirements">
                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-arrow-right mr-2"></i>
                            Start Installation
                        </button>
                    </form>
                </div>

                <?php elseif ($step == 2): ?>
                <!-- Step 2: System Requirements -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">System Requirements Check</h3>
                    <div class="space-y-3">
                        <?php foreach ($requirements as $requirement => $met): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg <?php echo $met ? 'bg-green-50' : 'bg-red-50'; ?>">
                            <span class="<?php echo $met ? 'text-green-700' : 'text-red-700'; ?>"><?php echo $requirement; ?></span>
                            <span class="<?php echo $met ? 'text-green-600' : 'text-red-600'; ?>">
                                <i class="fas <?php echo $met ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($all_requirements_met): ?>
                    <div class="mt-6 text-center">
                        <form method="POST">
                            <input type="hidden" name="action" value="setup_database">
                            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                                <i class="fas fa-database mr-2"></i>
                                Setup Database
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="mt-6 p-4 bg-yellow-100 border border-yellow-400 rounded-lg">
                        <p class="text-yellow-700">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Please fix the requirements above before continuing.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($step == 3): ?>
                <!-- Step 3: Create Admin Account -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Create Admin Account</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_admin">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="admin_name" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                   placeholder="Administrator Name">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="admin_username" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                   placeholder="admin">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="admin_email" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                   placeholder="admin@example.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="admin_password" required minlength="8"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                   placeholder="Strong password (min 8 characters)">
                        </div>
                        
                        <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-user-plus mr-2"></i>
                            Create Admin Account
                        </button>
                    </form>
                </div>

                <?php elseif ($step == 4): ?>
                <!-- Step 4: Setup Directories -->
                <div class="text-center">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Setup Directories</h3>
                    <p class="text-gray-600 mb-6">
                        Now we'll create the necessary directories for uploads, logs, and cache files.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="setup_directories">
                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-folder-plus mr-2"></i>
                            Create Directories
                        </button>
                    </form>
                </div>

                <?php elseif ($step == 5): ?>
                <!-- Step 5: Installation Complete -->
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-check text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Installation Complete!</h3>
                    <p class="text-gray-600 mb-6">
                        Your admin panel has been successfully installed. You can now log in and start managing your wedding invitation platform.
                    </p>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Important Security Notes
                        </h4>
                        <ul class="text-sm text-blue-700 text-left space-y-1">
                            <li>• Delete this install.php file for security</li>
                            <li>• Change default database passwords</li>
                            <li>• Enable HTTPS in production</li>
                            <li>• Configure regular backups</li>
                            <li>• Update email SMTP settings</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="finish_installation">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Go to Admin Login
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-600">
                <p>© 2024 <?php echo SITE_NAME; ?>. Wedding Invitation Platform.</p>
            </div>
        </div>
    </div>
</body>
</html>
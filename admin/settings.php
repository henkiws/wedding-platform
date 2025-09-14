<?php
// admin/settings.php - System Settings
require_once '../config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Create settings table if it doesn't exist
$db->query("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'textarea', 'select') DEFAULT 'text',
        category VARCHAR(50) DEFAULT 'general',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

// Initialize default settings if not exist
$default_settings = [
    ['setting_key' => 'site_name', 'setting_value' => SITE_NAME, 'setting_type' => 'text', 'category' => 'general', 'description' => 'Website name displayed across the platform'],
    ['setting_key' => 'site_description', 'setting_value' => SITE_DESCRIPTION, 'setting_type' => 'textarea', 'category' => 'general', 'description' => 'Website description for SEO'],
    ['setting_key' => 'admin_email', 'setting_value' => 'admin@wevitation.com', 'setting_type' => 'text', 'category' => 'general', 'description' => 'Administrator email address'],
    ['setting_key' => 'registration_enabled', 'setting_value' => '1', 'setting_type' => 'boolean', 'category' => 'users', 'description' => 'Allow new user registrations'],
    ['setting_key' => 'auto_approve_users', 'setting_value' => '1', 'setting_type' => 'boolean', 'category' => 'users', 'description' => 'Automatically approve new user accounts'],
    ['setting_key' => 'free_plan_limit_days', 'setting_value' => '30', 'setting_type' => 'number', 'category' => 'subscriptions', 'description' => 'Free plan duration in days'],
    ['setting_key' => 'free_plan_guest_limit', 'setting_value' => '50', 'setting_type' => 'number', 'category' => 'subscriptions', 'description' => 'Guest limit for free plans'],
    ['setting_key' => 'free_plan_gallery_limit', 'setting_value' => '10', 'setting_type' => 'number', 'category' => 'subscriptions', 'description' => 'Gallery photo limit for free plans'],
    ['setting_key' => 'premium_plan_price', 'setting_value' => '99000', 'setting_type' => 'number', 'category' => 'subscriptions', 'description' => 'Premium plan price in IDR'],
    ['setting_key' => 'business_plan_price', 'setting_value' => '199000', 'setting_type' => 'number', 'category' => 'subscriptions', 'description' => 'Business plan price in IDR'],
    ['setting_key' => 'max_file_size_mb', 'setting_value' => '5', 'setting_type' => 'number', 'category' => 'uploads', 'description' => 'Maximum file upload size in MB'],
    ['setting_key' => 'allowed_image_types', 'setting_value' => 'jpg,jpeg,png,gif', 'setting_type' => 'text', 'category' => 'uploads', 'description' => 'Allowed image file extensions (comma separated)'],
    ['setting_key' => 'allowed_audio_types', 'setting_value' => 'mp3,wav,ogg', 'setting_type' => 'text', 'category' => 'uploads', 'description' => 'Allowed audio file extensions (comma separated)'],
    ['setting_key' => 'email_notifications', 'setting_value' => '1', 'setting_type' => 'boolean', 'category' => 'notifications', 'description' => 'Enable email notifications'],
    ['setting_key' => 'smtp_enabled', 'setting_value' => '0', 'setting_type' => 'boolean', 'category' => 'email', 'description' => 'Enable SMTP for email sending'],
    ['setting_key' => 'smtp_host', 'setting_value' => 'smtp.gmail.com', 'setting_type' => 'text', 'category' => 'email', 'description' => 'SMTP server hostname'],
    ['setting_key' => 'smtp_port', 'setting_value' => '587', 'setting_type' => 'number', 'category' => 'email', 'description' => 'SMTP server port'],
    ['setting_key' => 'smtp_username', 'setting_value' => '', 'setting_type' => 'text', 'category' => 'email', 'description' => 'SMTP username'],
    ['setting_key' => 'smtp_password', 'setting_value' => '', 'setting_type' => 'text', 'category' => 'email', 'description' => 'SMTP password'],
    ['setting_key' => 'maintenance_mode', 'setting_value' => '0', 'setting_type' => 'boolean', 'category' => 'system', 'description' => 'Put site in maintenance mode'],
    ['setting_key' => 'analytics_code', 'setting_value' => '', 'setting_type' => 'textarea', 'category' => 'analytics', 'description' => 'Google Analytics or other tracking code'],
    ['setting_key' => 'custom_css', 'setting_value' => '', 'setting_type' => 'textarea', 'category' => 'appearance', 'description' => 'Custom CSS to be added to all pages'],
    ['setting_key' => 'footer_text', 'setting_value' => 'Made with ❤️ by Wevitation Team', 'setting_type' => 'text', 'category' => 'appearance', 'description' => 'Footer text displayed on all pages']
];

foreach ($default_settings as $setting) {
    $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = ?", [$setting['setting_key']]);
    if (!$existing) {
        $db->query("
            INSERT INTO settings (setting_key, setting_value, setting_type, category, description) 
            VALUES (?, ?, ?, ?, ?)
        ", [$setting['setting_key'], $setting['setting_value'], $setting['setting_type'], $setting['category'], $setting['description']]);
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    $updated_count = 0;
    
    foreach ($_POST['settings'] as $key => $value) {
        // Handle boolean values
        if (!isset($value)) {
            $value = '0';
        }
        
        $result = $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        if ($result) {
            $updated_count++;
        }
    }
    
    if ($updated_count > 0) {
        $message = "Settings updated successfully ($updated_count settings changed)";
    } else {
        $error = 'No settings were updated';
    }
}

// Handle cache clear
if (isset($_GET['action']) && $_GET['action'] == 'clear_cache') {
    // Clear various cache files/directories
    $cache_cleared = false;
    
    // Clear file cache if exists
    if (is_dir('../cache')) {
        $files = glob('../cache/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cache_cleared = true;
            }
        }
    }
    
    // Clear session files if needed
    if (ini_get('session.save_handler') == 'files') {
        $session_path = session_save_path();
        if ($session_path && is_dir($session_path)) {
            $files = glob($session_path . '/sess_*');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 3600) { // Clear sessions older than 1 hour
                    unlink($file);
                    $cache_cleared = true;
                }
            }
        }
    }
    
    $message = $cache_cleared ? 'Cache cleared successfully' : 'No cache files found to clear';
}

// Handle database optimization
if (isset($_GET['action']) && $_GET['action'] == 'optimize_db') {
    try {
        $tables = ['users', 'invitations', 'guests', 'rsvps', 'guest_messages', 'themes', 'settings'];
        foreach ($tables as $table) {
            $db->query("OPTIMIZE TABLE $table");
        }
        $message = 'Database optimized successfully';
    } catch (Exception $e) {
        $error = 'Database optimization failed: ' . $e->getMessage();
    }
}

// Get all settings grouped by category
$all_settings = $db->fetchAll("SELECT * FROM settings ORDER BY category, setting_key");
$settings_by_category = [];
foreach ($all_settings as $setting) {
    $settings_by_category[$setting['category']][] = $setting;
}

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'disk_free_space' => disk_free_space('.') ? round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB' : 'Unknown'
];

// Get recent error logs
$error_logs = [];
if (file_exists('../logs/error.log')) {
    $log_content = file_get_contents('../logs/error.log');
    $log_lines = array_slice(array_filter(explode("\n", $log_content)), -10);
    $error_logs = array_reverse($log_lines);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?> Admin</title>
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
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-gray-900">
                        <i class="fas fa-heart text-primary mr-2"></i>
                        <?php echo SITE_NAME; ?> Admin
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../" target="_blank" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-external-link-alt mr-1"></i> View Site
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="users.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-users mr-3"></i>
                        Users Management
                    </a>
                    <a href="invitations.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-envelope mr-3"></i>
                        Invitations
                    </a>
                    <a href="themes.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-palette mr-3"></i>
                        Themes
                    </a>
                    <a href="reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-chart-bar mr-3"></i>
                        Reports
                    </a>
                    <a href="settings.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
                        <i class="fas fa-cog mr-3"></i>
                        Settings
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <!-- Page Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">System Settings</h2>
                        <p class="text-gray-600">Configure platform settings and system preferences</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="?action=clear_cache" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-broom mr-1"></i> Clear Cache
                        </a>
                        <a href="?action=optimize_db" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-database mr-1"></i> Optimize DB
                        </a>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Settings Tabs -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                    data-tab="general">
                                <i class="fas fa-cog mr-2"></i>General
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                    data-tab="users">
                                <i class="fas fa-users mr-2"></i>Users
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                    data-tab="subscriptions">
                                <i class="fas fa-crown mr-2"></i>Subscriptions
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                    data-tab="email">
                                <i class="fas fa-envelope mr-2"></i>Email
                            </button>
                            <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                                    data-tab="system">
                                <i class="fas fa-server mr-2"></i>System
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Settings Form -->
                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- Settings Sections -->
                    <?php foreach ($settings_by_category as $category => $settings): ?>
                    <div class="tab-content hidden" data-category="<?php echo $category; ?>">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 capitalize"><?php echo ucfirst($category); ?> Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <?php foreach ($settings as $setting): ?>
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                                        </label>
                                        <?php if ($setting['description']): ?>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($setting['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lg:col-span-2">
                                        <?php if ($setting['setting_type'] == 'boolean'): ?>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                   value="1" 
                                                   <?php echo $setting['setting_value'] ? 'checked' : ''; ?>
                                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                                            <span class="ml-2 text-sm text-gray-700">Enable</span>
                                        </label>
                                        <?php elseif ($setting['setting_type'] == 'textarea'): ?>
                                        <textarea name="settings[<?php echo $setting['setting_key']; ?>]" 
                                                  rows="4" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                        <?php elseif ($setting['setting_type'] == 'number'): ?>
                                        <input type="number" 
                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <?php else: ?>
                                        <input type="text" 
                                               name="settings[<?php echo $setting['setting_key']; ?>]" 
                                               value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- System Information Tab -->
                    <div class="tab-content hidden" data-category="system-info">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- System Information -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                                <div class="p-6 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <?php foreach ($system_info as $key => $value): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700"><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</span>
                                        <span class="text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Recent Error Logs -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                                <div class="p-6 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900">Recent Error Logs</h3>
                                </div>
                                <div class="p-6">
                                    <?php if (empty($error_logs)): ?>
                                    <p class="text-sm text-gray-500 italic">No recent errors found</p>
                                    <?php else: ?>
                                    <div class="space-y-2 max-h-64 overflow-y-auto">
                                        <?php foreach ($error_logs as $log): ?>
                                        <div class="text-xs font-mono text-red-600 bg-red-50 p-2 rounded">
                                            <?php echo htmlspecialchars($log); ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-8 py-3 rounded-lg font-medium transition">
                            <i class="fas fa-save mr-2"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Show first tab by default
            if (tabButtons.length > 0) {
                tabButtons[0].click();
            }
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.dataset.tab;
                    
                    // Update button states
                    tabButtons.forEach(btn => {
                        btn.classList.remove('border-primary', 'text-primary');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    });
                    
                    this.classList.remove('border-transparent', 'text-gray-500');
                    this.classList.add('border-primary', 'text-primary');
                    
                    // Show/hide tab contents
                    tabContents.forEach(content => {
                        if (content.dataset.category === targetTab || content.dataset.category === targetTab + '-info') {
                            content.classList.remove('hidden');
                        } else {
                            content.classList.add('hidden');
                        }
                    });
                });
            });
            
            // Handle boolean checkboxes to ensure they're included in form submission
            const booleanCheckboxes = document.querySelectorAll('input[type="checkbox"]');
            booleanCheckboxes.forEach(checkbox => {
                // Add hidden input for unchecked state
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = checkbox.name;
                hiddenInput.value = '0';
                checkbox.parentNode.insertBefore(hiddenInput, checkbox);
                
                checkbox.addEventListener('change', function() {
                    hiddenInput.disabled = this.checked;
                });
                
                // Initial state
                hiddenInput.disabled = checkbox.checked;
            });
        });

        // Auto-save form on change (optional)
        let saveTimeout;
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Show saving indicator
                    const saveButton = document.querySelector('button[type="submit"]');
                    const originalText = saveButton.innerHTML;
                    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                    saveButton.disabled = true;
                    
                    // Submit form via AJAX (if you want auto-save)
                    // For now, just restore button after delay
                    setTimeout(() => {
                        saveButton.innerHTML = originalText;
                        saveButton.disabled = false;
                    }, 1000);
                }, 2000);
            });
        });
    </script>
</body>
</html>
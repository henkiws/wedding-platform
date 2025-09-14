<?php
// admin/themes.php - Themes Management
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

// Handle theme actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $css_file = sanitize($_POST['css_file']);
        $is_premium = (int)$_POST['is_premium'];
        $is_active = (int)$_POST['is_active'];
        
        // Handle preview image upload
        $preview_image = '';
        if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] == 0) {
            try {
                $preview_image = uploadFile($_FILES['preview_image'], 'uploads/themes/', ALLOWED_IMAGE_TYPES);
            } catch (Exception $e) {
                $error = 'Failed to upload preview image: ' . $e->getMessage();
            }
        }
        
        if (!$error) {
            $result = $db->query("
                INSERT INTO themes (name, description, preview_image, css_file, is_premium, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [$name, $description, $preview_image, $css_file, $is_premium, $is_active]);
            
            if ($result) {
                $message = 'Theme created successfully';
            } else {
                $error = 'Failed to create theme';
            }
        }
    } elseif ($action == 'update') {
        $theme_id = (int)$_POST['theme_id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $css_file = sanitize($_POST['css_file']);
        $is_premium = (int)$_POST['is_premium'];
        $is_active = (int)$_POST['is_active'];
        
        // Get current theme data
        $current_theme = $db->fetch("SELECT * FROM themes WHERE id = ?", [$theme_id]);
        $preview_image = $current_theme['preview_image'];
        
        // Handle preview image upload
        if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] == 0) {
            try {
                $new_preview_image = uploadFile($_FILES['preview_image'], 'uploads/themes/', ALLOWED_IMAGE_TYPES);
                // Delete old preview image
                if ($preview_image && file_exists('uploads/themes/' . $preview_image)) {
                    unlink('uploads/themes/' . $preview_image);
                }
                $preview_image = $new_preview_image;
            } catch (Exception $e) {
                $error = 'Failed to upload preview image: ' . $e->getMessage();
            }
        }
        
        if (!$error) {
            $result = $db->query("
                UPDATE themes 
                SET name = ?, description = ?, preview_image = ?, css_file = ?, is_premium = ?, is_active = ? 
                WHERE id = ?
            ", [$name, $description, $preview_image, $css_file, $is_premium, $is_active, $theme_id]);
            
            if ($result) {
                $message = 'Theme updated successfully';
            } else {
                $error = 'Failed to update theme';
            }
        }
    } elseif ($action == 'delete') {
        $theme_id = (int)$_POST['theme_id'];
        
        // Check if theme is being used
        $usage_count = $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE theme_id = ?", [$theme_id])['count'];
        
        if ($usage_count > 0) {
            $error = "Cannot delete theme. It is currently being used by $usage_count invitation(s).";
        } else {
            // Get theme data to delete files
            $theme = $db->fetch("SELECT * FROM themes WHERE id = ?", [$theme_id]);
            
            // Delete theme
            $result = $db->query("DELETE FROM themes WHERE id = ?", [$theme_id]);
            
            if ($result) {
                // Delete associated files
                if ($theme['preview_image'] && file_exists('uploads/themes/' . $theme['preview_image'])) {
                    unlink('uploads/themes/' . $theme['preview_image']);
                }
                if ($theme['css_file'] && file_exists(THEME_DIR . $theme['css_file'])) {
                    unlink(THEME_DIR . $theme['css_file']);
                }
                
                $message = 'Theme deleted successfully';
            } else {
                $error = 'Failed to delete theme';
            }
        }
    } elseif ($action == 'toggle_status') {
        $theme_id = (int)$_POST['theme_id'];
        $is_active = (int)$_POST['is_active'];
        
        $result = $db->query("UPDATE themes SET is_active = ? WHERE id = ?", [$is_active, $theme_id]);
        
        if ($result) {
            $message = $is_active ? 'Theme activated successfully' : 'Theme deactivated successfully';
        } else {
            $error = 'Failed to update theme status';
        }
    }
}

// Get themes with usage statistics
$themes = $db->fetchAll("
    SELECT t.*, 
           COUNT(i.id) as usage_count,
           COUNT(CASE WHEN i.status = 'active' THEN 1 END) as active_usage
    FROM themes t
    LEFT JOIN invitations i ON t.id = i.theme_id
    GROUP BY t.id
    ORDER BY t.created_at DESC
");

// Get theme to edit if requested
$edit_theme = null;
if (isset($_GET['edit'])) {
    $edit_theme = $db->fetch("SELECT * FROM themes WHERE id = ?", [(int)$_GET['edit']]);
}

// Get statistics
$stats = [
    'total' => count($themes),
    'active' => count(array_filter($themes, function($t) { return $t['is_active']; })),
    'premium' => count(array_filter($themes, function($t) { return $t['is_premium']; })),
    'most_used' => $db->fetch("
        SELECT t.name, COUNT(i.id) as count 
        FROM themes t 
        LEFT JOIN invitations i ON t.id = i.theme_id 
        GROUP BY t.id 
        ORDER BY count DESC 
        LIMIT 1
    ")
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Themes Management - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="themes.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
                        <i class="fas fa-palette mr-3"></i>
                        Themes
                    </a>
                    <a href="reports.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-chart-bar mr-3"></i>
                        Reports
                    </a>
                    <a href="settings.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition">
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
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Themes Management</h2>
                        <p class="text-gray-600">Create and manage invitation themes</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                        <i class="fas fa-plus mr-2"></i>
                        Add Theme
                    </button>
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

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Themes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                            </div>
                            <div class="bg-primary/10 p-3 rounded-full">
                                <i class="fas fa-palette text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Themes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['active']; ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Premium Themes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['premium']; ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-crown text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Most Used</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($stats['most_used']['name'] ?? 'N/A'); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $stats['most_used']['count'] ?? 0; ?> invitations</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-star text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Themes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($themes as $theme): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                        <!-- Theme Preview -->
                        <div class="relative h-48 bg-gray-100">
                            <?php if ($theme['preview_image']): ?>
                            <img src="../uploads/themes/<?php echo htmlspecialchars($theme['preview_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($theme['name']); ?>" 
                                 class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-4xl"></i>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge -->
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $theme['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $theme['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            
                            <!-- Premium Badge -->
                            <?php if ($theme['is_premium']): ?>
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-crown mr-1"></i>
                                    Premium
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Theme Info -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($theme['name']); ?></h3>
                                <div class="flex items-center space-x-2">
                                    <button onclick="toggleThemeStatus(<?php echo $theme['id']; ?>, <?php echo $theme['is_active'] ? 0 : 1; ?>)" 
                                            class="text-sm <?php echo $theme['is_active'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'; ?>" 
                                            title="<?php echo $theme['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                    <button onclick="editTheme(<?php echo htmlspecialchars(json_encode($theme)); ?>)" 
                                            class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteTheme(<?php echo $theme['id']; ?>, '<?php echo htmlspecialchars($theme['name']); ?>', <?php echo $theme['usage_count']; ?>)" 
                                            class="text-red-600 hover:text-red-800" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($theme['description'] ?: 'No description available'); ?></p>
                            
                            <!-- Theme Stats -->
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div>
                                    <i class="fas fa-envelope mr-1"></i>
                                    <?php echo $theme['usage_count']; ?> invitation<?php echo $theme['usage_count'] != 1 ? 's' : ''; ?>
                                </div>
                                <div>
                                    <i class="fas fa-check mr-1"></i>
                                    <?php echo $theme['active_usage']; ?> active
                                </div>
                            </div>
                            
                            <!-- CSS File Info -->
                            <?php if ($theme['css_file']): ?>
                            <div class="mt-3 text-xs text-gray-400">
                                <i class="fas fa-file-code mr-1"></i>
                                <?php echo htmlspecialchars($theme['css_file']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <?php if (empty($themes)): ?>
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-palette text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No themes found</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first theme.</p>
                    <button onclick="openCreateModal()" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                        <i class="fas fa-plus mr-2"></i>
                        Create First Theme
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create/Edit Theme Modal -->
    <div id="themeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add New Theme</h3>
                <form id="themeForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="theme_id" id="themeId" value="">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Theme Name</label>
                        <input type="text" name="name" id="themeName" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="themeDescription" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Preview Image</label>
                        <input type="file" name="preview_image" id="previewImage" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Recommended size: 400x300px</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">CSS File</label>
                        <input type="text" name="css_file" id="cssFile" 
                               placeholder="theme-name.css"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">CSS file name in themes directory</p>
                    </div>
                    
                    <div class="mb-4 flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_premium" id="isPremium" value="1" 
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Premium Theme</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" id="isActive" value="1" checked 
                                   class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <span id="submitText">Create Theme</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Theme</h3>
                <p class="text-sm text-gray-500 mb-4" id="deleteMessage">
                    Are you sure you want to delete this theme?
                </p>
                <form method="POST" class="flex justify-center space-x-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="theme_id" id="deleteThemeId" value="">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" id="deleteButton"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Delete Theme
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New Theme';
            document.getElementById('formAction').value = 'create';
            document.getElementById('themeId').value = '';
            document.getElementById('themeForm').reset();
            document.getElementById('isActive').checked = true;
            document.getElementById('submitText').textContent = 'Create Theme';
            document.getElementById('themeModal').classList.remove('hidden');
        }

        function editTheme(theme) {
            document.getElementById('modalTitle').textContent = 'Edit Theme';
            document.getElementById('formAction').value = 'update';
            document.getElementById('themeId').value = theme.id;
            document.getElementById('themeName').value = theme.name;
            document.getElementById('themeDescription').value = theme.description || '';
            document.getElementById('cssFile').value = theme.css_file || '';
            document.getElementById('isPremium').checked = theme.is_premium == 1;
            document.getElementById('isActive').checked = theme.is_active == 1;
            document.getElementById('submitText').textContent = 'Update Theme';
            document.getElementById('themeModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('themeModal').classList.add('hidden');
        }

        function toggleThemeStatus(themeId, newStatus) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="theme_id" value="${themeId}">
                <input type="hidden" name="is_active" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function deleteTheme(themeId, themeName, usageCount) {
            document.getElementById('deleteThemeId').value = themeId;
            
            if (usageCount > 0) {
                document.getElementById('deleteMessage').innerHTML = 
                    `Cannot delete "<strong>${themeName}</strong>". It is currently being used by <strong>${usageCount}</strong> invitation${usageCount > 1 ? 's' : ''}.`;
                document.getElementById('deleteButton').style.display = 'none';
            } else {
                document.getElementById('deleteMessage').innerHTML = 
                    `Are you sure you want to delete "<strong>${themeName}</strong>"? This action cannot be undone.`;
                document.getElementById('deleteButton').style.display = 'block';
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const themeModal = document.getElementById('themeModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === themeModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // Auto-generate CSS filename from theme name
        document.getElementById('themeName').addEventListener('input', function() {
            const name = this.value.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').trim('-');
            if (name && !document.getElementById('cssFile').value) {
                document.getElementById('cssFile').value = name + '.css';
            }
        });
    </script>
</body>
</html>
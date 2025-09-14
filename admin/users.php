<?php
// admin/users.php - Users Management
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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $subscription_plan = sanitize($_POST['subscription_plan']);
        
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } else {
            // Check if username or email already exists
            $existing = $db->fetch("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($existing) {
                $error = 'Username or email already exists';
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $subscription_end = $subscription_plan == 'free' ? date('Y-m-d H:i:s', strtotime('+30 days')) : null;
                
                $result = $db->query("
                    INSERT INTO users (username, email, password, subscription_plan, subscription_end, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ", [$username, $email, $hashed_password, $subscription_plan, $subscription_end]);
                
                if ($result) {
                    $message = 'User created successfully';
                } else {
                    $error = 'Failed to create user';
                }
            }
        }
    } elseif ($action == 'update') {
        $user_id = (int)$_POST['user_id'];
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $subscription_plan = sanitize($_POST['subscription_plan']);
        $status = sanitize($_POST['status']);
        
        // Update user
        $subscription_end = $subscription_plan == 'free' ? date('Y-m-d H:i:s', strtotime('+30 days')) : null;
        
        $result = $db->query("
            UPDATE users 
            SET username = ?, email = ?, subscription_plan = ?, subscription_end = ?, status = ? 
            WHERE id = ?
        ", [$username, $email, $subscription_plan, $subscription_end, $status, $user_id]);
        
        if ($result) {
            $message = 'User updated successfully';
        } else {
            $error = 'Failed to update user';
        }
    } elseif ($action == 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Delete user and related data
        $db->query("DELETE FROM rsvps WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user_id]);
        $db->query("DELETE FROM guest_messages WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user_id]);
        $db->query("DELETE FROM guests WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user_id]);
        $db->query("DELETE FROM invitations WHERE user_id = ?", [$user_id]);
        $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
        
        $message = 'User deleted successfully';
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($plan_filter) {
    $where_conditions[] = "subscription_plan = ?";
    $params[] = $plan_filter;
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get users
$users = $db->fetchAll("
    SELECT u.*, 
           COUNT(i.id) as invitation_count,
           SUM(CASE WHEN i.status = 'active' THEN 1 ELSE 0 END) as active_invitations
    FROM users u
    LEFT JOIN invitations i ON u.id = i.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Get total count for pagination
$total_users = $db->fetch("SELECT COUNT(*) as count FROM users $where_clause", $params)['count'];
$total_pages = ceil($total_users / $per_page);

// Get user to edit if requested
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_user = $db->fetch("SELECT * FROM users WHERE id = ?", [(int)$_GET['edit']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="users.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
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
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Users Management</h2>
                        <p class="text-gray-600">Manage user accounts and subscriptions</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-primary hover:bg-primary/90 text-white px-6 py-3 rounded-lg font-medium transition">
                        <i class="fas fa-plus mr-2"></i>
                        Add User
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

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Username or email..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subscription Plan</label>
                            <select name="plan" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Plans</option>
                                <option value="free" <?php echo $plan_filter == 'free' ? 'selected' : ''; ?>>Free</option>
                                <option value="premium" <?php echo $plan_filter == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="business" <?php echo $plan_filter == 'business' ? 'selected' : ''; ?>>Business</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>
                            <a href="users.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-times mr-1"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invitations</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['subscription_plan'] == 'free' ? 'bg-gray-100 text-gray-800' : ($user['subscription_plan'] == 'premium' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo ucfirst($user['subscription_plan']); ?>
                                        </span>
                                        <?php if ($user['subscription_end']): ?>
                                        <div class="text-xs text-gray-500 mt-1">Expires: <?php echo date('M j, Y', strtotime($user['subscription_end'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div><?php echo $user['invitation_count']; ?> total</div>
                                        <div class="text-xs text-green-600"><?php echo $user['active_invitations']; ?> active</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-primary hover:text-primary/80">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($plan_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($plan_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                    <span class="font-medium"><?php echo min($offset + $per_page, $total_users); ?></span> of 
                                    <span class="font-medium"><?php echo $total_users; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&plan=<?php echo urlencode($plan_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       class="<?php echo $i == $page ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Add New User</h3>
                <form id="userForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="user_id" id="userId" value="">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="username" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="mb-4" id="passwordField">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan</label>
                        <select name="subscription_plan" id="subscriptionPlan" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="free">Free</option>
                            <option value="premium">Premium</option>
                            <option value="business">Business</option>
                        </select>
                    </div>
                    
                    <div class="mb-6" id="statusField" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <span id="submitText">Create User</span>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete User</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete "<span id="deleteUsername"></span>"? This action cannot be undone and will also delete all their invitations and related data.
                </p>
                <form method="POST" class="flex justify-center space-x-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="deleteUserId" value="">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'create';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('statusField').style.display = 'none';
            document.getElementById('submitText').textContent = 'Create User';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('subscriptionPlan').value = user.subscription_plan;
            document.getElementById('status').value = user.status;
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('password').required = false;
            document.getElementById('statusField').style.display = 'block';
            document.getElementById('submitText').textContent = 'Update User';
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function deleteUser(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === userModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
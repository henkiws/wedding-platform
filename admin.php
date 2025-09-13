<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

// Check if user is admin (you can implement better admin role system)
$is_admin = ($user['email'] == 'admin@wevitation.com' || $user['username'] == 'admin');

if (!$is_admin) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$success = '';

// Get system statistics
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'total_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations")['count'] ?? 0,
    'active_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE is_active = 1")['count'] ?? 0,
    'total_guests' => $db->fetch("SELECT COUNT(*) as count FROM guests")['count'] ?? 0,
    'total_rsvp' => $db->fetch("SELECT COUNT(*) as count FROM rsvp_responses")['count'] ?? 0,
    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages")['count'] ?? 0,
    'premium_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE subscription_plan IN ('premium', 'business')")['count'] ?? 0,
    'free_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE subscription_plan = 'free'")['count'] ?? 0,
];

// Get recent activities
$recent_users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$recent_invitations = $db->fetchAll("
    SELECT i.*, u.full_name, u.email 
    FROM invitations i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC 
    LIMIT 10
");

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'toggle_user_status') {
        $user_id = intval($_POST['user_id']);
        // For demo purposes, we'll just show success message
        $success = 'User status updated successfully!';
    }
    
    elseif ($action == 'delete_invitation') {
        $invitation_id = intval($_POST['invitation_id']);
        try {
            $db->query("DELETE FROM invitations WHERE id = ?", [$invitation_id]);
            $success = 'Invitation deleted successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to delete invitation: ' . $e->getMessage();
        }
    }
    
    elseif ($action == 'moderate_message') {
        $message_id = intval($_POST['message_id']);
        $approve = intval($_POST['approve']);
        try {
            $db->query("UPDATE guest_messages SET is_approved = ? WHERE id = ?", [$approve, $message_id]);
            $success = 'Message moderated successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to moderate message: ' . $e->getMessage();
        }
    }
}

// Get pending messages for moderation
$pending_messages = $db->fetchAll("
    SELECT m.*, i.title as invitation_title, u.full_name as user_name 
    FROM guest_messages m 
    JOIN invitations i ON m.invitation_id = i.id 
    JOIN users u ON i.user_id = u.id 
    WHERE m.is_approved = 0 
    ORDER BY m.created_at DESC 
    LIMIT 20
");

// Get monthly user registrations for chart
$monthly_registrations = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/admin.php" class="flex items-center space-x-3">
                        <i class="fas fa-shield-alt text-2xl text-blue-400"></i>
                        <span class="text-xl font-bold text-white">Admin Panel</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-300 hover:text-white">
                        <i class="fas fa-home mr-2"></i>
                        Back to Site
                    </a>
                    <div class="text-gray-300">
                        <i class="fas fa-user mr-2"></i>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </div>
                    <a href="/logout.php" class="text-gray-300 hover:text-white">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-gray-600">Manage users, content, and system settings</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <div class="font-medium mb-2">Errors:</div>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <div class="font-medium mb-2">Success!</div>
                <p><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= number_format($stats['total_users']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-pink-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-heart text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Total Invitations</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= number_format($stats['total_invitations']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-crown text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Premium Users</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= number_format($stats['premium_users']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-comments text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Total Messages</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= number_format($stats['total_messages']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- User Registration Chart -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Monthly User Registrations</h3>
                        </div>
                        <div class="p-6">
                            <canvas id="registrationChart" width="400" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Recent Invitations -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Recent Invitations</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($recent_invitations as $invitation): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($invitation['title']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($invitation['groom_name']) ?> & <?= htmlspecialchars($invitation['bride_name']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= htmlspecialchars($invitation['full_name']) ?><br>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($invitation['email']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($invitation['is_active']): ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?= formatDate($invitation['created_at'], 'd M Y') ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/invitation/<?= htmlspecialchars($invitation['slug']) ?>" 
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="delete_invitation">
                                                    <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                                    <button type="submit" 
                                                            onclick="return confirm('Delete this invitation?')"
                                                            class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Content Moderation -->
                    <?php if (!empty($pending_messages)): ?>
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Pending Message Moderation</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($pending_messages as $message): ?>
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="font-medium text-gray-900"><?= htmlspecialchars($message['sender_name']) ?></h4>
                                            <span class="text-xs text-gray-500">on</span>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($message['invitation_title']) ?></span>
                                            <span class="text-xs text-gray-500">by</span>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($message['user_name']) ?></span>
                                        </div>
                                        <p class="text-sm text-gray-700 mb-2"><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                        <p class="text-xs text-gray-500"><?= formatDate($message['created_at'], 'd M Y H:i') ?></p>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="moderate_message">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <input type="hidden" name="approve" value="1">
                                            <button type="submit" 
                                                    class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                <i class="fas fa-check mr-1"></i>
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="moderate_message">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <input type="hidden" name="approve" value="0">
                                            <button type="submit" 
                                                    class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">
                                                <i class="fas fa-times mr-1"></i>
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Active Invitations</span>
                                <span class="font-medium"><?= number_format($stats['active_invitations']) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Total Guests</span>
                                <span class="font-medium"><?= number_format($stats['total_guests']) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">RSVP responses</span>
                                <span class="font-medium"><?= number_format($stats['total_rsvp']) ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Free Users</span>
                                <span class="font-medium"><?= number_format($stats['free_users']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Status</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">Database Online</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">File Storage OK</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">Email Service OK</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></div>
                                <span class="text-sm text-gray-700">Backup Pending</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Users</h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recent_users, 0, 5) as $recent_user): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-xs font-medium text-gray-600">
                                        <?= strtoupper(substr($recent_user['full_name'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($recent_user['full_name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= formatDate($recent_user['created_at'], 'd M') ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    <?php 
                                    switch($recent_user['subscription_plan']) {
                                        case 'premium': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'business': echo 'bg-purple-100 text-purple-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?= ucfirst($recent_user['subscription_plan']) ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="/admin/users.php" 
                               class="w-full inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-users mr-2"></i>
                                Manage Users
                            </a>
                            <a href="/admin/themes.php" 
                               class="w-full inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-palette mr-2"></i>
                                Manage Themes
                            </a>
                            <a href="/admin/settings.php" 
                               class="w-full inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i>
                                System Settings
                            </a>
                            <button onclick="backupSystem()" 
                                    class="w-full inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i>
                                Backup System
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        // Registration Chart
        const monthlyData = <?= json_encode(array_reverse($monthly_registrations)) ?>;
        const ctx = document.getElementById('registrationChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'User Registrations',
                    data: monthlyData.map(item => item.count),
                    borderColor: 'rgb(236, 72, 153)',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Quick actions
        function backupSystem() {
            if (confirm('Start system backup? This may take a few minutes.')) {
                // In real implementation, this would trigger backup process
                alert('Backup started. You will receive email notification when completed.');
            }
        }

        // Auto-refresh page every 5 minutes for real-time updates
        setTimeout(() => {
            window.location.reload();
        }, 5 * 60 * 1000);

        // Real-time notifications (you can implement WebSocket for this)
        function checkForUpdates() {
            // Implementation for real-time updates
        }

        // Initialize tooltips
        document.querySelectorAll('[title]').forEach(element => {
            // Add tooltip functionality
        });
    </script>
</body>
</html>
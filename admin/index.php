<?php
// admin/index.php - Admin Dashboard
require_once '../config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Get dashboard statistics
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'total_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations")['count'],
    'active_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE status = 'active'")['count'],
    'total_guests' => $db->fetch("SELECT COUNT(*) as count FROM guests")['count'],
    'total_rsvp' => $db->fetch("SELECT COUNT(*) as count FROM rsvps")['count'],
    'premium_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE subscription_plan IN ('premium', 'business')")['count'],
    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages")['count']
];

// Get recent activities
$recent_users = $db->fetchAll("SELECT id, username, email, created_at, subscription_plan FROM users ORDER BY created_at DESC LIMIT 5");
$recent_invitations = $db->fetchAll("
    SELECT i.id, i.title, i.slug, i.status, i.created_at, u.username 
    FROM invitations i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC LIMIT 5
");

// Get monthly growth data for charts
$monthly_data = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

$invitation_growth = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM invitations 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Get subscription distribution
$subscription_stats = $db->fetchAll("
    SELECT subscription_plan, COUNT(*) as count 
    FROM users 
    GROUP BY subscription_plan
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-heart text-primary mr-2"></i>
                        <?php echo SITE_NAME; ?> Admin
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, Admin</span>
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
                    <a href="index.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
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
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Overview</h2>
                    <p class="text-gray-600">Monitor your wedding invitation platform performance</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Users -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Invitations -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Invitations</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_invitations']); ?></p>
                            </div>
                            <div class="bg-primary/10 p-3 rounded-full">
                                <i class="fas fa-envelope text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Invitations -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Invitations</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_invitations']); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Premium Users -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Premium Users</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['premium_users']); ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-crown text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- User Growth Chart -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">User Growth (Last 12 Months)</h3>
                        <canvas id="userGrowthChart" width="400" height="200"></canvas>
                    </div>

                    <!-- Subscription Distribution -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Distribution</h3>
                        <canvas id="subscriptionChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Users -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Users</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($recent_users as $user): ?>
                            <div class="p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['subscription_plan'] == 'free' ? 'bg-gray-100 text-gray-800' : ($user['subscription_plan'] == 'premium' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'); ?>">
                                            <?php echo ucfirst($user['subscription_plan']); ?>
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="users.php" class="text-primary hover:text-primary/80 text-sm font-medium">View all users →</a>
                        </div>
                    </div>

                    <!-- Recent Invitations -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Invitations</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($recent_invitations as $invitation): ?>
                            <div class="p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($invitation['title']); ?></p>
                                        <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($invitation['username']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $invitation['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($invitation['status']); ?>
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($invitation['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="invitations.php" class="text-primary hover:text-primary/80 text-sm font-medium">View all invitations →</a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-8 bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <a href="users.php?action=create" class="flex items-center justify-center px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition border border-blue-200">
                            <i class="fas fa-user-plus mr-2 text-blue-600"></i>
                            <span class="text-blue-700 font-medium">Add User</span>
                        </a>
                        <a href="themes.php?action=create" class="flex items-center justify-center px-4 py-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition border border-purple-200">
                            <i class="fas fa-palette mr-2 text-purple-600"></i>
                            <span class="text-purple-700 font-medium">Add Theme</span>
                        </a>
                        <a href="reports.php" class="flex items-center justify-center px-4 py-3 bg-green-50 hover:bg-green-100 rounded-lg transition border border-green-200">
                            <i class="fas fa-download mr-2 text-green-600"></i>
                            <span class="text-green-700 font-medium">Export Data</span>
                        </a>
                        <a href="settings.php" class="flex items-center justify-center px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition border border-gray-200">
                            <i class="fas fa-cog mr-2 text-gray-600"></i>
                            <span class="text-gray-700 font-medium">System Settings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Growth Chart
        const userCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($monthly_data, 'count')); ?>,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Subscription Distribution Chart
        const subCtx = document.getElementById('subscriptionChart').getContext('2d');
        new Chart(subCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_column($subscription_stats, 'subscription_plan'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($subscription_stats, 'count')); ?>,
                    backgroundColor: ['#e5e7eb', '#fbbf24', '#8b5cf6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
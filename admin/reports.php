<?php
// admin/reports.php - Reports and Analytics
require_once '../config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $export_type . '_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    switch ($export_type) {
        case 'users':
            fputcsv($output, ['ID', 'Username', 'Email', 'Subscription Plan', 'Status', 'Created At', 'Invitations Count', 'Last Login']);
            $users = $db->fetchAll("
                SELECT u.*, 
                       COUNT(i.id) as invitation_count,
                       MAX(u.updated_at) as last_activity
                FROM users u
                LEFT JOIN invitations i ON u.id = i.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ");
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['subscription_plan'],
                    $user['status'],
                    $user['created_at'],
                    $user['invitation_count'],
                    $user['last_activity']
                ]);
            }
            break;
            
        case 'invitations':
            fputcsv($output, ['ID', 'Title', 'Owner', 'Groom Name', 'Bride Name', 'Status', 'Theme', 'Guest Count', 'RSVP Count', 'Created At']);
            $invitations = $db->fetchAll("
                SELECT i.*, u.username, t.name as theme_name,
                       COUNT(DISTINCT g.id) as guest_count,
                       COUNT(DISTINCT r.id) as rsvp_count
                FROM invitations i
                JOIN users u ON i.user_id = u.id
                LEFT JOIN themes t ON i.theme_id = t.id
                LEFT JOIN guests g ON i.id = g.invitation_id
                LEFT JOIN rsvps r ON i.id = r.invitation_id
                GROUP BY i.id
                ORDER BY i.created_at DESC
            ");
            foreach ($invitations as $invitation) {
                fputcsv($output, [
                    $invitation['id'],
                    $invitation['title'],
                    $invitation['username'],
                    $invitation['groom_name'],
                    $invitation['bride_name'],
                    $invitation['status'],
                    $invitation['theme_name'],
                    $invitation['guest_count'],
                    $invitation['rsvp_count'],
                    $invitation['created_at']
                ]);
            }
            break;
            
        case 'rsvps':
            fputcsv($output, ['ID', 'Invitation', 'Guest Name', 'Email', 'Phone', 'Attendance', 'Message', 'Submitted At']);
            $rsvps = $db->fetchAll("
                SELECT r.*, i.title as invitation_title
                FROM rsvps r
                JOIN invitations i ON r.invitation_id = i.id
                ORDER BY r.created_at DESC
            ");
            foreach ($rsvps as $rsvp) {
                fputcsv($output, [
                    $rsvp['id'],
                    $rsvp['invitation_title'],
                    $rsvp['guest_name'],
                    $rsvp['guest_email'],
                    $rsvp['guest_phone'],
                    $rsvp['attendance'],
                    $rsvp['message'],
                    $rsvp['created_at']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

// Get date range for filters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Overall statistics
$stats = [
    'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'new_users_today' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count'],
    'new_users_this_month' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")['count'],
    'total_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations")['count'],
    'active_invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE status = 'active'")['count'],
    'total_rsvps' => $db->fetch("SELECT COUNT(*) as count FROM rsvps")['count'],
    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages")['count'],
    'premium_users' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE subscription_plan IN ('premium', 'business')")['count'],
    'revenue_potential' => $db->fetch("SELECT COUNT(*) * 99000 as potential FROM users WHERE subscription_plan IN ('premium', 'business')")['potential']
];

// User growth data (last 12 months)
$user_growth = $db->fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_users,
        SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as total_users
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Invitation statistics by theme
$theme_stats = $db->fetchAll("
    SELECT 
        COALESCE(t.name, 'Default') as theme_name,
        COUNT(i.id) as invitation_count,
        COUNT(CASE WHEN i.status = 'active' THEN 1 END) as active_count
    FROM invitations i
    LEFT JOIN themes t ON i.theme_id = t.id
    GROUP BY t.id, t.name
    ORDER BY invitation_count DESC
    LIMIT 10
");

// RSVP response rates
$rsvp_stats = $db->fetchAll("
    SELECT 
        attendance,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM rsvps), 2) as percentage
    FROM rsvps 
    GROUP BY attendance
    ORDER BY count DESC
");

// Subscription plan distribution
$subscription_stats = $db->fetchAll("
    SELECT 
        subscription_plan,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users), 2) as percentage
    FROM users 
    GROUP BY subscription_plan
    ORDER BY count DESC
");

// Daily activity (last 30 days)
$daily_activity = $db->fetchAll("
    SELECT 
        DATE(created_at) as date,
        COUNT(DISTINCT CASE WHEN table_name = 'users' THEN id END) as new_users,
        COUNT(DISTINCT CASE WHEN table_name = 'invitations' THEN id END) as new_invitations,
        COUNT(DISTINCT CASE WHEN table_name = 'rsvps' THEN id END) as new_rsvps
    FROM (
        SELECT id, created_at, 'users' as table_name FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION ALL
        SELECT id, created_at, 'invitations' as table_name FROM invitations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION ALL
        SELECT id, created_at, 'rsvps' as table_name FROM rsvps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ) as combined
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30
");

// Top performing invitations
$top_invitations = $db->fetchAll("
    SELECT 
        i.title,
        i.groom_name,
        i.bride_name,
        u.username,
        COUNT(DISTINCT g.id) as guest_count,
        COUNT(DISTINCT r.id) as rsvp_count,
        COUNT(DISTINCT gm.id) as message_count,
        ROUND(COUNT(DISTINCT r.id) * 100.0 / NULLIF(COUNT(DISTINCT g.id), 0), 2) as rsvp_rate
    FROM invitations i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN guests g ON i.id = g.invitation_id
    LEFT JOIN rsvps r ON i.id = r.invitation_id
    LEFT JOIN guest_messages gm ON i.id = gm.invitation_id
    WHERE i.status = 'active'
    GROUP BY i.id
    HAVING guest_count > 0
    ORDER BY rsvp_rate DESC, guest_count DESC
    LIMIT 10
");

// Recent activity logs
$recent_activity = $db->fetchAll("
    SELECT 
        'User Registration' as activity_type,
        CONCAT(username, ' registered') as description,
        created_at as activity_time
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'New Invitation' as activity_type,
        CONCAT('\"', title, '\" created by ', (SELECT username FROM users WHERE id = user_id)) as description,
        created_at as activity_time
    FROM invitations 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'RSVP Response' as activity_type,
        CONCAT(guest_name, ' responded to \"', (SELECT title FROM invitations WHERE id = invitation_id), '\"') as description,
        created_at as activity_time
    FROM rsvps 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY activity_time DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="reports.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
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
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Reports & Analytics</h2>
                        <p class="text-gray-600">Comprehensive platform insights and data export</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="?export=users" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-download mr-1"></i> Export Users
                        </a>
                        <a href="?export=invitations" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-download mr-1"></i> Export Invitations
                        </a>
                        <a href="?export=rsvps" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-download mr-1"></i> Export RSVPs
                        </a>
                    </div>
                </div>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_users']); ?></p>
                                <p class="text-xs text-green-600 mt-1">+<?php echo $stats['new_users_today']; ?> today</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Invitations</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_invitations']); ?></p>
                                <p class="text-xs text-gray-500 mt-1">of <?php echo number_format($stats['total_invitations']); ?> total</p>
                            </div>
                            <div class="bg-primary/10 p-3 rounded-full">
                                <i class="fas fa-envelope text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Premium Users</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['premium_users']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo round($stats['premium_users'] / $stats['total_users'] * 100, 1); ?>% of total</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-crown text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total RSVPs</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_rsvps']); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo number_format($stats['total_messages']); ?> messages</p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
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

                    <!-- RSVP Response Distribution -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">RSVP Response Distribution</h3>
                        <canvas id="rsvpChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Data Tables Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Subscription Plan Stats -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Subscription Plans</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($subscription_stats as $plan): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 rounded-full mr-3 <?php echo $plan['subscription_plan'] == 'free' ? 'bg-gray-400' : ($plan['subscription_plan'] == 'premium' ? 'bg-yellow-400' : 'bg-purple-400'); ?>"></div>
                                        <span class="font-medium text-gray-900"><?php echo ucfirst($plan['subscription_plan']); ?></span>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900"><?php echo number_format($plan['count']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $plan['percentage']; ?>%</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Theme Usage Stats -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Popular Themes</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($theme_stats as $theme): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($theme['theme_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $theme['active_count']; ?> active</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900"><?php echo number_format($theme['invitation_count']); ?></div>
                                        <div class="text-sm text-gray-500">invitations</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Invitations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Top Performing Invitations</h3>
                        <p class="text-sm text-gray-500">Ranked by RSVP response rate and guest count</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invitation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guests</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RSVPs</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Messages</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($top_invitations as $invitation): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invitation['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($invitation['groom_name']); ?> & <?php echo htmlspecialchars($invitation['bride_name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($invitation['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($invitation['guest_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($invitation['rsvp_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($invitation['message_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                                <div class="bg-primary h-2 rounded-full" style="width: <?php echo min(100, $invitation['rsvp_rate'] ?? 0); ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900"><?php echo number_format($invitation['rsvp_rate'] ?? 0, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity (Last 7 Days)</h3>
                    </div>
                    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <?php 
                                    $icon_class = '';
                                    $icon_color = '';
                                    switch($activity['activity_type']) {
                                        case 'User Registration':
                                            $icon_class = 'fa-user-plus';
                                            $icon_color = 'text-blue-500';
                                            break;
                                        case 'New Invitation':
                                            $icon_class = 'fa-envelope';
                                            $icon_color = 'text-primary';
                                            break;
                                        case 'RSVP Response':
                                            $icon_class = 'fa-check-circle';
                                            $icon_color = 'text-green-500';
                                            break;
                                        default:
                                            $icon_class = 'fa-bell';
                                            $icon_color = 'text-gray-500';
                                    }
                                    ?>
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas <?php echo $icon_class; ?> <?php echo $icon_color; ?> text-sm"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900"><?php echo $activity['activity_type']; ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('M j, g:i A', strtotime($activity['activity_time'])); ?></p>
                                    </div>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($activity['description']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                labels: <?php echo json_encode(array_column($user_growth, 'month')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($user_growth, 'new_users')); ?>,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Total Users',
                    data: <?php echo json_encode(array_column($user_growth, 'total_users')); ?>,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: false
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
                        position: 'top'
                    }
                }
            }
        });

        // RSVP Distribution Chart
        const rsvpCtx = document.getElementById('rsvpChart').getContext('2d');
        new Chart(rsvpCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { 
                    return ucfirst($r['attendance']) . ' (' . $r['percentage'] . '%)'; 
                }, $rsvp_stats)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($rsvp_stats, 'count')); ?>,
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
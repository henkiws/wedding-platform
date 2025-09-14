<?php
// admin/invitations.php - Invitations Management
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

// Handle invitation actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_status') {
        $invitation_id = (int)$_POST['invitation_id'];
        $status = sanitize($_POST['status']);
        
        $result = $db->query("UPDATE invitations SET status = ? WHERE id = ?", [$status, $invitation_id]);
        
        if ($result) {
            $message = 'Invitation status updated successfully';
        } else {
            $error = 'Failed to update invitation status';
        }
    } elseif ($action == 'delete') {
        $invitation_id = (int)$_POST['invitation_id'];
        
        // Delete related data first
        $db->query("DELETE FROM rsvps WHERE invitation_id = ?", [$invitation_id]);
        $db->query("DELETE FROM guest_messages WHERE invitation_id = ?", [$invitation_id]);
        $db->query("DELETE FROM guests WHERE invitation_id = ?", [$invitation_id]);
        $db->query("DELETE FROM invitations WHERE id = ?", [$invitation_id]);
        
        $message = 'Invitation deleted successfully';
    } elseif ($action == 'feature') {
        $invitation_id = (int)$_POST['invitation_id'];
        $featured = (int)$_POST['featured'];
        
        $result = $db->query("UPDATE invitations SET is_featured = ? WHERE id = ?", [$featured, $invitation_id]);
        
        if ($result) {
            $message = $featured ? 'Invitation featured successfully' : 'Invitation unfeatured successfully';
        } else {
            $error = 'Failed to update invitation';
        }
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$theme_filter = $_GET['theme'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(i.title LIKE ? OR i.groom_name LIKE ? OR i.bride_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "i.status = ?";
    $params[] = $status_filter;
}

if ($theme_filter) {
    $where_conditions[] = "i.theme_id = ?";
    $params[] = $theme_filter;
}

if ($user_filter) {
    $where_conditions[] = "u.username LIKE ?";
    $params[] = "%$user_filter%";
}

if ($date_from) {
    $where_conditions[] = "DATE(i.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(i.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get invitations
$invitations = $db->fetchAll("
    SELECT i.*, 
           u.username, u.email, u.subscription_plan,
           t.name as theme_name,
           COUNT(DISTINCT g.id) as guest_count,
           COUNT(DISTINCT r.id) as rsvp_count,
           COUNT(DISTINCT gm.id) as message_count
    FROM invitations i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN themes t ON i.theme_id = t.id
    LEFT JOIN guests g ON i.id = g.invitation_id
    LEFT JOIN rsvps r ON i.id = r.invitation_id
    LEFT JOIN guest_messages gm ON i.id = gm.invitation_id
    $where_clause
    GROUP BY i.id
    ORDER BY i.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Get total count for pagination
$total_invitations = $db->fetch("
    SELECT COUNT(DISTINCT i.id) as count 
    FROM invitations i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN themes t ON i.theme_id = t.id
    $where_clause
", $params)['count'];

$total_pages = ceil($total_invitations / $per_page);

// Get themes for filter
$themes = $db->fetchAll("SELECT id, name FROM themes ORDER BY name");

// Get statistics
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM invitations")['count'],
    'active' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE status = 'active'")['count'],
    'featured' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE is_featured = 1")['count'],
    'this_month' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitations Management - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="invitations.php" class="flex items-center px-4 py-3 text-gray-700 bg-primary/10 rounded-lg border-r-4 border-primary">
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Invitations Management</h2>
                    <p class="text-gray-600">Monitor and manage all wedding invitations</p>
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
                                <p class="text-sm font-medium text-gray-600">Total Invitations</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total']); ?></p>
                            </div>
                            <div class="bg-primary/10 p-3 rounded-full">
                                <i class="fas fa-envelope text-primary text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Invitations</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active']); ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Featured</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['featured']); ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-star text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">This Month</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['this_month']); ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-calendar text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Title, names..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Theme</label>
                            <select name="theme" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">All Themes</option>
                                <?php foreach ($themes as $theme): ?>
                                <option value="<?php echo $theme['id']; ?>" <?php echo $theme_filter == $theme['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($theme['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                            <input type="text" name="user" value="<?php echo htmlspecialchars($user_filter); ?>" 
                                   placeholder="Username..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                    </form>
                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="submit" form="filterForm" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-search mr-1"></i> Filter
                        </button>
                        <a href="invitations.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                            <i class="fas fa-times mr-1"></i> Clear
                        </a>
                    </div>
                </div>

                <!-- Invitations Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invitation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Theme</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stats</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($invitations as $invitation): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <?php if ($invitation['cover_image']): ?>
                                            <img src="../uploads/covers/<?php echo htmlspecialchars($invitation['cover_image']); ?>" 
                                                 alt="Cover" class="w-12 h-12 rounded-lg object-cover mr-4">
                                            <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                                                <i class="fas fa-heart text-gray-400"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($invitation['title']); ?>
                                                    <?php if ($invitation['is_featured']): ?>
                                                    <i class="fas fa-star text-yellow-500 ml-1" title="Featured"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($invitation['groom_name']); ?> & 
                                                    <?php echo htmlspecialchars($invitation['bride_name']); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <a href="../invitation/<?php echo htmlspecialchars($invitation['slug']); ?>" 
                                                       target="_blank" class="hover:text-primary">
                                                        View Invitation <i class="fas fa-external-link-alt ml-1"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invitation['username']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($invitation['email']); ?></div>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $invitation['subscription_plan'] == 'free' ? 'bg-gray-100 text-gray-800' : ($invitation['subscription_plan'] == 'premium' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800'); ?>">
                                                <?php echo ucfirst($invitation['subscription_plan']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($invitation['theme_name'] ?? 'Default'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="space-y-1">
                                            <div><i class="fas fa-users text-gray-400 mr-1"></i> <?php echo $invitation['guest_count']; ?> guests</div>
                                            <div><i class="fas fa-check text-green-400 mr-1"></i> <?php echo $invitation['rsvp_count']; ?> RSVPs</div>
                                            <div><i class="fas fa-comment text-blue-400 mr-1"></i> <?php echo $invitation['message_count']; ?> messages</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $invitation['status'] == 'active' ? 'bg-green-100 text-green-800' : ($invitation['status'] == 'inactive' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($invitation['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($invitation['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="changeStatus(<?php echo $invitation['id']; ?>, '<?php echo $invitation['status']; ?>')" 
                                                    class="text-blue-600 hover:text-blue-800" title="Change Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="toggleFeature(<?php echo $invitation['id']; ?>, <?php echo $invitation['is_featured'] ? 1 : 0; ?>)" 
                                                    class="<?php echo $invitation['is_featured'] ? 'text-yellow-500' : 'text-gray-400'; ?> hover:text-yellow-600" title="Toggle Feature">
                                                <i class="fas fa-star"></i>
                                            </button>
                                            <a href="../invitation/<?php echo htmlspecialchars($invitation['slug']); ?>" 
                                               target="_blank" class="text-green-600 hover:text-green-800" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="deleteInvitation(<?php echo $invitation['id']; ?>, '<?php echo htmlspecialchars($invitation['title']); ?>')" 
                                                    class="text-red-600 hover:text-red-800" title="Delete">
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
                            <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                    <span class="font-medium"><?php echo min($offset + $per_page, $total_invitations); ?></span> of 
                                    <span class="font-medium"><?php echo $total_invitations; ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>" 
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

    <!-- Status Change Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Change Invitation Status</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="invitation_id" id="statusInvitationId" value="">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="newStatus" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            Update Status
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Invitation</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete "<span id="deleteInvitationTitle"></span>"? This action cannot be undone and will also delete all related guests, RSVPs, and messages.
                </p>
                <form method="POST" class="flex justify-center space-x-3">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="invitation_id" id="deleteInvitationId" value="">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Delete Invitation
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update the form to use GET method for filters
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="GET"]');
            form.id = 'filterForm';
        });

        function changeStatus(invitationId, currentStatus) {
            document.getElementById('statusInvitationId').value = invitationId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function toggleFeature(invitationId, isFeatured) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="feature">
                <input type="hidden" name="invitation_id" value="${invitationId}">
                <input type="hidden" name="featured" value="${isFeatured ? 0 : 1}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function deleteInvitation(invitationId, title) {
            document.getElementById('deleteInvitationId').value = invitationId;
            document.getElementById('deleteInvitationTitle').textContent = title;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const statusModal = document.getElementById('statusModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === statusModal) {
                closeStatusModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
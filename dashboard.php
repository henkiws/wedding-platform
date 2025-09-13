<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

// Get user's invitations
$invitations = $db->fetchAll(
    "SELECT i.*, t.name as theme_name 
     FROM invitations i 
     LEFT JOIN themes t ON i.theme_id = t.id 
     WHERE i.user_id = ? 
     ORDER BY i.created_at DESC",
    [$user['id']]
);

// Get user statistics
$stats = [
    'total_invitations' => count($invitations),
    'total_guests' => $db->fetch("SELECT COUNT(*) as count FROM guests WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user['id']])['count'] ?? 0,
    'total_rsvp' => $db->fetch("SELECT COUNT(*) as count FROM rsvp_responses WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user['id']])['count'] ?? 0,
    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages WHERE invitation_id IN (SELECT id FROM invitations WHERE user_id = ?)", [$user['id']])['count'] ?? 0
];

// Handle invitation actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $invitation_id = intval($_POST['invitation_id']);
    
    // Verify invitation belongs to user
    $invitation = $db->fetch("SELECT * FROM invitations WHERE id = ? AND user_id = ?", [$invitation_id, $user['id']]);
    
    if ($invitation) {
        switch ($action) {
            case 'toggle_status':
                $new_status = $invitation['is_active'] ? 0 : 1;
                $db->query("UPDATE invitations SET is_active = ? WHERE id = ?", [$new_status, $invitation_id]);
                break;
            
            case 'delete':
                $db->query("DELETE FROM invitations WHERE id = ?", [$invitation_id]);
                break;
        }
        
        header('Location: /dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="/" class="flex items-center space-x-3">
                <i class="fas fa-heart text-2xl text-pink-500"></i>
                <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800"><?= SITE_NAME ?></span>
            </a>
            
            <div class="flex items-center md:order-2 space-x-3 md:space-x-0">
                <!-- User Menu Dropdown -->
                <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <span class="sr-only">Open user menu</span>
                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-medium"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                    </div>
                </button>
                
                <!-- Dropdown menu -->
                <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow" id="user-dropdown">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                        <span class="block text-sm text-gray-500 truncate"><?= htmlspecialchars($user['email']) ?></span>
                        <span class="block text-xs text-pink-600 font-medium mt-1">
                            Plan: <?= ucfirst($user['subscription_plan']) ?>
                        </span>
                    </div>
                    <ul class="py-2">
                        <li><a href="/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-user mr-2"></i>Profile</a></li>
                        <li><a href="/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-cog mr-2"></i>Settings</a></li>
                        <li><a href="/subscription.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-crown mr-2"></i>Upgrade Plan</a></li>
                        <li><hr class="my-2"></li>
                        <li><a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a></li>
                    </ul>
                </div>
                
                <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                    </svg>
                </button>
            </div>
            
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-user">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 md:flex-row md:mt-0 md:border-0 md:bg-white">
                    <li><a href="/dashboard.php" class="block py-2 px-3 text-pink-600 bg-gray-100 rounded md:bg-transparent md:text-pink-600 md:p-0">Dashboard</a></li>
                    <li><a href="/invitations.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Undangan</a></li>
                    <li><a href="/guests.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Tamu</a></li>
                    <li><a href="/rsvp.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">RSVP</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-600">Selamat datang kembali, <?= htmlspecialchars($user['full_name']) ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-heart text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Undangan</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['total_invitations'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Tamu</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['total_guests'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-check-circle text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">RSVP</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['total_rsvp'] ?></dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Ucapan</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['total_messages'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Aksi Cepat</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="/create-invitation.php" class="flex items-center p-4 bg-pink-50 rounded-lg hover:bg-pink-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-pink-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-plus text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Buat Undangan Baru</p>
                                <p class="text-sm text-gray-500">Mulai proyek undangan</p>
                            </div>
                        </a>

                        <a href="/guests.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user-plus text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Kelola Tamu</p>
                                <p class="text-sm text-gray-500">Tambah & atur daftar tamu</p>
                            </div>
                        </a>

                        <a href="/themes.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-palette text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Pilih Tema</p>
                                <p class="text-sm text-gray-500">Ubah tampilan undangan</p>
                            </div>
                        </a>

                        <a href="/analytics.php" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-bar text-white"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Lihat Analytics</p>
                                <p class="text-sm text-gray-500">Statistik undangan</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Invitations -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">Undangan Terbaru</h2>
                    <a href="/create-invitation.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                        <i class="fas fa-plus mr-2"></i>
                        Buat Baru
                    </a>
                </div>
                
                <?php if (empty($invitations)): ?>
                <div class="p-6 text-center">
                    <div class="max-w-sm mx-auto">
                        <i class="fas fa-heart text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Undangan</h3>
                        <p class="text-gray-500 mb-4">Mulai buat undangan pernikahan digital pertama Anda</p>
                        <a href="/create-invitation.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                            <i class="fas fa-plus mr-2"></i>
                            Buat Undangan Pertama
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Undangan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Acara</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tema</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($invitations as $invitation): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-pink-100 flex items-center justify-center">
                                                <i class="fas fa-heart text-pink-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($invitation['title']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($invitation['groom_name']) ?> & <?= htmlspecialchars($invitation['bride_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= formatDate($invitation['wedding_date'], 'd M Y') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($invitation['is_active']): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($invitation['theme_name'] ?: 'Default') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="/invitation/<?= htmlspecialchars($invitation['slug']) ?>" 
                                           target="_blank"
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/edit-invitation.php?id=<?= $invitation['id'] ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                <i class="fas fa-<?= $invitation['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus undangan ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="invitation_id" value="<?= $invitation['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>
</body>
</html>
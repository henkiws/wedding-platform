<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$errors = [];
$success = '';

// Get user's invitations for dropdown
$invitations = $db->fetchAll(
    "SELECT id, title, groom_name, bride_name, wedding_date FROM invitations WHERE user_id = ? ORDER BY created_at DESC",
    [$user['id']]
);

// Get selected invitation
$selected_invitation_id = intval($_GET['invitation'] ?? $_POST['invitation_id'] ?? ($invitations[0]['id'] ?? 0));

if ($selected_invitation_id > 0) {
    // Verify invitation belongs to user
    $selected_invitation = $db->fetch(
        "SELECT * FROM invitations WHERE id = ? AND user_id = ?",
        [$selected_invitation_id, $user['id']]
    );
    
    if (!$selected_invitation) {
        $selected_invitation_id = 0;
    }
}

// Get RSVP responses for selected invitation
$rsvp_responses = [];
$rsvp_stats = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'total' => 0];

if ($selected_invitation_id > 0) {
    $rsvp_responses = $db->fetchAll(
        "SELECT *, DATE_FORMAT(responded_at, '%d/%m/%Y %H:%i') as formatted_date 
         FROM rsvp_responses 
         WHERE invitation_id = ? 
         ORDER BY responded_at DESC",
        [$selected_invitation_id]
    );
    
    // Calculate statistics
    foreach ($rsvp_responses as $response) {
        $rsvp_stats[$response['attendance']]++;
        $rsvp_stats['total']++;
    }
}

// Get guest messages for selected invitation
$guest_messages = [];
if ($selected_invitation_id > 0) {
    $guest_messages = $db->fetchAll(
        "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date 
         FROM guest_messages 
         WHERE invitation_id = ? 
         ORDER BY created_at DESC",
        [$selected_invitation_id]
    );
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'delete_rsvp') {
        $rsvp_id = intval($_POST['rsvp_id']);
        
        // Verify RSVP belongs to user's invitation
        $rsvp = $db->fetch(
            "SELECT r.*, i.user_id FROM rsvp_responses r 
             JOIN invitations i ON r.invitation_id = i.id 
             WHERE r.id = ? AND i.user_id = ?",
            [$rsvp_id, $user['id']]
        );
        
        if ($rsvp) {
            $db->query("DELETE FROM rsvp_responses WHERE id = ?", [$rsvp_id]);
            $success = 'RSVP berhasil dihapus!';
            
            // Refresh data
            $rsvp_responses = $db->fetchAll(
                "SELECT *, DATE_FORMAT(responded_at, '%d/%m/%Y %H:%i') as formatted_date 
                 FROM rsvp_responses 
                 WHERE invitation_id = ? 
                 ORDER BY responded_at DESC",
                [$selected_invitation_id]
            );
            
            // Recalculate statistics
            $rsvp_stats = ['yes' => 0, 'no' => 0, 'maybe' => 0, 'total' => 0];
            foreach ($rsvp_responses as $response) {
                $rsvp_stats[$response['attendance']]++;
                $rsvp_stats['total']++;
            }
        }
    }
    
    elseif ($action == 'delete_message') {
        $message_id = intval($_POST['message_id']);
        
        // Verify message belongs to user's invitation
        $message = $db->fetch(
            "SELECT m.*, i.user_id FROM guest_messages m 
             JOIN invitations i ON m.invitation_id = i.id 
             WHERE m.id = ? AND i.user_id = ?",
            [$message_id, $user['id']]
        );
        
        if ($message) {
            $db->query("DELETE FROM guest_messages WHERE id = ?", [$message_id]);
            $success = 'Ucapan berhasil dihapus!';
            
            // Refresh messages
            $guest_messages = $db->fetchAll(
                "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date 
                 FROM guest_messages 
                 WHERE invitation_id = ? 
                 ORDER BY created_at DESC",
                [$selected_invitation_id]
            );
        }
    }
    
    elseif ($action == 'toggle_message_approval') {
        $message_id = intval($_POST['message_id']);
        
        // Verify message belongs to user's invitation
        $message = $db->fetch(
            "SELECT m.*, i.user_id FROM guest_messages m 
             JOIN invitations i ON m.invitation_id = i.id 
             WHERE m.id = ? AND i.user_id = ?",
            [$message_id, $user['id']]
        );
        
        if ($message) {
            $new_status = $message['is_approved'] ? 0 : 1;
            $db->query("UPDATE guest_messages SET is_approved = ? WHERE id = ?", [$new_status, $message_id]);
            $success = $new_status ? 'Ucapan disetujui!' : 'Ucapan disembunyikan!';
            
            // Refresh messages
            $guest_messages = $db->fetchAll(
                "SELECT *, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date 
                 FROM guest_messages 
                 WHERE invitation_id = ? 
                 ORDER BY created_at DESC",
                [$selected_invitation_id]
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVP Management - <?= SITE_NAME ?></title>
    
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
                <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-medium"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                    </div>
                </button>
                
                <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow" id="user-dropdown">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                        <span class="block text-sm text-gray-500 truncate"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <ul class="py-2">
                        <li><a href="/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a></li>
                        <li><a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 md:flex-row md:mt-0 md:border-0 md:bg-white">
                    <li><a href="/dashboard.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Dashboard</a></li>
                    <li><a href="/invitations.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Undangan</a></li>
                    <li><a href="/guests.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Tamu</a></li>
                    <li><a href="/rsvp.php" class="block py-2 px-3 text-pink-600 bg-gray-100 rounded md:bg-transparent md:text-pink-600 md:p-0">RSVP</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">RSVP Management</h1>
                <p class="text-gray-600">Kelola konfirmasi kehadiran dan ucapan dari tamu</p>
            </div>

            <?php if (empty($invitations)): ?>
            <!-- No Invitations -->
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <i class="fas fa-heart text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Undangan</h3>
                <p class="text-gray-500 mb-4">Buat undangan terlebih dahulu untuk melihat RSVP</p>
                <a href="/create-invitation.php" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                    <i class="fas fa-plus mr-2"></i>
                    Buat Undangan
                </a>
            </div>
            <?php else: ?>

            <!-- Invitation Selector -->
            <div class="bg-white rounded-lg shadow mb-8 p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pilih Undangan</h3>
                <form method="GET" class="flex items-center space-x-4">
                    <div class="flex-1">
                        <select name="invitation" 
                                onchange="this.form.submit()"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            <option value="">Pilih Undangan</option>
                            <?php foreach ($invitations as $inv): ?>
                            <option value="<?= $inv['id'] ?>" <?= ($inv['id'] == $selected_invitation_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($inv['title']) ?> - <?= formatDate($inv['wedding_date']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_invitation_id > 0): ?>

            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                <div class="font-medium mb-2">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                <div class="font-medium mb-2">Berhasil!</div>
                <p><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <!-- RSVP Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-reply text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Total RSVP</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $rsvp_stats['total'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-check text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Akan Hadir</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $rsvp_stats['yes'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-times text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Tidak Hadir</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $rsvp_stats['no'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <i class="fas fa-question text-white"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500">Mungkin</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $rsvp_stats['maybe'] ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="showTab('rsvp')" id="tab-rsvp" 
                                class="tab-button border-pink-500 text-pink-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-reply mr-2"></i>
                            RSVP Responses (<?= count($rsvp_responses) ?>)
                        </button>
                        <button onclick="showTab('messages')" id="tab-messages" 
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-comments mr-2"></i>
                            Guest Messages (<?= count($guest_messages) ?>)
                        </button>
                    </nav>
                </div>
            </div>

            <!-- RSVP Responses Tab -->
            <div id="content-rsvp" class="tab-content">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">RSVP Responses</h3>
                            <button onclick="exportRSVP()" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i>
                                Export CSV
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($rsvp_responses)): ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada RSVP</h4>
                        <p class="text-gray-500">Tamu akan muncul di sini setelah mengisi konfirmasi kehadiran</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Tamu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kehadiran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($rsvp_responses as $rsvp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-gray-600">
                                                        <?= strtoupper(substr($rsvp['guest_name'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($rsvp['guest_name']) ?>
                                                </div>
                                                <?php if ($rsvp['message']): ?>
                                                <div class="text-sm text-gray-500 max-w-xs truncate">
                                                    "<?= htmlspecialchars($rsvp['message']) ?>"
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($rsvp['attendance'] == 'yes'): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Hadir
                                            </span>
                                        <?php elseif ($rsvp['attendance'] == 'no'): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1"></i>
                                                Tidak Hadir
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-question mr-1"></i>
                                                Mungkin
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $rsvp['guest_count'] ?> orang
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($rsvp['phone']): ?>
                                        <div class="text-sm text-gray-900">
                                            <i class="fas fa-phone text-gray-400 mr-1"></i>
                                            <?= htmlspecialchars($rsvp['phone']) ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-sm text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $rsvp['formatted_date'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($rsvp['message']): ?>
                                            <button onclick="showMessage('<?= htmlspecialchars($rsvp['guest_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($rsvp['message'], ENT_QUOTES) ?>')" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($rsvp['phone']): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $rsvp['phone']) ?>" 
                                               target="_blank"
                                               class="text-green-600 hover:text-green-900">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="delete_rsvp">
                                                <input type="hidden" name="rsvp_id" value="<?= $rsvp['id'] ?>">
                                                <button type="submit" 
                                                        onclick="return confirm('Yakin ingin menghapus RSVP ini?')"
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
                    <?php endif; ?>
                </div>
            </div>

            <!-- Guest Messages Tab -->
            <div id="content-messages" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Guest Messages & Wishes</h3>
                            <button onclick="exportMessages()" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i>
                                Export CSV
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($guest_messages)): ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-heart text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Ucapan</h4>
                        <p class="text-gray-500">Ucapan dan doa dari tamu akan muncul di sini</p>
                    </div>
                    <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($guest_messages as $message): ?>
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-pink-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-pink-600">
                                                <?= strtoupper(substr($message['sender_name'], 0, 1)) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($message['sender_name']) ?>
                                            </p>
                                            <span class="text-xs text-gray-500">
                                                <?= $message['formatted_date'] ?>
                                            </span>
                                            <?php if (!$message['is_approved']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-eye-slash mr-1"></i>
                                                    Hidden
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">
                                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center space-x-2 ml-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_message_approval">
                                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                        <button type="submit" 
                                                class="text-sm px-3 py-1 rounded <?= $message['is_approved'] ? 'text-yellow-700 hover:text-yellow-900' : 'text-green-700 hover:text-green-900' ?>">
                                            <i class="fas fa-<?= $message['is_approved'] ? 'eye-slash' : 'eye' ?> mr-1"></i>
                                            <?= $message['is_approved'] ? 'Hide' : 'Show' ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_message">
                                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                        <button type="submit" 
                                                onclick="return confirm('Yakin ingin menghapus ucapan ini?')"
                                                class="text-sm px-3 py-1 rounded text-red-700 hover:text-red-900">
                                            <i class="fas fa-trash mr-1"></i>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="message-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeMessageModal()"></div>
            
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">RSVP Message</h3>
                    <button onclick="closeMessageModal()" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mt-2">
                    <p class="text-sm text-gray-700" id="modal-message"></p>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button onclick="closeMessageModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-pink-500', 'text-pink-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active class to selected tab button
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.remove('border-transparent', 'text-gray-500');
            activeButton.classList.add('border-pink-500', 'text-pink-600');
        }
        
        // Message modal functions
        function showMessage(guestName, message) {
            document.getElementById('modal-title').textContent = `Message from ${guestName}`;
            document.getElementById('modal-message').innerHTML = message.replace(/\n/g, '<br>');
            document.getElementById('message-modal').classList.remove('hidden');
        }
        
        function closeMessageModal() {
            document.getElementById('message-modal').classList.add('hidden');
        }
        
        // Export functions
        function exportRSVP() {
            const rsvpData = <?= json_encode($rsvp_responses) ?>;
            let csvContent = "Nama,Kehadiran,Jumlah Tamu,Telepon,Pesan,Tanggal RSVP\n";
            
            rsvpData.forEach(rsvp => {
                const attendance = rsvp.attendance === 'yes' ? 'Hadir' : (rsvp.attendance === 'no' ? 'Tidak Hadir' : 'Mungkin');
                const row = [
                    rsvp.guest_name,
                    attendance,
                    rsvp.guest_count,
                    rsvp.phone || '',
                    (rsvp.message || '').replace(/"/g, '""'),
                    rsvp.formatted_date
                ].map(field => `"${field}"`).join(',');
                csvContent += row + "\n";
            });
            
            downloadCSV(csvContent, 'rsvp_responses.csv');
        }
        
        function exportMessages() {
            const messageData = <?= json_encode($guest_messages) ?>;
            let csvContent = "Nama,Ucapan,Status,Tanggal\n";
            
            messageData.forEach(message => {
                const status = message.is_approved == 1 ? 'Disetujui' : 'Disembunyikan';
                const row = [
                    message.sender_name,
                    message.message.replace(/"/g, '""'),
                    status,
                    message.formatted_date
                ].map(field => `"${field}"`).join(',');
                csvContent += row + "\n";
            });
            
            downloadCSV(csvContent, 'guest_messages.csv');
        }
        
        function downloadCSV(csvContent, filename) {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        // Initialize first tab
        showTab('rsvp');
    </script>
</body>
</html>
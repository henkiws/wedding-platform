<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$errors = [];
$success = '';

// Get user's invitations for dropdown
$invitations = $db->fetchAll(
    "SELECT id, title, groom_name, bride_name FROM invitations WHERE user_id = ? ORDER BY created_at DESC",
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

// Get guests for selected invitation
$guests = [];
if ($selected_invitation_id > 0) {
    $guests = $db->fetchAll(
        "SELECT g.*, 
        (SELECT COUNT(*) FROM rsvp_responses r WHERE r.invitation_id = g.invitation_id AND r.guest_name = g.name) as has_rsvp
        FROM guests g 
        WHERE g.invitation_id = ? 
        ORDER BY g.created_at DESC",
        [$selected_invitation_id]
    );
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_guest') {
        $invitation_id = intval($_POST['invitation_id']);
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $guest_type = $_POST['guest_type'] ?? 'friend';
        
        // Verify invitation belongs to user
        $invitation = $db->fetch(
            "SELECT * FROM invitations WHERE id = ? AND user_id = ?",
            [$invitation_id, $user['id']]
        );
        
        if (!$invitation) {
            $errors[] = 'Undangan tidak ditemukan';
        } elseif (empty($name)) {
            $errors[] = 'Nama tamu harus diisi';
        } else {
            // Check guest limit for free users
            if ($user['subscription_plan'] == 'free') {
                $current_count = $db->fetch(
                    "SELECT COUNT(*) as count FROM guests WHERE invitation_id = ?",
                    [$invitation_id]
                )['count'] ?? 0;
                
                if ($current_count >= FREE_GUEST_LIMIT) {
                    $errors[] = "Batas tamu tercapai. Paket gratis maksimal " . FREE_GUEST_LIMIT . " tamu.";
                }
            }
            
            if (empty($errors)) {
                try {
                    $db->query(
                        "INSERT INTO guests (invitation_id, name, phone, email, address, guest_type) 
                        VALUES (?, ?, ?, ?, ?, ?)",
                        [$invitation_id, $name, $phone, $email, $address, $guest_type]
                    );
                    
                    $success = 'Tamu berhasil ditambahkan!';
                    $selected_invitation_id = $invitation_id;
                    
                    // Refresh guests list
                    $guests = $db->fetchAll(
                        "SELECT g.*, 
                        (SELECT COUNT(*) FROM rsvp_responses r WHERE r.invitation_id = g.invitation_id AND r.guest_name = g.name) as has_rsvp
                        FROM guests g 
                        WHERE g.invitation_id = ? 
                        ORDER BY g.created_at DESC",
                        [$selected_invitation_id]
                    );
                } catch (Exception $e) {
                    $errors[] = 'Gagal menambah tamu: ' . $e->getMessage();
                }
            }
        }
    }
    
    elseif ($action == 'delete_guest') {
        $guest_id = intval($_POST['guest_id']);
        
        // Verify guest belongs to user's invitation
        $guest = $db->fetch(
            "SELECT g.*, i.user_id FROM guests g 
            JOIN invitations i ON g.invitation_id = i.id 
            WHERE g.id = ? AND i.user_id = ?",
            [$guest_id, $user['id']]
        );
        
        if ($guest) {
            $db->query("DELETE FROM guests WHERE id = ?", [$guest_id]);
            $success = 'Tamu berhasil dihapus!';
            
            // Refresh guests list
            if ($selected_invitation_id > 0) {
                $guests = $db->fetchAll(
                    "SELECT g.*, 
                    (SELECT COUNT(*) FROM rsvp_responses r WHERE r.invitation_id = g.invitation_id AND r.guest_name = g.name) as has_rsvp
                    FROM guests g 
                    WHERE g.invitation_id = ? 
                    ORDER BY g.created_at DESC",
                    [$selected_invitation_id]
                );
            }
        }
    }
    
    elseif ($action == 'bulk_import') {
        $invitation_id = intval($_POST['invitation_id']);
        
        // Verify invitation belongs to user
        $invitation = $db->fetch(
            "SELECT * FROM invitations WHERE id = ? AND user_id = ?",
            [$invitation_id, $user['id']]
        );
        
        if (!$invitation) {
            $errors[] = 'Undangan tidak ditemukan';
        } elseif (!empty($_FILES['csv_file']['tmp_name'])) {
            $csv_file = $_FILES['csv_file']['tmp_name'];
            
            if (($handle = fopen($csv_file, "r")) !== FALSE) {
                $imported_count = 0;
                $header = fgetcsv($handle); // Skip header row
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 1 && !empty(trim($data[0]))) {
                        $name = sanitize(trim($data[0]));
                        $phone = sanitize(trim($data[1] ?? ''));
                        $email = sanitize(trim($data[2] ?? ''));
                        $address = sanitize(trim($data[3] ?? ''));
                        $guest_type = in_array(trim($data[4] ?? ''), ['family', 'friend', 'colleague', 'other']) ? trim($data[4]) : 'friend';
                        
                        if (!empty($name)) {
                            try {
                                $db->query(
                                    "INSERT INTO guests (invitation_id, name, phone, email, address, guest_type) 
                                    VALUES (?, ?, ?, ?, ?, ?)",
                                    [$invitation_id, $name, $phone, $email, $address, $guest_type]
                                );
                                $imported_count++;
                            } catch (Exception $e) {
                                // Skip duplicate or invalid entries
                            }
                        }
                    }
                }
                fclose($handle);
                
                if ($imported_count > 0) {
                    $success = "{$imported_count} tamu berhasil diimport!";
                    $selected_invitation_id = $invitation_id;
                    
                    // Refresh guests list
                    $guests = $db->fetchAll(
                        "SELECT g.*, 
                        (SELECT COUNT(*) FROM rsvp_responses r WHERE r.invitation_id = g.invitation_id AND r.guest_name = g.name) as has_rsvp
                        FROM guests g 
                        WHERE g.invitation_id = ? 
                        ORDER BY g.created_at DESC",
                        [$selected_invitation_id]
                    );
                } else {
                    $errors[] = 'Tidak ada tamu yang berhasil diimport';
                }
            } else {
                $errors[] = 'Gagal membaca file CSV';
            }
        } else {
            $errors[] = 'File CSV harus diupload';
        }
    }
}

// Calculate guest limit info
$guest_limit = ($user['subscription_plan'] == 'free') ? FREE_GUEST_LIMIT : 'Unlimited';
$current_guest_count = count($guests);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tamu - <?= SITE_NAME ?></title>
    
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
                    <li><a href="/guests.php" class="block py-2 px-3 text-pink-600 bg-gray-100 rounded md:bg-transparent md:text-pink-600 md:p-0">Tamu</a></li>
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
                <h1 class="text-3xl font-bold text-gray-900">Kelola Tamu</h1>
                <p class="text-gray-600">Tambah dan kelola daftar tamu undangan</p>
            </div>

            <?php if (empty($invitations)): ?>
            <!-- No Invitations -->
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <i class="fas fa-heart text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Undangan</h3>
                <p class="text-gray-500 mb-4">Buat undangan terlebih dahulu sebelum menambah tamu</p>
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
                                <?= htmlspecialchars($inv['title']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php if ($selected_invitation_id > 0): ?>
                            <?= $current_guest_count ?> / <?= $guest_limit ?> tamu
                        <?php endif; ?>
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

            <!-- Add Guest Form -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Tambah Tamu Baru</h3>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_guest">
                        <input type="hidden" name="invitation_id" value="<?= $selected_invitation_id ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Tamu *
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                       placeholder="John Doe"
                                       required>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    No. Telepon
                                </label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                       placeholder="081234567890">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                       placeholder="john@email.com">
                            </div>
                            
                            <div>
                                <label for="guest_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Kategori
                                </label>
                                <select id="guest_type" 
                                        name="guest_type" 
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                    <option value="family">Keluarga</option>
                                    <option value="friend" selected>Teman</option>
                                    <option value="colleague">Rekan Kerja</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                    Alamat
                                </label>
                                <input type="text" 
                                       id="address" 
                                       name="address" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                       placeholder="Jl. Contoh No. 123, Jakarta">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4">
                            <button type="button" 
                                    onclick="document.getElementById('bulk-import-modal').classList.remove('hidden')"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-upload mr-2"></i>
                                Import CSV
                            </button>
                            
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Tamu
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Guests List -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Daftar Tamu (<?= count($guests) ?>)</h3>
                        <div class="flex space-x-2">
                            <button onclick="exportGuestList()" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i>
                                Export CSV
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($guests)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Tamu</h4>
                    <p class="text-gray-500">Tambahkan tamu pertama untuk undangan ini</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RSVP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($guests as $guest): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-600">
                                                    <?= strtoupper(substr($guest['name'], 0, 1)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($guest['name']) ?>
                                            </div>
                                            <?php if ($guest['address']): ?>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($guest['address']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($guest['phone']): ?>
                                    <div class="text-sm text-gray-900">
                                        <i class="fas fa-phone text-gray-400 mr-1"></i>
                                        <?= htmlspecialchars($guest['phone']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($guest['email']): ?>
                                    <div class="text-sm text-gray-500">
                                        <i class="fas fa-envelope text-gray-400 mr-1"></i>
                                        <?= htmlspecialchars($guest['email']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        <?php 
                                        switch($guest['guest_type']) {
                                            case 'family': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'friend': echo 'bg-green-100 text-green-800'; break;
                                            case 'colleague': echo 'bg-blue-100 text-blue-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php
                                        switch($guest['guest_type']) {
                                            case 'family': echo 'Keluarga'; break;
                                            case 'friend': echo 'Teman'; break;
                                            case 'colleague': echo 'Rekan Kerja'; break;
                                            default: echo 'Lainnya';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($guest['has_rsvp'] > 0): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>
                                            Sudah RSVP
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            Belum RSVP
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($guest['phone']): ?>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $guest['phone']) ?>?text=<?= urlencode('Undangan Pernikahan - ' . SITE_URL . '/invitation/' . $selected_invitation['slug']) ?>" 
                                           target="_blank"
                                           class="text-green-600 hover:text-green-900">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <button onclick="editGuest(<?= $guest['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete_guest">
                                            <input type="hidden" name="guest_id" value="<?= $guest['id'] ?>">
                                            <button type="submit" 
                                                    onclick="return confirm('Yakin ingin menghapus tamu ini?')"
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

            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div id="bulk-import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="document.getElementById('bulk-import-modal').classList.add('hidden')"></div>
            
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Import Tamu dari CSV</h3>
                    <button onclick="document.getElementById('bulk-import-modal').classList.add('hidden')" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bulk_import">
                    <input type="hidden" name="invitation_id" value="<?= $selected_invitation_id ?>">
                    
                    <div class="mb-4">
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">
                            File CSV
                        </label>
                        <input type="file" 
                               id="csv_file" 
                               name="csv_file" 
                               accept=".csv"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100"
                               required>
                    </div>
                    
                    <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">Format CSV:</h4>
                        <p class="text-xs text-blue-700 mb-2">Kolom: Nama,Telepon,Email,Alamat,Kategori</p>
                        <p class="text-xs text-blue-600">Kategori: family, friend, colleague, other</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="document.getElementById('bulk-import-modal').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-pink-600 rounded-md hover:bg-pink-700">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        function editGuest(guestId) {
            // Simple implementation - in a real app, you'd open a modal with edit form
            alert('Edit functionality would open modal for guest ID: ' + guestId);
        }
        
        function exportGuestList() {
            // Create CSV content
            const guests = <?= json_encode($guests) ?>;
            let csvContent = "Nama,Telepon,Email,Alamat,Kategori,RSVP\n";
            
            guests.forEach(guest => {
                const rsvpStatus = guest.has_rsvp > 0 ? 'Sudah RSVP' : 'Belum RSVP';
                const row = [
                    guest.name,
                    guest.phone || '',
                    guest.email || '',
                    guest.address || '',
                    guest.guest_type,
                    rsvpStatus
                ].map(field => `"${field.replace(/"/g, '""')}"`).join(',');
                csvContent += row + "\n";
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'daftar_tamu.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    </script>
</body>
</html>
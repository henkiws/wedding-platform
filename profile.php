<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'Nama lengkap harus diisi';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid';
        }
        
        // Check if email is already used by another user
        if ($email !== $user['email']) {
            $existing_email = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']]);
            if ($existing_email) {
                $errors[] = 'Email sudah digunakan pengguna lain';
            }
        }
        
        // Password validation
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = 'Password lama harus diisi untuk mengubah password';
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = 'Password lama tidak benar';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'Password baru minimal 6 karakter';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'Konfirmasi password baru tidak sama';
            }
        }
        
        // Update profile
        if (empty($errors)) {
            try {
                if (!empty($new_password)) {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $db->query(
                        "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$full_name, $email, $phone, $hashed_password, $user['id']]
                    );
                } else {
                    // Update without password change
                    $db->query(
                        "UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$full_name, $email, $phone, $user['id']]
                    );
                }
                
                $success = 'Profile berhasil diperbarui!';
                
                // Update session data
                $_SESSION['full_name'] = $full_name;
                
                // Refresh user data
                $user = getCurrentUser();
            } catch (Exception $e) {
                $errors[] = 'Gagal memperbarui profile: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action == 'delete_account') {
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        $password_verify = $_POST['password_verify'] ?? '';
        
        if ($confirm_delete !== 'DELETE') {
            $errors[] = 'Ketik "DELETE" untuk mengkonfirmasi penghapusan akun';
        } elseif (!password_verify($password_verify, $user['password'])) {
            $errors[] = 'Password tidak benar';
        } else {
            try {
                // Delete user and all related data (CASCADE will handle this)
                $db->query("DELETE FROM users WHERE id = ?", [$user['id']]);
                
                // Clear session
                session_destroy();
                
                // Redirect to homepage with message
                header('Location: /?message=account_deleted');
                exit;
            } catch (Exception $e) {
                $errors[] = 'Gagal menghapus akun: ' . $e->getMessage();
            }
        }
    }
}

// Get user statistics
$stats = [
    'invitations' => $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE user_id = ?", [$user['id']])['count'] ?? 0,
    'total_guests' => $db->fetch("SELECT COUNT(*) as count FROM guests g JOIN invitations i ON g.invitation_id = i.id WHERE i.user_id = ?", [$user['id']])['count'] ?? 0,
    'total_rsvp' => $db->fetch("SELECT COUNT(*) as count FROM rsvp_responses r JOIN invitations i ON r.invitation_id = i.id WHERE i.user_id = ?", [$user['id']])['count'] ?? 0,
    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages m JOIN invitations i ON m.invitation_id = i.id WHERE i.user_id = ?", [$user['id']])['count'] ?? 0
];

// Calculate account age
$account_age = '';
if ($user['created_at']) {
    $created = new DateTime($user['created_at']);
    $now = new DateTime();
    $diff = $created->diff($now);
    
    if ($diff->y > 0) {
        $account_age = $diff->y . ' tahun';
    } elseif ($diff->m > 0) {
        $account_age = $diff->m . ' bulan';
    } else {
        $account_age = $diff->d . ' hari';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?= SITE_NAME ?></title>
    
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
                <a href="/dashboard.php" class="text-gray-500 hover:text-gray-700 px-3 py-2">
                    <i class="fas fa-arrow-left mr-2"></i>Dashboard
                </a>
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
                        <li><a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Profile Settings</h1>
                <p class="text-gray-600">Kelola informasi akun dan preferensi Anda</p>
            </div>

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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Profile Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-pink-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-white font-bold text-2xl"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($user['full_name']) ?></h2>
                            <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                            
                            <!-- Subscription Badge -->
                            <div class="mt-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    <?php 
                                    switch($user['subscription_plan']) {
                                        case 'premium': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'business': echo 'bg-purple-100 text-purple-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php if ($user['subscription_plan'] == 'premium'): ?>
                                        <i class="fas fa-crown mr-2"></i>Premium
                                    <?php elseif ($user['subscription_plan'] == 'business'): ?>
                                        <i class="fas fa-briefcase mr-2"></i>Business
                                    <?php else: ?>
                                        <i class="fas fa-user mr-2"></i>Free
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <!-- Account Info -->
                            <div class="mt-6 text-sm text-gray-500 space-y-1">
                                <div><i class="fas fa-calendar mr-2"></i>Bergabung <?= $account_age ?> lalu</div>
                                <div><i class="fas fa-clock mr-2"></i>Terakhir update: <?= formatDate($user['updated_at'], 'd M Y H:i') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Statistik Akun</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600"><i class="fas fa-heart mr-2 text-pink-500"></i>Undangan</span>
                                <span class="font-medium"><?= $stats['invitations'] ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600"><i class="fas fa-users mr-2 text-blue-500"></i>Total Tamu</span>
                                <span class="font-medium"><?= $stats['total_guests'] ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600"><i class="fas fa-reply mr-2 text-green-500"></i>RSVP</span>
                                <span class="font-medium"><?= $stats['total_rsvp'] ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600"><i class="fas fa-comments mr-2 text-purple-500"></i>Ucapan</span>
                                <span class="font-medium"><?= $stats['total_messages'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="/subscription.php" class="w-full inline-flex items-center justify-center px-4 py-2 border border-pink-600 text-sm font-medium rounded-md text-pink-600 bg-white hover:bg-pink-50">
                                <i class="fas fa-crown mr-2"></i>
                                Upgrade Plan
                            </a>
                            <a href="/create-invitation.php" class="w-full inline-flex items-center justify-center px-4 py-2 border border-blue-600 text-sm font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50">
                                <i class="fas fa-plus mr-2"></i>
                                Buat Undangan
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <!-- Profile Form -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Informasi Profile</h3>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Nama Lengkap *
                                        </label>
                                        <input type="text" 
                                               id="full_name" 
                                               name="full_name" 
                                               value="<?= htmlspecialchars($user['full_name']) ?>"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                               required>
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                            Email *
                                        </label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               value="<?= htmlspecialchars($user['email']) ?>"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                               required>
                                    </div>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        No. Telepon
                                    </label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= htmlspecialchars($user['phone']) ?>"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                </div>

                                <hr class="border-gray-200">

                                <div class="space-y-4">
                                    <h4 class="text-md font-medium text-gray-900">Ubah Password (Opsional)</h4>
                                    
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                            Password Lama
                                        </label>
                                        <input type="password" 
                                               id="current_password" 
                                               name="current_password" 
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                        <p class="mt-1 text-xs text-gray-500">Isi hanya jika ingin mengubah password</p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Password Baru
                                            </label>
                                            <input type="password" 
                                                   id="new_password" 
                                                   name="new_password" 
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                        </div>

                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                                Konfirmasi Password
                                            </label>
                                            <input type="password" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        <i class="fas fa-save mr-2"></i>
                                        Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="bg-white rounded-lg shadow mt-8">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Account Settings</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-6">
                                <!-- Email Notifications -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Email Notifications</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input id="notify_rsvp" 
                                                   name="notify_rsvp" 
                                                   type="checkbox" 
                                                   checked
                                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="notify_rsvp" class="ml-3 text-sm text-gray-700">
                                                RSVP baru dari tamu
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="notify_messages" 
                                                   name="notify_messages" 
                                                   type="checkbox" 
                                                   checked
                                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="notify_messages" class="ml-3 text-sm text-gray-700">
                                                Ucapan dan pesan baru
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="notify_promotions" 
                                                   name="notify_promotions" 
                                                   type="checkbox" 
                                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="notify_promotions" class="ml-3 text-sm text-gray-700">
                                                Promosi dan penawaran khusus
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Privacy Settings -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Privacy Settings</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input id="public_profile" 
                                                   name="public_profile" 
                                                   type="checkbox" 
                                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="public_profile" class="ml-3 text-sm text-gray-700">
                                                Tampilkan profile di direktori publik
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="analytics_sharing" 
                                                   name="analytics_sharing" 
                                                   type="checkbox" 
                                                   checked
                                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="analytics_sharing" class="ml-3 text-sm text-gray-700">
                                                Berbagi data analytics untuk meningkatkan layanan
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-white rounded-lg shadow mt-8 border-2 border-red-200">
                        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
                            <h3 class="text-lg font-medium text-red-900">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Danger Zone
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-md font-medium text-gray-900 mb-2">Delete Account</h4>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Menghapus akun akan menghapus semua data termasuk undangan, tamu, dan RSVP. 
                                        Tindakan ini tidak dapat dibatalkan.
                                    </p>
                                    <button onclick="openDeleteModal()" 
                                            class="inline-flex items-center px-4 py-2 border border-red-600 text-sm font-medium rounded-md text-red-600 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-trash mr-2"></i>
                                        Delete My Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeDeleteModal()"></div>
            
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-red-900">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Delete Account
                    </h3>
                    <button onclick="closeDeleteModal()" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="mb-6">
                    <p class="text-sm text-gray-700 mb-4">
                        Tindakan ini akan menghapus secara permanen:
                    </p>
                    <ul class="text-sm text-red-600 space-y-1">
                        <li><i class="fas fa-times mr-2"></i>Semua undangan yang telah dibuat</li>
                        <li><i class="fas fa-times mr-2"></i>Daftar tamu dan RSVP</li>
                        <li><i class="fas fa-times mr-2"></i>Galeri foto dan video</li>
                        <li><i class="fas fa-times mr-2"></i>Data analitik</li>
                        <li><i class="fas fa-times mr-2"></i>Semua data akun</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete_account">
                    
                    <div class="mb-4">
                        <label for="confirm_delete" class="block text-sm font-medium text-gray-700 mb-2">
                            Ketik "DELETE" untuk mengkonfirmasi:
                        </label>
                        <input type="text" 
                               id="confirm_delete" 
                               name="confirm_delete" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                               placeholder="DELETE"
                               required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password_verify" class="block text-sm font-medium text-gray-700 mb-2">
                            Password untuk konfirmasi:
                        </label>
                        <input type="password" 
                               id="password_verify" 
                               name="password_verify" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                               required>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeDeleteModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        // Delete modal functions
        function openDeleteModal() {
            document.getElementById('delete-modal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
        }
        
        // Password strength checker
        const newPasswordInput = document.getElementById('new_password');
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                // You can add visual feedback here
            });
        }
        
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
        
        // Form validation
        document.querySelector('form[action=""]').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password baru dan konfirmasi password tidak sama');
                return false;
            }
        });
        
        // Auto-save settings (you can implement this for checkboxes)
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Implement auto-save functionality here
                console.log(`Setting ${this.name} changed to ${this.checked}`);
            });
        });
    </script>
</body>
</html>
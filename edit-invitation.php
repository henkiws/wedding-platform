<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$invitation_id = intval($_GET['id'] ?? 0);
$errors = [];
$success = '';

// Get invitation details
$invitation = $db->fetch(
    "SELECT * FROM invitations WHERE id = ? AND user_id = ?",
    [$invitation_id, $user['id']]
);

if (!$invitation) {
    header('Location: /dashboard.php');
    exit;
}

// Get available themes
$themes = $db->fetchAll("SELECT * FROM themes ORDER BY is_premium ASC, name ASC");

// Get gallery items
$gallery = $db->fetchAll(
    "SELECT * FROM gallery WHERE invitation_id = ? ORDER BY sort_order ASC, created_at ASC",
    [$invitation_id]
);

// Get digital gifts
$gifts = $db->fetchAll(
    "SELECT * FROM digital_gifts WHERE invitation_id = ? ORDER BY created_at ASC",
    [$invitation_id]
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? 'update';
    
    if ($action == 'update') {
        $title = sanitize($_POST['title'] ?? '');
        $groom_name = sanitize($_POST['groom_name'] ?? '');
        $bride_name = sanitize($_POST['bride_name'] ?? '');
        $wedding_date = $_POST['wedding_date'] ?? '';
        $wedding_time = $_POST['wedding_time'] ?? '';
        $venue_name = sanitize($_POST['venue_name'] ?? '');
        $venue_address = sanitize($_POST['venue_address'] ?? '');
        $venue_maps_link = sanitize($_POST['venue_maps_link'] ?? '');
        $theme_id = intval($_POST['theme_id'] ?? 1);
        $story = sanitize($_POST['story'] ?? '');
        $live_streaming_link = sanitize($_POST['live_streaming_link'] ?? '');
        
        // Validation
        if (empty($title)) $errors[] = 'Judul undangan harus diisi';
        if (empty($groom_name)) $errors[] = 'Nama mempelai pria harus diisi';
        if (empty($bride_name)) $errors[] = 'Nama mempelai wanita harus diisi';
        if (empty($wedding_date)) $errors[] = 'Tanggal pernikahan harus diisi';
        if (empty($wedding_time)) $errors[] = 'Waktu pernikahan harus diisi';
        if (empty($venue_name)) $errors[] = 'Nama tempat harus diisi';
        
        // Check theme permission
        $selected_theme = $db->fetch("SELECT * FROM themes WHERE id = ?", [$theme_id]);
        if ($selected_theme && $selected_theme['is_premium'] && !hasPermission('premium')) {
            $errors[] = 'Tema premium hanya untuk pengguna premium/business';
        }
        
        // Handle cover image upload
        $cover_image = $invitation['cover_image'];
        if (!empty($_FILES['cover_image']['tmp_name'])) {
            try {
                if (!is_dir(UPLOAD_DIR . 'covers/')) {
                    mkdir(UPLOAD_DIR . 'covers/', 0755, true);
                }
                $new_cover = uploadFile($_FILES['cover_image'], UPLOAD_DIR . 'covers/', ALLOWED_IMAGE_TYPES);
                
                // Delete old cover if exists
                if ($cover_image && file_exists(UPLOAD_DIR . 'covers/' . $cover_image)) {
                    unlink(UPLOAD_DIR . 'covers/' . $cover_image);
                }
                $cover_image = $new_cover;
            } catch (Exception $e) {
                $errors[] = 'Gagal upload cover image: ' . $e->getMessage();
            }
        }
        
        // Handle background music upload
        $background_music = $invitation['background_music'];
        if (!empty($_FILES['background_music']['tmp_name'])) {
            try {
                if (!is_dir(MUSIC_DIR)) {
                    mkdir(MUSIC_DIR, 0755, true);
                }
                $new_music = uploadFile($_FILES['background_music'], MUSIC_DIR, ALLOWED_AUDIO_TYPES);
                
                // Delete old music if exists
                if ($background_music && file_exists(MUSIC_DIR . $background_music)) {
                    unlink(MUSIC_DIR . $background_music);
                }
                $background_music = $new_music;
            } catch (Exception $e) {
                $errors[] = 'Gagal upload musik: ' . $e->getMessage();
            }
        }
        
        // Update invitation
        if (empty($errors)) {
            try {
                $db->query(
                    "UPDATE invitations SET 
                    title = ?, groom_name = ?, bride_name = ?, wedding_date = ?, wedding_time = ?,
                    venue_name = ?, venue_address = ?, venue_maps_link = ?, theme_id = ?,
                    background_music = ?, cover_image = ?, story = ?, live_streaming_link = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?",
                    [
                        $title, $groom_name, $bride_name, $wedding_date, $wedding_time,
                        $venue_name, $venue_address, $venue_maps_link, $theme_id,
                        $background_music, $cover_image, $story, $live_streaming_link,
                        $invitation_id, $user['id']
                    ]
                );
                
                $success = 'Undangan berhasil diupdate!';
                
                // Refresh invitation data
                $invitation = $db->fetch(
                    "SELECT * FROM invitations WHERE id = ? AND user_id = ?",
                    [$invitation_id, $user['id']]
                );
            } catch (Exception $e) {
                $errors[] = 'Gagal mengupdate undangan: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action == 'upload_gallery') {
        if (!empty($_FILES['gallery_files']['tmp_name'][0])) {
            $uploaded_count = 0;
            $gallery_limit = ($user['subscription_plan'] == 'free') ? FREE_GALLERY_LIMIT : 999;
            $current_count = count($gallery);
            
            if ($current_count >= $gallery_limit) {
                $errors[] = "Batas galeri tercapai. Paket {$user['subscription_plan']} maksimal {$gallery_limit} file.";
            } else {
                if (!is_dir(GALLERY_DIR)) {
                    mkdir(GALLERY_DIR, 0755, true);
                }
                
                foreach ($_FILES['gallery_files']['tmp_name'] as $key => $tmp_name) {
                    if (!empty($tmp_name) && ($current_count + $uploaded_count) < $gallery_limit) {
                        try {
                            $file_info = [
                                'name' => $_FILES['gallery_files']['name'][$key],
                                'tmp_name' => $tmp_name,
                                'size' => $_FILES['gallery_files']['size'][$key],
                                'error' => $_FILES['gallery_files']['error'][$key]
                            ];
                            
                            $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
                            $allowed_types = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES);
                            $file_type = in_array($file_ext, ALLOWED_IMAGE_TYPES) ? 'image' : 'video';
                            
                            $filename = uploadFile($file_info, GALLERY_DIR, $allowed_types);
                            $caption = sanitize($_POST['gallery_captions'][$key] ?? '');
                            
                            $db->query(
                                "INSERT INTO gallery (invitation_id, file_name, file_path, file_type, caption, sort_order) 
                                VALUES (?, ?, ?, ?, ?, ?)",
                                [$invitation_id, $file_info['name'], $filename, $file_type, $caption, $current_count + $uploaded_count]
                            );
                            
                            $uploaded_count++;
                        } catch (Exception $e) {
                            $errors[] = "Gagal upload {$file_info['name']}: " . $e->getMessage();
                        }
                    }
                }
                
                if ($uploaded_count > 0) {
                    $success = "{$uploaded_count} file berhasil diupload ke galeri!";
                    // Refresh gallery data
                    $gallery = $db->fetchAll(
                        "SELECT * FROM gallery WHERE invitation_id = ? ORDER BY sort_order ASC, created_at ASC",
                        [$invitation_id]
                    );
                }
            }
        } else {
            $errors[] = 'Pilih file untuk diupload';
        }
    }
    
    elseif ($action == 'delete_gallery') {
        $gallery_id = intval($_POST['gallery_id']);
        $gallery_item = $db->fetch(
            "SELECT * FROM gallery WHERE id = ? AND invitation_id = ?",
            [$gallery_id, $invitation_id]
        );
        
        if ($gallery_item) {
            // Delete file
            $file_path = GALLERY_DIR . $gallery_item['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete record
            $db->query("DELETE FROM gallery WHERE id = ?", [$gallery_id]);
            $success = 'File galeri berhasil dihapus!';
            
            // Refresh gallery data
            $gallery = $db->fetchAll(
                "SELECT * FROM gallery WHERE invitation_id = ? ORDER BY sort_order ASC, created_at ASC",
                [$invitation_id]
            );
        }
    }
    
    elseif ($action == 'add_gift') {
        $account_type = $_POST['account_type'] ?? '';
        $account_name = sanitize($_POST['account_name'] ?? '');
        $account_number = sanitize($_POST['account_number'] ?? '');
        
        if (empty($account_type) || empty($account_name) || empty($account_number)) {
            $errors[] = 'Semua field gift account harus diisi';
        } else {
            try {
                $db->query(
                    "INSERT INTO digital_gifts (invitation_id, account_type, account_name, account_number, is_active) 
                    VALUES (?, ?, ?, ?, 1)",
                    [$invitation_id, $account_type, $account_name, $account_number]
                );
                
                $success = 'Akun gift berhasil ditambahkan!';
                
                // Refresh gifts data
                $gifts = $db->fetchAll(
                    "SELECT * FROM digital_gifts WHERE invitation_id = ? ORDER BY created_at ASC",
                    [$invitation_id]
                );
            } catch (Exception $e) {
                $errors[] = 'Gagal menambah akun gift: ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action == 'delete_gift') {
        $gift_id = intval($_POST['gift_id']);
        $db->query("DELETE FROM digital_gifts WHERE id = ? AND invitation_id = ?", [$gift_id, $invitation_id]);
        $success = 'Akun gift berhasil dihapus!';
        
        // Refresh gifts data
        $gifts = $db->fetchAll(
            "SELECT * FROM digital_gifts WHERE invitation_id = ? ORDER BY created_at ASC",
            [$invitation_id]
        );
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Undangan - <?= htmlspecialchars($invitation['title']) ?></title>
    
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
            
            <div class="flex items-center space-x-4">
                <a href="/dashboard.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>Dashboard
                </a>
                <a href="/invitation/<?= htmlspecialchars($invitation['slug']) ?>" 
                   target="_blank"
                   class="text-blue-600 hover:text-blue-700">
                    <i class="fas fa-external-link-alt mr-2"></i>Preview
                </a>
                <div class="text-sm">
                    <span class="text-gray-600">Editing: </span>
                    <span class="font-medium"><?= htmlspecialchars($invitation['title']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Edit Undangan</h1>
                <p class="text-gray-600">Kelola dan update detail undangan Anda</p>
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

            <!-- Tabs -->
            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button onclick="showTab('basic')" id="tab-basic" 
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            Informasi Dasar
                        </button>
                        <button onclick="showTab('gallery')" id="tab-gallery" 
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-images mr-2"></i>
                            Galeri (<?= count($gallery) ?>)
                        </button>
                        <button onclick="showTab('gifts')" id="tab-gifts" 
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-gift mr-2"></i>
                            Digital Gift (<?= count($gifts) ?>)
                        </button>
                        <button onclick="showTab('analytics')" id="tab-analytics" 
                                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Statistik
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab Contents -->
            
            <!-- Basic Information Tab -->
            <div id="content-basic" class="tab-content">
                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="action" value="update">
                    
                    <!-- Basic Information -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Informasi Dasar</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Judul Undangan *
                                </label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       value="<?= htmlspecialchars($invitation['title']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>

                            <!-- Names -->
                            <div>
                                <label for="groom_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Mempelai Pria *
                                </label>
                                <input type="text" 
                                       id="groom_name" 
                                       name="groom_name" 
                                       value="<?= htmlspecialchars($invitation['groom_name']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>

                            <div>
                                <label for="bride_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Mempelai Wanita *
                                </label>
                                <input type="text" 
                                       id="bride_name" 
                                       name="bride_name" 
                                       value="<?= htmlspecialchars($invitation['bride_name']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>

                            <!-- Date & Time -->
                            <div>
                                <label for="wedding_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tanggal Pernikahan *
                                </label>
                                <input type="date" 
                                       id="wedding_date" 
                                       name="wedding_date" 
                                       value="<?= htmlspecialchars($invitation['wedding_date']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>

                            <div>
                                <label for="wedding_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Waktu Pernikahan *
                                </label>
                                <input type="time" 
                                       id="wedding_time" 
                                       name="wedding_time" 
                                       value="<?= htmlspecialchars($invitation['wedding_time']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Venue Information -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Informasi Venue</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="venue_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nama Tempat *
                                </label>
                                <input type="text" 
                                       id="venue_name" 
                                       name="venue_name" 
                                       value="<?= htmlspecialchars($invitation['venue_name']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                       required>
                            </div>

                            <div>
                                <label for="venue_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    Alamat Lengkap
                                </label>
                                <textarea id="venue_address" 
                                          name="venue_address" 
                                          rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"><?= htmlspecialchars($invitation['venue_address']) ?></textarea>
                            </div>

                            <div>
                                <label for="venue_maps_link" class="block text-sm font-medium text-gray-700 mb-2">
                                    Link Google Maps
                                </label>
                                <input type="url" 
                                       id="venue_maps_link" 
                                       name="venue_maps_link" 
                                       value="<?= htmlspecialchars($invitation['venue_maps_link']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            </div>
                        </div>
                    </div>

                    <!-- Theme Selection -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Pilih Tema</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($themes as $theme): ?>
                            <div class="relative">
                                <input type="radio" 
                                       id="theme_<?= $theme['id'] ?>" 
                                       name="theme_id" 
                                       value="<?= $theme['id'] ?>"
                                       class="sr-only peer"
                                       <?= ($invitation['theme_id'] == $theme['id']) ? 'checked' : '' ?>
                                       <?= ($theme['is_premium'] && !hasPermission('premium')) ? 'disabled' : '' ?>>
                                
                                <label for="theme_<?= $theme['id'] ?>" 
                                       class="block relative rounded-lg border-2 border-gray-200 cursor-pointer hover:border-pink-300 peer-checked:border-pink-500 peer-disabled:cursor-not-allowed peer-disabled:opacity-50">
                                    <div class="aspect-[4/3] bg-gray-100 rounded-t-lg overflow-hidden">
                                        <img src="<?= htmlspecialchars($theme['preview_image'] ?: '/assets/themes/default-preview.jpg') ?>" 
                                             alt="<?= htmlspecialchars($theme['name']) ?>"
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="p-4">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium text-gray-900"><?= htmlspecialchars($theme['name']) ?></h4>
                                            <?php if ($theme['is_premium']): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-crown mr-1"></i>
                                                    Premium
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Gratis
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Check mark for selected theme -->
                                    <div class="absolute top-2 right-2 w-6 h-6 bg-pink-500 rounded-full items-center justify-center text-white hidden peer-checked:flex">
                                        <i class="fas fa-check text-xs"></i>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Detail Tambahan</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label for="story" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cerita Cinta (Opsional)
                                </label>
                                <textarea id="story" 
                                          name="story" 
                                          rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"><?= htmlspecialchars($invitation['story']) ?></textarea>
                            </div>

                            <div>
                                <label for="live_streaming_link" class="block text-sm font-medium text-gray-700 mb-2">
                                    Link Live Streaming (Opsional)
                                </label>
                                <input type="url" 
                                       id="live_streaming_link" 
                                       name="live_streaming_link" 
                                       value="<?= htmlspecialchars($invitation['live_streaming_link']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cover Image
                                    </label>
                                    <?php if ($invitation['cover_image']): ?>
                                        <div class="mb-3">
                                            <img src="<?= UPLOAD_DIR . 'covers/' . $invitation['cover_image'] ?>" 
                                                 alt="Current cover" 
                                                 class="w-32 h-20 object-cover rounded-lg">
                                            <p class="text-xs text-gray-500 mt-1">Cover saat ini</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" 
                                           id="cover_image" 
                                           name="cover_image" 
                                           accept=".jpg,.jpeg,.png,.gif"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                                </div>

                                <div>
                                    <label for="background_music" class="block text-sm font-medium text-gray-700 mb-2">
                                        Musik Latar
                                    </label>
                                    <?php if ($invitation['background_music']): ?>
                                        <div class="mb-3">
                                            <audio controls class="w-full">
                                                <source src="<?= MUSIC_DIR . $invitation['background_music'] ?>" type="audio/mpeg">
                                            </audio>
                                            <p class="text-xs text-gray-500 mt-1">Musik saat ini</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" 
                                           id="background_music" 
                                           name="background_music" 
                                           accept=".mp3,.wav,.ogg"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a href="/dashboard.php" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kembali ke Dashboard
                        </a>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700">
                            <i class="fas fa-save mr-2"></i>
                            Update Undangan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Gallery Tab -->
            <div id="content-gallery" class="tab-content hidden">
                <div class="space-y-8">
                    <!-- Upload Gallery -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Galeri Foto & Video</h3>
                            <span class="text-sm text-gray-500">
                                <?= count($gallery) ?> / <?= ($user['subscription_plan'] == 'free') ? FREE_GALLERY_LIMIT : '∞' ?> files
                            </span>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="upload_gallery">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload Foto/Video
                                </label>
                                <input type="file" 
                                       name="gallery_files[]" 
                                       multiple 
                                       accept="image/*,video/*"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                                <p class="mt-1 text-sm text-gray-500">Pilih beberapa file sekaligus (JPG, PNG, GIF, MP4, AVI)</p>
                            </div>
                            
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                                <i class="fas fa-upload mr-2"></i>
                                Upload ke Galeri
                            </button>
                        </form>
                    </div>
                    
                    <!-- Gallery Items -->
                    <?php if (!empty($gallery)): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">File Galeri</h3>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($gallery as $item): ?>
                            <div class="relative group">
                                <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                                    <?php if ($item['file_type'] == 'image'): ?>
                                        <img src="<?= GALLERY_DIR . $item['file_path'] ?>" 
                                             alt="<?= htmlspecialchars($item['caption']) ?>"
                                             class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <video class="w-full h-full object-cover" muted>
                                            <source src="<?= GALLERY_DIR . $item['file_path'] ?>" type="video/mp4">
                                        </video>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <i class="fas fa-play text-white text-2xl bg-black bg-opacity-50 rounded-full p-3"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Delete Button -->
                                <form method="POST" class="absolute top-2 right-2 hidden group-hover:block">
                                    <input type="hidden" name="action" value="delete_gallery">
                                    <input type="hidden" name="gallery_id" value="<?= $item['id'] ?>">
                                    <button type="submit" 
                                            onclick="return confirm('Yakin ingin menghapus file ini?')"
                                            class="w-8 h-8 bg-red-500 text-white rounded-full hover:bg-red-600 flex items-center justify-center">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                                
                                <!-- Caption -->
                                <?php if ($item['caption']): ?>
                                <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($item['caption']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Galeri</h3>
                        <p class="text-gray-500">Upload foto dan video indah untuk melengkapi undangan Anda</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Digital Gifts Tab -->
            <div id="content-gifts" class="tab-content hidden">
                <div class="space-y-8">
                    <!-- Add Gift Account -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Tambah Akun Digital Gift</h3>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_gift">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipe Akun *
                                    </label>
                                    <select id="account_type" 
                                            name="account_type" 
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                            required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="e-wallet">E-Wallet</option>
                                        <option value="crypto">Cryptocurrency</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nama Akun *
                                    </label>
                                    <input type="text" 
                                           id="account_name" 
                                           name="account_name" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                           placeholder="John Doe / OVO / BTC Address"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nomor Akun *
                                    </label>
                                    <input type="text" 
                                           id="account_number" 
                                           name="account_number" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"
                                           placeholder="1234567890"
                                           required>
                                </div>
                            </div>
                            
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Akun Gift
                            </button>
                        </form>
                    </div>
                    
                    <!-- Gift Accounts List -->
                    <?php if (!empty($gifts)): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Akun Digital Gift</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($gifts as $gift): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <?php if ($gift['account_type'] == 'bank'): ?>
                                            <i class="fas fa-university text-blue-500 text-lg mr-3"></i>
                                        <?php elseif ($gift['account_type'] == 'e-wallet'): ?>
                                            <i class="fas fa-mobile-alt text-green-500 text-lg mr-3"></i>
                                        <?php else: ?>
                                            <i class="fab fa-bitcoin text-orange-500 text-lg mr-3"></i>
                                        <?php endif; ?>
                                        <span class="font-medium text-gray-900"><?= ucfirst($gift['account_type']) ?></span>
                                    </div>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_gift">
                                        <input type="hidden" name="gift_id" value="<?= $gift['id'] ?>">
                                        <button type="submit" 
                                                onclick="return confirm('Yakin ingin menghapus akun gift ini?')"
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <p class="font-medium text-gray-700"><?= htmlspecialchars($gift['account_name']) ?></p>
                                <p class="text-gray-600 font-mono"><?= htmlspecialchars($gift['account_number']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <i class="fas fa-gift text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Akun Gift</h3>
                        <p class="text-gray-500">Tambahkan akun bank atau e-wallet untuk menerima kado digital</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="content-analytics" class="tab-content hidden">
                <?php
                // Get analytics data
                $analytics = [
                    'total_views' => rand(50, 500), // Placeholder - implement actual view tracking
                    'total_rsvp' => $db->fetch("SELECT COUNT(*) as count FROM rsvp_responses WHERE invitation_id = ?", [$invitation_id])['count'] ?? 0,
                    'confirmed_yes' => $db->fetch("SELECT COUNT(*) as count FROM rsvp_responses WHERE invitation_id = ? AND attendance = 'yes'", [$invitation_id])['count'] ?? 0,
                    'total_messages' => $db->fetch("SELECT COUNT(*) as count FROM guest_messages WHERE invitation_id = ?", [$invitation_id])['count'] ?? 0,
                    'total_guests' => $db->fetch("SELECT COUNT(*) as count FROM guests WHERE invitation_id = ?", [$invitation_id])['count'] ?? 0
                ];
                ?>
                
                <div class="space-y-8">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-eye text-white"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500">Total Views</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= $analytics['total_views'] ?></dd>
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
                                        <dt class="text-sm font-medium text-gray-500">RSVP Responses</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= $analytics['total_rsvp'] ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-user-check text-white"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500">Confirmed Attendance</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= $analytics['confirmed_yes'] ?></dd>
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
                                        <dt class="text-sm font-medium text-gray-500">Guest Messages</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= $analytics['total_messages'] ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-pink-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500">Total Guests</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= $analytics['total_guests'] ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-images text-white"></i>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500">Gallery Items</dt>
                                        <dd class="text-lg font-medium text-gray-900"><?= count($gallery) ?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Quick Actions</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <a href="/guests.php?invitation=<?= $invitation_id ?>" 
                               class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <i class="fas fa-users text-blue-600 text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Kelola Tamu</p>
                                    <p class="text-sm text-gray-500">Add & manage guests</p>
                                </div>
                            </a>

                            <a href="/rsvp.php?invitation=<?= $invitation_id ?>" 
                               class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                <i class="fas fa-clipboard-check text-green-600 text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Lihat RSVP</p>
                                    <p class="text-sm text-gray-500">View responses</p>
                                </div>
                            </a>

                            <a href="/invitation/<?= htmlspecialchars($invitation['slug']) ?>" 
                               target="_blank"
                               class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                <i class="fas fa-external-link-alt text-purple-600 text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Preview Undangan</p>
                                    <p class="text-sm text-gray-500">See live invitation</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Sharing Links -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Share Invitation</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Invitation URL
                                </label>
                                <div class="flex">
                                    <input type="text" 
                                           id="invitation-url"
                                           value="<?= SITE_URL ?>/invitation/<?= htmlspecialchars($invitation['slug']) ?>"
                                           class="flex-1 rounded-l-md border-gray-300 shadow-sm"
                                           readonly>
                                    <button onclick="copyToClipboard('invitation-url')" 
                                            class="px-4 py-2 bg-pink-600 text-white rounded-r-md hover:bg-pink-700">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex space-x-3">
                                <a href="https://wa.me/?text=<?= urlencode('Undangan Pernikahan ' . $invitation['groom_name'] . ' & ' . $invitation['bride_name'] . ': ' . SITE_URL . '/invitation/' . $invitation['slug']) ?>" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <i class="fab fa-whatsapp mr-2"></i>
                                    Share via WhatsApp
                                </a>

                                <button onclick="shareViaEmail()" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-envelope mr-2"></i>
                                    Share via Email
                                </button>
                            </div>
                        </div>
                    </div>
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
        
        // Initialize first tab
        showTab('basic');
        
        // Copy to clipboard function
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');
            alert('URL copied to clipboard!');
        }
        
        // Share via email
        function shareViaEmail() {
            const subject = encodeURIComponent('Undangan Pernikahan <?= $invitation["groom_name"] ?> & <?= $invitation["bride_name"] ?>');
            const body = encodeURIComponent('Kami mengundang Anda untuk hadir di pernikahan kami. Klik link berikut untuk melihat undangan: <?= SITE_URL ?>/invitation/<?= $invitation["slug"] ?>');
            window.open(`mailto:?subject=${subject}&body=${body}`);
        }
        
        // File size validation
        document.querySelectorAll('input[type="file"]').forEach(function(input) {
            input.addEventListener('change', function(e) {
                const files = e.target.files;
                for (let file of files) {
                    if (file.size > 10 * 1024 * 1024) { // 10MB
                        alert('File ' + file.name + ' terlalu besar. Maksimal 10MB.');
                        this.value = '';
                        break;
                    }
                }
            });
        });
    </script>
</body>
</html>
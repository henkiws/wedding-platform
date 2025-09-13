<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$errors = [];
$success = '';

// Get available themes
$themes = $db->fetchAll("SELECT * FROM themes ORDER BY is_premium ASC, name ASC");

// Check user limits
$user_invitations_count = $db->fetch(
    "SELECT COUNT(*) as count FROM invitations WHERE user_id = ?", 
    [$user['id']]
)['count'];

$can_create = true;
$limit_message = '';

if ($user['subscription_plan'] == 'free') {
    if ($user_invitations_count >= 1) {
        $can_create = false;
        $limit_message = 'Paket gratis hanya bisa membuat 1 undangan. Upgrade ke premium untuk unlimited undangan.';
    }
} elseif ($user['subscription_plan'] == 'premium') {
    if ($user_invitations_count >= 1) {
        $can_create = false;
        $limit_message = 'Paket premium bisa membuat 1 undangan. Upgrade ke business untuk 2 undangan.';
    }
} elseif ($user['subscription_plan'] == 'business') {
    if ($user_invitations_count >= 2) {
        $can_create = false;
        $limit_message = 'Paket business maksimal 2 undangan. Hubungi support untuk lebih banyak.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_create) {
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
    if (empty($title)) {
        $errors[] = 'Judul undangan harus diisi';
    }
    
    if (empty($groom_name)) {
        $errors[] = 'Nama mempelai pria harus diisi';
    }
    
    if (empty($bride_name)) {
        $errors[] = 'Nama mempelai wanita harus diisi';
    }
    
    if (empty($wedding_date)) {
        $errors[] = 'Tanggal pernikahan harus diisi';
    }
    
    if (empty($wedding_time)) {
        $errors[] = 'Waktu pernikahan harus diisi';
    }
    
    if (empty($venue_name)) {
        $errors[] = 'Nama tempat harus diisi';
    }
    
    // Check theme permission
    $selected_theme = $db->fetch("SELECT * FROM themes WHERE id = ?", [$theme_id]);
    if ($selected_theme && $selected_theme['is_premium'] && !hasPermission('premium')) {
        $errors[] = 'Tema premium hanya untuk pengguna premium/business';
    }
    
    // Generate unique slug
    if (empty($errors)) {
        $base_slug = generateSlug($groom_name . '-' . $bride_name);
        $slug = $base_slug;
        $counter = 1;
        
        while ($db->fetch("SELECT id FROM invitations WHERE slug = ?", [$slug])) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
    }
    
    // Handle cover image upload
    $cover_image = '';
    if (!empty($_FILES['cover_image']['tmp_name'])) {
        try {
            if (!is_dir(UPLOAD_DIR . 'covers/')) {
                mkdir(UPLOAD_DIR . 'covers/', 0755, true);
            }
            $cover_image = uploadFile($_FILES['cover_image'], UPLOAD_DIR . 'covers/', ALLOWED_IMAGE_TYPES);
        } catch (Exception $e) {
            $errors[] = 'Gagal upload cover image: ' . $e->getMessage();
        }
    }
    
    // Handle background music upload
    $background_music = '';
    if (!empty($_FILES['background_music']['tmp_name'])) {
        try {
            if (!is_dir(MUSIC_DIR)) {
                mkdir(MUSIC_DIR, 0755, true);
            }
            $background_music = uploadFile($_FILES['background_music'], MUSIC_DIR, ALLOWED_AUDIO_TYPES);
        } catch (Exception $e) {
            $errors[] = 'Gagal upload musik: ' . $e->getMessage();
        }
    }
    
    // Create invitation
    if (empty($errors)) {
        try {
            $db->query(
                "INSERT INTO invitations (user_id, title, groom_name, bride_name, wedding_date, wedding_time, 
                venue_name, venue_address, venue_maps_link, theme_id, background_music, cover_image, 
                story, live_streaming_link, slug) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $user['id'], $title, $groom_name, $bride_name, $wedding_date, $wedding_time,
                    $venue_name, $venue_address, $venue_maps_link, $theme_id, $background_music,
                    $cover_image, $story, $live_streaming_link, $slug
                ]
            );
            
            $invitation_id = $db->lastInsertId();
            $success = 'Undangan berhasil dibuat!';
            
            // Redirect to edit page after successful creation
            header('Location: /edit-invitation.php?id=' . $invitation_id);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal membuat undangan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Undangan Baru - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-gray-200 shadow-sm">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="/" class="flex items-center space-x-3">
                <i class="fas fa-heart text-2xl text-pink-500"></i>
                <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800"><?= SITE_NAME ?></span>
            </a>
            
            <div class="flex items-center space-x-4">
                <a href="/dashboard.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <div class="text-sm">
                    <span class="text-gray-600">Welcome, </span>
                    <span class="font-medium"><?= htmlspecialchars($user['full_name']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Buat Undangan Baru</h1>
                <p class="text-gray-600">Isi detail undangan pernikahan Anda dengan lengkap</p>
            </div>

            <?php if (!$can_create): ?>
            <div class="mb-6 p-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 border border-yellow-200" role="alert">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="font-medium">Batas Undangan Tercapai</span>
                </div>
                <p><?= $limit_message ?></p>
                <div class="mt-3">
                    <a href="/subscription.php" class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg hover:bg-yellow-200">
                        <i class="fas fa-crown mr-2"></i>
                        Upgrade Sekarang
                    </a>
                </div>
            </div>
            <?php endif; ?>

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

            <!-- Form -->
            <?php if ($can_create): ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-8">
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
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="Wedding Invitation - John & Jane"
                                   required>
                        </div>

                        <!-- Groom Name -->
                        <div>
                            <label for="groom_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Mempelai Pria *
                            </label>
                            <input type="text" 
                                   id="groom_name" 
                                   name="groom_name" 
                                   value="<?= htmlspecialchars($_POST['groom_name'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="John Doe"
                                   required>
                        </div>

                        <!-- Bride Name -->
                        <div>
                            <label for="bride_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Mempelai Wanita *
                            </label>
                            <input type="text" 
                                   id="bride_name" 
                                   name="bride_name" 
                                   value="<?= htmlspecialchars($_POST['bride_name'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="Jane Smith"
                                   required>
                        </div>

                        <!-- Wedding Date -->
                        <div>
                            <label for="wedding_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Pernikahan *
                            </label>
                            <input type="date" 
                                   id="wedding_date" 
                                   name="wedding_date" 
                                   value="<?= htmlspecialchars($_POST['wedding_date'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   required>
                        </div>

                        <!-- Wedding Time -->
                        <div>
                            <label for="wedding_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Waktu Pernikahan *
                            </label>
                            <input type="time" 
                                   id="wedding_time" 
                                   name="wedding_time" 
                                   value="<?= htmlspecialchars($_POST['wedding_time'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Venue Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Informasi Venue</h3>
                    
                    <div class="space-y-6">
                        <!-- Venue Name -->
                        <div>
                            <label for="venue_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Tempat *
                            </label>
                            <input type="text" 
                                   id="venue_name" 
                                   name="venue_name" 
                                   value="<?= htmlspecialchars($_POST['venue_name'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="Grand Ballroom Hotel ABC"
                                   required>
                        </div>

                        <!-- Venue Address -->
                        <div>
                            <label for="venue_address" class="block text-sm font-medium text-gray-700 mb-2">
                                Alamat Lengkap
                            </label>
                            <textarea id="venue_address" 
                                      name="venue_address" 
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                      placeholder="Jl. Contoh No. 123, Jakarta Selatan, DKI Jakarta"><?= htmlspecialchars($_POST['venue_address'] ?? '') ?></textarea>
                        </div>

                        <!-- Maps Link -->
                        <div>
                            <label for="venue_maps_link" class="block text-sm font-medium text-gray-700 mb-2">
                                Link Google Maps
                            </label>
                            <input type="url" 
                                   id="venue_maps_link" 
                                   name="venue_maps_link" 
                                   value="<?= htmlspecialchars($_POST['venue_maps_link'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="https://maps.google.com/...">
                            <p class="mt-1 text-sm text-gray-500">Tamu bisa langsung membuka lokasi di Google Maps</p>
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
                                   <?= (isset($_POST['theme_id']) && $_POST['theme_id'] == $theme['id']) ? 'checked' : '' ?>
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
                                    
                                    <?php if ($theme['is_premium'] && !hasPermission('premium')): ?>
                                        <p class="mt-2 text-xs text-gray-500">Upgrade ke premium untuk menggunakan tema ini</p>
                                    <?php endif; ?>
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
                        <!-- Love Story -->
                        <div>
                            <label for="story" class="block text-sm font-medium text-gray-700 mb-2">
                                Cerita Cinta (Opsional)
                            </label>
                            <textarea id="story" 
                                      name="story" 
                                      rows="4"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                      placeholder="Ceritakan bagaimana kalian bertemu dan jatuh cinta..."><?= htmlspecialchars($_POST['story'] ?? '') ?></textarea>
                        </div>

                        <!-- Live Streaming -->
                        <div>
                            <label for="live_streaming_link" class="block text-sm font-medium text-gray-700 mb-2">
                                Link Live Streaming (Opsional)
                            </label>
                            <input type="url" 
                                   id="live_streaming_link" 
                                   name="live_streaming_link" 
                                   value="<?= htmlspecialchars($_POST['live_streaming_link'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500" 
                                   placeholder="https://youtube.com/watch?v=...">
                            <p class="mt-1 text-sm text-gray-500">Tamu yang tidak bisa hadir bisa menyaksikan secara online</p>
                        </div>

                        <!-- Cover Image -->
                        <div>
                            <label for="cover_image" class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Image (Opsional)
                            </label>
                            <input type="file" 
                                   id="cover_image" 
                                   name="cover_image" 
                                   accept=".jpg,.jpeg,.png,.gif"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                            <p class="mt-1 text-sm text-gray-500">Foto utama untuk undangan (JPG, PNG, GIF, maks. 5MB)</p>
                        </div>

                        <!-- Background Music -->
                        <div>
                            <label for="background_music" class="block text-sm font-medium text-gray-700 mb-2">
                                Musik Latar (Opsional)
                            </label>
                            <input type="file" 
                                   id="background_music" 
                                   name="background_music" 
                                   accept=".mp3,.wav,.ogg"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Musik yang akan diputar saat undangan dibuka (MP3, WAV, OGG, maks. 5MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-between">
                    <a href="/dashboard.php" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                    
                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                            <i class="fas fa-save mr-2"></i>
                            Buat Undangan
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        // Auto-generate title from names
        document.getElementById('groom_name').addEventListener('input', updateTitle);
        document.getElementById('bride_name').addEventListener('input', updateTitle);

        function updateTitle() {
            const groomName = document.getElementById('groom_name').value.trim();
            const brideName = document.getElementById('bride_name').value.trim();
            const titleField = document.getElementById('title');
            
            if (groomName && brideName && !titleField.value) {
                titleField.value = `Wedding Invitation - ${groomName} & ${brideName}`;
            }
        }

        // File size validation
        document.querySelectorAll('input[type="file"]').forEach(function(input) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.size > 5 * 1024 * 1024) { // 5MB
                    alert('File terlalu besar. Maksimal 5MB.');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>
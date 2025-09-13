<?php
require_once 'config.php';

// Get invitation slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$db = new Database();

// Get invitation details with theme
$invitation = $db->fetch(
    "SELECT i.*, t.name as theme_name, t.css_file as theme_css, u.full_name as creator_name
     FROM invitations i 
     LEFT JOIN themes t ON i.theme_id = t.id 
     LEFT JOIN users u ON i.user_id = u.id
     WHERE i.slug = ? AND i.is_active = 1",
    [$slug]
);

if (!$invitation) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get gallery images
$gallery = $db->fetchAll(
    "SELECT * FROM gallery WHERE invitation_id = ? ORDER BY sort_order ASC, created_at ASC",
    [$invitation['id']]
);

// Get guest messages
$messages = $db->fetchAll(
    "SELECT * FROM guest_messages WHERE invitation_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 20",
    [$invitation['id']]
);

// Get digital gifts
$gifts = $db->fetchAll(
    "SELECT * FROM digital_gifts WHERE invitation_id = ? AND is_active = 1",
    [$invitation['id']]
);

// Handle RSVP submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'rsvp') {
        $guest_name = sanitize($_POST['guest_name'] ?? '');
        $attendance = $_POST['attendance'] ?? '';
        $guest_count = intval($_POST['guest_count'] ?? 1);
        $message = sanitize($_POST['message'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (!empty($guest_name) && in_array($attendance, ['yes', 'no', 'maybe'])) {
            try {
                $db->query(
                    "INSERT INTO rsvp_responses (invitation_id, guest_name, attendance, guest_count, message, phone) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$invitation['id'], $guest_name, $attendance, $guest_count, $message, $phone]
                );
                $rsvp_success = true;
            } catch (Exception $e) {
                $rsvp_error = 'Gagal mengirim RSVP. Silakan coba lagi.';
            }
        } else {
            $rsvp_error = 'Mohon isi nama dan konfirmasi kehadiran.';
        }
    }
    
    if ($action == 'message') {
        $sender_name = sanitize($_POST['sender_name'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        
        if (!empty($sender_name) && !empty($message)) {
            try {
                $db->query(
                    "INSERT INTO guest_messages (invitation_id, sender_name, message) VALUES (?, ?, ?)",
                    [$invitation['id'], $sender_name, $message]
                );
                $message_success = true;
            } catch (Exception $e) {
                $message_error = 'Gagal mengirim ucapan. Silakan coba lagi.';
            }
        } else {
            $message_error = 'Mohon isi nama dan ucapan.';
        }
    }
}

// Format date for display
$wedding_date_formatted = formatDate($invitation['wedding_date'], 'l, d F Y');
$wedding_time_formatted = date('H:i', strtotime($invitation['wedding_time']));

// Count days until wedding
$today = new DateTime();
$wedding_date = new DateTime($invitation['wedding_date']);
$days_until = $today->diff($wedding_date)->days;
$is_past = $today > $wedding_date;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($invitation['title']) ?></title>
    <meta name="description" content="Undangan Pernikahan <?= htmlspecialchars($invitation['groom_name']) ?> & <?= htmlspecialchars($invitation['bride_name']) ?> - <?= formatDate($invitation['wedding_date'], 'd F Y') ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($invitation['title']) ?>">
    <meta property="og:description" content="Undangan Pernikahan <?= htmlspecialchars($invitation['groom_name']) ?> & <?= htmlspecialchars($invitation['bride_name']) ?>">
    <meta property="og:image" content="<?= $invitation['cover_image'] ? SITE_URL . '/uploads/covers/' . $invitation['cover_image'] : SITE_URL . '/assets/images/default-cover.jpg' ?>">
    <meta property="og:url" content="<?= SITE_URL ?>/invitation/<?= $slug ?>">
    <meta property="og:type" content="website">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Theme CSS -->
    <?php if (!empty($invitation['theme_css'])): ?>
    <link href="<?= THEME_DIR . $invitation['theme_css'] ?>" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        .font-script { font-family: 'Dancing Script', cursive; }
        .font-sans { font-family: 'Poppins', sans-serif; }
        
        .parallax {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .heart-beat {
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .gradient-text {
            background: linear-gradient(45deg, #ec4899, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="font-sans">
    <!-- Audio Player (Hidden) -->
    <?php if (!empty($invitation['background_music'])): ?>
    <audio id="backgroundMusic" loop>
        <source src="<?= MUSIC_DIR . $invitation['background_music'] ?>" type="audio/mpeg">
    </audio>
    <?php endif; ?>

    <!-- Music Control Button -->
    <?php if (!empty($invitation['background_music'])): ?>
    <div class="fixed top-4 right-4 z-50">
        <button id="musicToggle" class="w-12 h-12 bg-pink-500 rounded-full flex items-center justify-center text-white shadow-lg hover:bg-pink-600 transition-colors">
            <i class="fas fa-music" id="musicIcon"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center relative parallax"
             style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?= $invitation['cover_image'] ? '/uploads/covers/' . $invitation['cover_image'] : '/assets/images/hero-bg.jpg' ?>');">
        
        <!-- Floating Hearts -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating absolute top-20 left-10 text-pink-300 text-2xl opacity-50">
                <i class="fas fa-heart"></i>
            </div>
            <div class="floating absolute top-40 right-20 text-pink-400 text-xl opacity-60" style="animation-delay: 0.5s;">
                <i class="fas fa-heart"></i>
            </div>
            <div class="floating absolute bottom-40 left-20 text-pink-200 text-3xl opacity-40" style="animation-delay: 1s;">
                <i class="fas fa-heart"></i>
            </div>
            <div class="floating absolute bottom-20 right-10 text-pink-300 text-xl opacity-50" style="animation-delay: 1.5s;">
                <i class="fas fa-heart"></i>
            </div>
        </div>
        
        <div class="text-center text-white px-4 max-w-4xl mx-auto fade-in">
            <div class="mb-8">
                <h3 class="text-lg md:text-xl mb-4 font-light tracking-wider">The Wedding of</h3>
                <h1 class="font-script text-5xl md:text-7xl lg:text-8xl mb-4 gradient-text">
                    <?= htmlspecialchars($invitation['groom_name']) ?><br>&<br><?= htmlspecialchars($invitation['bride_name']) ?>
                </h1>
                <div class="heart-beat text-4xl text-pink-400 mb-6">
                    <i class="fas fa-heart"></i>
                </div>
                <p class="text-xl md:text-2xl mb-2 font-light"><?= $wedding_date_formatted ?></p>
                <p class="text-lg md:text-xl font-light"><?= htmlspecialchars($invitation['venue_name']) ?></p>
            </div>
            
            <!-- Countdown -->
            <?php if (!$is_past): ?>
            <div class="glass rounded-lg p-6 mb-8">
                <h4 class="text-lg mb-4">Countdown to Our Special Day</h4>
                <div id="countdown" class="grid grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-2xl md:text-3xl font-bold" id="days"><?= $days_until ?></div>
                        <div class="text-sm">Days</div>
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-bold" id="hours">00</div>
                        <div class="text-sm">Hours</div>
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-bold" id="minutes">00</div>
                        <div class="text-sm">Minutes</div>
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-bold" id="seconds">00</div>
                        <div class="text-sm">Seconds</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#details" class="bg-pink-600 hover:bg-pink-700 text-white px-8 py-3 rounded-full font-medium transition-colors">
                    <i class="fas fa-calendar mr-2"></i>
                    View Details
                </a>
                <a href="#rsvp" class="bg-transparent border-2 border-white hover:bg-white hover:text-gray-800 text-white px-8 py-3 rounded-full font-medium transition-colors">
                    <i class="fas fa-reply mr-2"></i>
                    RSVP
                </a>
            </div>
        </div>
    </section>

    <!-- Event Details -->
    <section id="details" class="py-16 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Event Details</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Date & Time -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center fade-in">
                    <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-calendar-alt text-2xl text-pink-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">When</h3>
                    <p class="text-lg text-gray-600 mb-2"><?= $wedding_date_formatted ?></p>
                    <p class="text-lg text-gray-600"><?= $wedding_time_formatted ?> WIB</p>
                </div>
                
                <!-- Venue -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center fade-in">
                    <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-map-marker-alt text-2xl text-pink-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">Where</h3>
                    <p class="text-lg text-gray-600 mb-2 font-medium"><?= htmlspecialchars($invitation['venue_name']) ?></p>
                    <?php if (!empty($invitation['venue_address'])): ?>
                    <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars($invitation['venue_address'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($invitation['venue_maps_link'])): ?>
                    <a href="<?= htmlspecialchars($invitation['venue_maps_link']) ?>" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fab fa-google mr-2"></i>
                        Open in Maps
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Love Story -->
    <?php if (!empty($invitation['story'])): ?>
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Our Love Story</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto"></div>
            </div>
            
            <div class="bg-pink-50 rounded-lg p-8 md:p-12 fade-in">
                <div class="text-center">
                    <div class="text-6xl text-pink-300 mb-4">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="text-lg md:text-xl text-gray-700 leading-relaxed italic">
                        <?= nl2br(htmlspecialchars($invitation['story'])) ?>
                    </p>
                    <div class="text-6xl text-pink-300 mt-4 rotate-180">
                        <i class="fas fa-quote-left"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Gallery -->
    <?php if (!empty($gallery)): ?>
    <section class="py-16 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Gallery</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto"></div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach (array_slice($gallery, 0, 12) as $index => $item): ?>
                <div class="fade-in cursor-pointer hover:opacity-90 transition-opacity" 
                     onclick="openLightbox(<?= $index ?>)">
                    <?php if ($item['file_type'] == 'image'): ?>
                    <img src="<?= GALLERY_DIR . $item['file_path'] ?>" 
                         alt="<?= htmlspecialchars($item['caption'] ?: 'Gallery Image') ?>"
                         class="w-full h-48 object-cover rounded-lg shadow-md">
                    <?php else: ?>
                    <div class="relative w-full h-48 bg-gray-200 rounded-lg shadow-md flex items-center justify-center">
                        <i class="fas fa-play text-4xl text-gray-600"></i>
                        <video class="absolute inset-0 w-full h-full object-cover rounded-lg" muted>
                            <source src="<?= GALLERY_DIR . $item['file_path'] ?>" type="video/mp4">
                        </video>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- RSVP Section -->
    <section id="rsvp" class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">RSVP</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-600">Please confirm your attendance</p>
            </div>
            
            <?php if (isset($rsvp_success)): ?>
            <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
                <p class="text-green-800 font-medium">Thank you for your RSVP! We look forward to celebrating with you.</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($rsvp_error)): ?>
            <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg text-center">
                <p class="text-red-800"><?= htmlspecialchars($rsvp_error) ?></p>
            </div>
            <?php endif; ?>
            
            <div class="bg-pink-50 rounded-lg p-8 fade-in">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="rsvp">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="guest_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Your Name *
                            </label>
                            <input type="text" 
                                   id="guest_name" 
                                   name="guest_name" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500"
                                   required>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            Will you attend? *
                        </label>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="attendance" value="yes" class="text-pink-600" required>
                                <span class="ml-2 text-green-700 font-medium">
                                    <i class="fas fa-check mr-1"></i>
                                    Yes, I'll be there
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="attendance" value="no" class="text-pink-600" required>
                                <span class="ml-2 text-red-700 font-medium">
                                    <i class="fas fa-times mr-1"></i>
                                    Sorry, can't make it
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="attendance" value="maybe" class="text-pink-600" required>
                                <span class="ml-2 text-yellow-700 font-medium">
                                    <i class="fas fa-question mr-1"></i>
                                    Not sure yet
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="guest_count" class="block text-sm font-medium text-gray-700 mb-2">
                                Number of Guests
                            </label>
                            <select id="guest_count" 
                                    name="guest_count" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                                <option value="1">1 Person</option>
                                <option value="2">2 People</option>
                                <option value="3">3 People</option>
                                <option value="4">4 People</option>
                                <option value="5">5+ People</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Message (Optional)
                        </label>
                        <textarea id="message" 
                                  name="message" 
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500"
                                  placeholder="Any special requests or messages for us..."></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" 
                                class="px-8 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send RSVP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Wishes Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Send Your Wishes</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-600">Share your congratulations and best wishes</p>
            </div>
            
            <?php if (isset($message_success)): ?>
            <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                <i class="fas fa-heart text-green-600 text-2xl mb-2"></i>
                <p class="text-green-800 font-medium">Thank you for your beautiful wishes!</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($message_error)): ?>
            <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-lg text-center">
                <p class="text-red-800"><?= htmlspecialchars($message_error) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Send Message Form -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8 fade-in">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="message">
                    
                    <div>
                        <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Your Name *
                        </label>
                        <input type="text" 
                               id="sender_name" 
                               name="sender_name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500"
                               required>
                    </div>
                    
                    <div>
                        <label for="wish_message" class="block text-sm font-medium text-gray-700 mb-2">
                            Your Message *
                        </label>
                        <textarea id="wish_message" 
                                  name="message" 
                                  rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500"
                                  placeholder="Write your congratulations and best wishes here..."
                                  required></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" 
                                class="px-8 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium">
                            <i class="fas fa-heart mr-2"></i>
                            Send Wishes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Display Messages -->
            <?php if (!empty($messages)): ?>
            <div class="space-y-4">
                <?php foreach ($messages as $msg): ?>
                <div class="bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($msg['sender_name']) ?></h4>
                            <p class="text-gray-700 mt-2"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                        </div>
                        <div class="text-pink-500 text-xl ml-4">
                            <i class="fas fa-heart"></i>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        <?= formatDate($msg['created_at'], 'd M Y H:i') ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Digital Gifts -->
    <?php if (!empty($gifts)): ?>
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-12 fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Digital Envelope</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-600">Send your love and blessings</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($gifts as $gift): ?>
                <div class="bg-gradient-to-br from-pink-50 to-purple-50 rounded-lg p-6 text-center shadow-lg fade-in">
                    <div class="w-16 h-16 bg-gradient-to-r from-pink-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <?php if ($gift['account_type'] == 'bank'): ?>
                            <i class="fas fa-university text-white text-xl"></i>
                        <?php elseif ($gift['account_type'] == 'e-wallet'): ?>
                            <i class="fas fa-mobile-alt text-white text-xl"></i>
                        <?php else: ?>
                            <i class="fab fa-bitcoin text-white text-xl"></i>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-xl font-semibold text-gray-800 mb-2"><?= ucfirst($gift['account_type']) ?></h3>
                    <p class="text-gray-700 font-medium mb-2"><?= htmlspecialchars($gift['account_name']) ?></p>
                    <p class="text-gray-600 mb-4 font-mono text-lg"><?= htmlspecialchars($gift['account_number']) ?></p>
                    
                    <?php if (!empty($gift['qr_code_image'])): ?>
                    <div class="mb-4">
                        <img src="<?= UPLOAD_DIR . 'qrcodes/' . $gift['qr_code_image'] ?>" 
                             alt="QR Code" 
                             class="w-32 h-32 mx-auto border rounded-lg">
                    </div>
                    <?php endif; ?>
                    
                    <button onclick="copyToClipboard('<?= htmlspecialchars($gift['account_number']) ?>')" 
                            class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i>
                        Copy Number
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Live Stream -->
    <?php if (!empty($invitation['live_streaming_link'])): ?>
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="fade-in">
                <h2 class="font-script text-4xl md:text-5xl text-gray-800 mb-4">Live Streaming</h2>
                <div class="w-24 h-1 bg-pink-500 mx-auto mb-6"></div>
                <p class="text-lg text-gray-600 mb-8">Can't make it? Join us virtually!</p>
                
                <a href="<?= htmlspecialchars($invitation['live_streaming_link']) ?>" 
                   target="_blank"
                   class="inline-flex items-center px-8 py-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium text-lg">
                    <i class="fas fa-video mr-3"></i>
                    Watch Live Stream
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="mb-4">
                <i class="fas fa-heart text-pink-500 text-2xl"></i>
            </div>
            <p class="mb-2">Thank you for being part of our special day</p>
            <p class="text-gray-400 text-sm">
                Made with <i class="fas fa-heart text-pink-500 mx-1"></i> by <?= SITE_NAME ?>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Music control
        <?php if (!empty($invitation['background_music'])): ?>
        const music = document.getElementById('backgroundMusic');
        const musicToggle = document.getElementById('musicToggle');
        const musicIcon = document.getElementById('musicIcon');
        let isPlaying = false;

        musicToggle.addEventListener('click', function() {
            if (isPlaying) {
                music.pause();
                musicIcon.className = 'fas fa-music';
                isPlaying = false;
            } else {
                music.play();
                musicIcon.className = 'fas fa-pause';
                isPlaying = true;
            }
        });

        // Auto play music (with user interaction)
        document.addEventListener('click', function() {
            if (!isPlaying && music.paused) {
                music.play().then(() => {
                    musicIcon.className = 'fas fa-pause';
                    isPlaying = true;
                }).catch(() => {
                    // Auto-play failed, user needs to click the music button
                });
            }
        }, { once: true });
        <?php endif; ?>

        // Countdown timer
        <?php if (!$is_past): ?>
        const weddingDate = new Date('<?= $invitation['wedding_date'] ?> <?= $invitation['wedding_time'] ?>').getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = weddingDate - now;

            if (distance > 0) {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('days').textContent = days;
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            }
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();
        <?php endif; ?>

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Account number copied to clipboard!');
            });
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Gallery lightbox (simple implementation)
        function openLightbox(index) {
            // Simple alert for now - you can implement a proper lightbox
            alert('Gallery lightbox would open image ' + (index + 1));
        }
    </script>
</body>
</html>
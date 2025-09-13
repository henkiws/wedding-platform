<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

// Get success details from URL
$order_id = $_GET['order_id'] ?? '';
$plan = $_GET['plan'] ?? '';

// Validate parameters
if (empty($order_id) || empty($plan)) {
    header('Location: /dashboard.php');
    exit;
}

// Define plan names
$plan_names = [
    'premium' => 'Premium',
    'business' => 'Business'
];

$plan_name = $plan_names[$plan] ?? 'Unknown';

// Refresh user data to get updated subscription
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .success-animation {
            animation: successPulse 2s ease-in-out infinite;
        }
        
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #f59e0b;
            animation: confetti-fall 3s linear infinite;
        }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        .confetti:nth-child(1) { left: 10%; animation-delay: 0s; background: #ef4444; }
        .confetti:nth-child(2) { left: 20%; animation-delay: 0.2s; background: #3b82f6; }
        .confetti:nth-child(3) { left: 30%; animation-delay: 0.4s; background: #10b981; }
        .confetti:nth-child(4) { left: 40%; animation-delay: 0.6s; background: #f59e0b; }
        .confetti:nth-child(5) { left: 50%; animation-delay: 0.8s; background: #ec4899; }
        .confetti:nth-child(6) { left: 60%; animation-delay: 1s; background: #8b5cf6; }
        .confetti:nth-child(7) { left: 70%; animation-delay: 1.2s; background: #06b6d4; }
        .confetti:nth-child(8) { left: 80%; animation-delay: 1.4s; background: #f97316; }
        .confetti:nth-child(9) { left: 90%; animation-delay: 1.6s; background: #84cc16; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 via-white to-pink-50 min-h-screen relative overflow-hidden">
    <!-- Confetti Animation -->
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>
    <div class="confetti"></div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md w-full space-y-8">
            <!-- Success Icon -->
            <div class="text-center">
                <div class="mx-auto h-24 w-24 bg-green-100 rounded-full flex items-center justify-center success-animation">
                    <i class="fas fa-check text-green-600 text-4xl"></i>
                </div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Payment Successful!</h2>
                <p class="mt-2 text-lg text-gray-600">
                    Selamat! Subscription Anda telah berhasil diupgrade
                </p>
            </div>

            <!-- Success Details -->
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                        <?php 
                        switch($plan) {
                            case 'premium': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'business': echo 'bg-purple-100 text-purple-800'; break;
                            default: echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?php if ($plan == 'premium'): ?>
                            <i class="fas fa-crown mr-2"></i>
                        <?php elseif ($plan == 'business'): ?>
                            <i class="fas fa-briefcase mr-2"></i>
                        <?php endif; ?>
                        <?= $plan_name ?> Plan Activated
                    </div>
                </div>

                <!-- Transaction Details -->
                <div class="space-y-4 mb-8">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Order ID:</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($order_id) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Plan:</span>
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($plan_name) ?></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Status:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i>
                            Active
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Valid Until:</span>
                        <span class="font-medium text-gray-900">
                            <?php if ($plan == 'premium' || $plan == 'business'): ?>
                                <i class="fas fa-infinity mr-1 text-green-600"></i>
                                Selamanya
                            <?php else: ?>
                                <?= formatDate($user['subscription_expires']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-600">Payment Date:</span>
                        <span class="font-medium text-gray-900"><?= date('d M Y, H:i') ?> WIB</span>
                    </div>
                </div>

                <!-- What's Next Section -->
                <div class="bg-blue-50 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-blue-900 mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>
                        What's Next?
                    </h3>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                            <span>Akses ke semua tema premium tersedia sekarang</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                            <span>Upload unlimited foto & video ke galeri</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                            <span>Undang unlimited tamu tanpa batasan</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                            <span>Dapatkan analytics lengkap untuk undangan</span>
                        </li>
                    </ul>
                </div>

                <!-- Email Confirmation Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-envelope text-yellow-600 mr-3 mt-1"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">Email Confirmation Sent</p>
                            <p class="text-sm text-yellow-700 mt-1">
                                Kami telah mengirim konfirmasi pembayaran ke <strong><?= htmlspecialchars($user['email']) ?></strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <a href="/dashboard.php" 
                       class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 transition-colors">
                        <i class="fas fa-home mr-2"></i>
                        Go to Dashboard
                    </a>
                    
                    <a href="/create-invitation.php" 
                       class="w-full flex justify-center py-2 px-4 border border-pink-600 rounded-md shadow-sm text-sm font-medium text-pink-600 bg-white hover:bg-pink-50 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Create New Invitation
                    </a>
                </div>
            </div>

            <!-- Receipt Download -->
            <div class="text-center">
                <button onclick="downloadReceipt()" 
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800">
                    <i class="fas fa-download mr-2"></i>
                    Download Receipt (PDF)
                </button>
            </div>

            <!-- Support Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-900 mb-4 text-center">
                    <i class="fas fa-headset mr-2"></i>
                    Need Help?
                </h3>
                
                <div class="grid grid-cols-1 gap-4">
                    <div class="text-center">
                        <div class="flex justify-center space-x-6">
                            <a href="mailto:support@<?= strtolower(SITE_NAME) ?>.com" 
                               class="flex items-center text-blue-600 hover:text-blue-700">
                                <i class="fas fa-envelope mr-2"></i>
                                <span class="text-sm">Email Support</span>
                            </a>
                            
                            <a href="https://wa.me/6281234567890?text=Hi,%20I%20need%20help%20with%20order%20<?= urlencode($order_id) ?>" 
                               target="_blank"
                               class="flex items-center text-green-600 hover:text-green-700">
                                <i class="fab fa-whatsapp mr-2"></i>
                                <span class="text-sm">WhatsApp</span>
                            </a>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-3">
                            Our support team is available 24/7 to help you
                        </p>
                    </div>
                </div>
            </div>

            <!-- Social Share -->
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-4">Share your success!</p>
                <div class="flex justify-center space-x-4">
                    <button onclick="shareToFacebook()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fab fa-facebook-f mr-2"></i>
                        Facebook
                    </button>
                    <button onclick="shareToTwitter()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 text-sm">
                        <i class="fab fa-twitter mr-2"></i>
                        Twitter
                    </button>
                    <button onclick="shareToWhatsApp()" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                        <i class="fab fa-whatsapp mr-2"></i>
                        WhatsApp
                    </button>
                </div>
            </div>

            <!-- Back to Site -->
            <div class="text-center pt-8">
                <a href="/" class="text-pink-600 hover:text-pink-700 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to <?= SITE_NAME ?>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide confetti after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.confetti').forEach(el => {
                el.style.display = 'none';
            });
        }, 5000);

        // Download receipt function
        function downloadReceipt() {
            // In a real implementation, this would generate and download a PDF receipt
            alert('Receipt download feature would be implemented here. In production, this would generate a PDF receipt.');
        }

        // Social sharing functions
        function shareToFacebook() {
            const url = encodeURIComponent(window.location.origin);
            const text = encodeURIComponent(`Just upgraded to ${<?= json_encode($plan_name) ?>} plan on <?= SITE_NAME ?>! Creating beautiful wedding invitations has never been easier. 💕`);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank', 'width=600,height=400');
        }

        function shareToTwitter() {
            const url = encodeURIComponent(window.location.origin);
            const text = encodeURIComponent(`Just upgraded to ${<?= json_encode($plan_name) ?>} plan on <?= SITE_NAME ?>! Creating beautiful wedding invitations 💕`);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
        }

        function shareToWhatsApp() {
            const text = encodeURIComponent(`Just upgraded my wedding invitation plan! Check out <?= SITE_NAME ?> for beautiful digital invitations: ${window.location.origin}`);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        // Celebration sound (optional)
        function playCelebrationSound() {
            // You can add a celebration sound effect here
            // const audio = new Audio('/sounds/celebration.mp3');
            // audio.play().catch(e => console.log('Audio play failed:', e));
        }

        // Auto-redirect after some time (optional)
        // setTimeout(() => {
        //     if (confirm('Redirect to dashboard?')) {
        //         window.location.href = '/dashboard.php';
        //     }
        // }, 10000); // 10 seconds

        // Prevent back button to payment page
        history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
            history.pushState(null, null, window.location.href);
        });

        // Track successful conversion (for analytics)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'purchase', {
                transaction_id: '<?= htmlspecialchars($order_id) ?>',
                value: <?= $plan == 'premium' ? 99000 : ($plan == 'business' ? 199000 : 0) ?>,
                currency: 'IDR',
                items: [{
                    item_id: '<?= $plan ?>',
                    item_name: '<?= $plan_name ?> Plan',
                    category: 'subscription',
                    quantity: 1,
                    price: <?= $plan == 'premium' ? 99000 : ($plan == 'business' ? 199000 : 0) ?>
                }]
            });
        }
    </script>
</body>
</html>
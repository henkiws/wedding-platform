<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

$errors = [];
$success = '';

// Define subscription plans
$plans = [
    'free' => [
        'name' => 'Gratis',
        'price' => 0,
        'duration' => '1 Bulan',
        'features' => [
            '1 Undangan Standar',
            'Aktif 1 Bulan',
            'Kuota Tamu Terbatas (50)',
            'Galeri Foto & Video Terbatas (10)',
            'Musik Latar',
            'Ucapan & Do\'a',
            'Pilihan Tema Terbatas',
            'Kado Cashless',
            'Link Streaming',
            'Layar Penerima Tamu'
        ],
        'limitations' => [
            'Watermark Wevitation',
            'Support via Email',
            'Template Terbatas'
        ]
    ],
    'premium' => [
        'name' => 'Premium',
        'price' => 99000,
        'duration' => 'Selamanya',
        'popular' => true,
        'features' => [
            '1 Undangan Premium',
            'Aktif Selamanya',
            'Unlimited Kuota Tamu',
            'Unlimited Galeri Foto & Video',
            'Musik Latar',
            'Ucapan & Do\'a',
            'Bebas Pilih Tema Premium',
            'Kado Cashless',
            'Link Streaming',
            'Layar Penerima Tamu',
            'Story Instagram',
            'Analytics Lengkap',
            'Custom Domain',
            'No Watermark'
        ],
        'limitations' => []
    ],
    'business' => [
        'name' => 'Business',
        'price' => 199000,
        'duration' => 'Selamanya',
        'features' => [
            '2 Undangan Premium',
            'Aktif Selamanya',
            'Unlimited Kuota Tamu',
            'Unlimited Galeri Foto & Video',
            'Musik Latar',
            'Ucapan & Do\'a',
            'Bebas Pilih Tema Premium',
            'Kado Cashless',
            'Link Streaming',
            'Layar Penerima Tamu',
            'Story Instagram',
            'Analytics Lengkap',
            'Custom Domain',
            'No Watermark',
            'White Label Option',
            'Priority Support',
            'API Access',
            'Advanced Analytics'
        ],
        'limitations' => []
    ]
];

// Get user's current plan info
$current_plan = $user['subscription_plan'];
$subscription_expires = $user['subscription_expires'];

// Handle upgrade request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'upgrade') {
        $selected_plan = $_POST['plan'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        
        if (!array_key_exists($selected_plan, $plans)) {
            $errors[] = 'Paket tidak valid';
        } elseif ($selected_plan == 'free') {
            $errors[] = 'Tidak bisa upgrade ke paket gratis';
        } elseif (empty($payment_method)) {
            $errors[] = 'Pilih metode pembayaran';
        } else {
            // In a real implementation, this would integrate with payment gateway
            // For demo purposes, we'll simulate successful payment
            
            $plan_info = $plans[$selected_plan];
            
            // Generate order ID
            $order_id = 'WV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // For demo, we'll just redirect to a payment simulation
            $payment_url = "/payment-simulation.php?plan={$selected_plan}&order_id={$order_id}&amount={$plan_info['price']}";
            header("Location: {$payment_url}");
            exit;
        }
    }
    
    elseif ($action == 'cancel_subscription') {
        // In a real implementation, this would handle subscription cancellation
        $success = 'Permintaan pembatalan berlangganan telah diterima. Tim kami akan menghubungi Anda.';
    }
}

// Calculate days remaining for current subscription
$days_remaining = 0;
if ($subscription_expires) {
    $expires_date = new DateTime($subscription_expires);
    $today = new DateTime();
    $diff = $today->diff($expires_date);
    $days_remaining = $expires_date > $today ? $diff->days : 0;
}

// Get user's invitation count
$invitation_count = $db->fetch(
    "SELECT COUNT(*) as count FROM invitations WHERE user_id = ?", 
    [$user['id']]
)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - <?= SITE_NAME ?></title>
    
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Kelola Subscription</h1>
                <p class="text-xl text-gray-600">Upgrade paket Anda untuk fitur yang lebih lengkap</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 max-w-2xl mx-auto" role="alert">
                <div class="font-medium mb-2">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 max-w-2xl mx-auto" role="alert">
                <div class="font-medium mb-2">Berhasil!</div>
                <p><?= htmlspecialchars($success) ?></p>
            </div>
            <?php endif; ?>

            <!-- Current Plan Status -->
            <div class="max-w-4xl mx-auto mb-12">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Paket Saat Ini</h2>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                                    <?php 
                                    switch($current_plan) {
                                        case 'premium': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'business': echo 'bg-purple-100 text-purple-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php if ($current_plan == 'premium'): ?>
                                        <i class="fas fa-crown mr-2"></i>
                                    <?php elseif ($current_plan == 'business'): ?>
                                        <i class="fas fa-briefcase mr-2"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user mr-2"></i>
                                    <?php endif; ?>
                                    <?= $plans[$current_plan]['name'] ?>
                                </span>
                                
                                <?php if ($current_plan == 'free' && $days_remaining > 0): ?>
                                <span class="text-sm text-gray-600">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= $days_remaining ?> hari tersisa
                                </span>
                                <?php elseif ($current_plan != 'free'): ?>
                                <span class="text-sm text-green-600">
                                    <i class="fas fa-infinity mr-1"></i>
                                    Aktif Selamanya
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="text-sm text-gray-500 mb-1">Undangan Dibuat</div>
                            <div class="text-2xl font-bold text-gray-900"><?= $invitation_count ?></div>
                            <div class="text-xs text-gray-500">
                                <?php 
                                if ($current_plan == 'free') {
                                    echo 'dari 1 maksimal';
                                } elseif ($current_plan == 'premium') {
                                    echo 'dari 1 maksimal';
                                } else {
                                    echo 'dari 2 maksimal';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription expiration warning -->
                    <?php if ($current_plan == 'free' && $days_remaining <= 7 && $days_remaining > 0): ?>
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                            <div>
                                <p class="font-medium text-yellow-800">Paket gratis akan berakhir dalam <?= $days_remaining ?> hari</p>
                                <p class="text-yellow-700 text-sm">Upgrade sekarang untuk menjaga undangan tetap aktif</p>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($current_plan == 'free' && $days_remaining == 0): ?>
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-red-600 mr-3"></i>
                            <div>
                                <p class="font-medium text-red-800">Paket gratis telah berakhir</p>
                                <p class="text-red-700 text-sm">Undangan tidak lagi dapat diakses. Upgrade untuk mengaktifkan kembali.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pricing Plans -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-7xl mx-auto">
                <?php foreach ($plans as $plan_key => $plan): ?>
                <div class="relative bg-white rounded-lg shadow-lg overflow-hidden
                    <?= isset($plan['popular']) ? 'ring-2 ring-pink-500' : '' ?>
                    <?= $plan_key == $current_plan ? 'opacity-75' : '' ?>">
                    
                    <!-- Popular badge -->
                    <?php if (isset($plan['popular'])): ?>
                    <div class="absolute top-0 right-0 bg-pink-500 text-white px-3 py-1 text-xs font-bold rounded-bl-lg">
                        MOST POPULAR
                    </div>
                    <?php endif; ?>
                    
                    <!-- Current plan badge -->
                    <?php if ($plan_key == $current_plan): ?>
                    <div class="absolute top-0 left-0 bg-green-500 text-white px-3 py-1 text-xs font-bold rounded-br-lg">
                        PAKET AKTIF
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-8">
                        <!-- Plan header -->
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= $plan['name'] ?></h3>
                            <div class="mb-4">
                                <?php if ($plan['price'] > 0): ?>
                                <span class="text-4xl font-bold text-gray-900">Rp <?= number_format($plan['price'], 0, ',', '.') ?></span>
                                <span class="text-gray-600">/ <?= $plan['duration'] ?></span>
                                <?php else: ?>
                                <span class="text-4xl font-bold text-gray-900">Gratis</span>
                                <span class="text-gray-600">/ <?= $plan['duration'] ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($plan_key == 'premium'): ?>
                            <p class="text-gray-600">Cocok untuk sekali pemakaian</p>
                            <?php elseif ($plan_key == 'business'): ?>
                            <p class="text-gray-600">Untuk wedding organizer</p>
                            <?php else: ?>
                            <p class="text-gray-600">Coba gratis dulu</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Features list -->
                        <ul class="space-y-3 mb-8">
                            <?php foreach ($plan['features'] as $feature): ?>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-3 mt-1"></i>
                                <span class="text-gray-700"><?= $feature ?></span>
                            </li>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($plan['limitations'])): ?>
                            <?php foreach ($plan['limitations'] as $limitation): ?>
                            <li class="flex items-start">
                                <i class="fas fa-times text-red-400 mr-3 mt-1"></i>
                                <span class="text-gray-500"><?= $limitation ?></span>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <!-- Action button -->
                        <div class="text-center">
                            <?php if ($plan_key == $current_plan): ?>
                            <button class="w-full py-3 px-6 border-2 border-gray-300 rounded-lg font-medium text-gray-400 cursor-not-allowed">
                                <i class="fas fa-check mr-2"></i>
                                Paket Aktif
                            </button>
                            <?php elseif ($plan_key == 'free'): ?>
                            <a href="/register.php" 
                               class="w-full inline-block py-3 px-6 border-2 border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 text-center">
                                Daftar Gratis
                            </a>
                            <?php else: ?>
                            <button onclick="openUpgradeModal('<?= $plan_key ?>')" 
                                    class="w-full py-3 px-6 
                                    <?= isset($plan['popular']) ? 'bg-pink-600 hover:bg-pink-700 text-white' : 'bg-gray-900 hover:bg-gray-800 text-white' ?>
                                    rounded-lg font-medium transition-colors">
                                <i class="fas fa-crown mr-2"></i>
                                Upgrade Sekarang
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Features Comparison -->
            <div class="mt-16 max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Perbandingan Fitur Lengkap</h2>
                
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase">Fitur</th>
                                    <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase">Gratis</th>
                                    <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase">Premium</th>
                                    <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase">Business</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Jumlah Undangan</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">1</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">1</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">2</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Durasi Aktif</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">1 Bulan</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Selamanya</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Selamanya</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Kuota Tamu</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">50</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Unlimited</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Unlimited</td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Galeri Foto/Video</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">10 File</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Unlimited</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700">Unlimited</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Tema Premium</td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-times text-red-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-check text-green-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-check text-green-500"></i></td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Custom Domain</td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-times text-red-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-check text-green-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-check text-green-500"></i></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">White Label</td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-times text-red-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-times text-red-500"></i></td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-check text-green-500"></i></td>
                                </tr>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">Priority Support</td>
                                    <td class="px-6 py-4 text-center"><i class="fas fa-times text-red-500"></i></td>
                                    <td class="px-6 py-4 text-center">Email</td>
                                    <td class="px-6 py-4 text-center">Phone & Email</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-16 max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
                
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Apakah bisa downgrade dari premium ke gratis?</h3>
                        <p class="text-gray-600">Tidak, setelah upgrade ke premium atau business, akun tidak bisa di-downgrade. Namun undangan akan tetap aktif selamanya.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Apa yang terjadi jika paket gratis habis?</h3>
                        <p class="text-gray-600">Undangan akan tidak dapat diakses oleh tamu. Data tidak hilang dan dapat diaktifkan kembali dengan upgrade.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Apakah ada refund jika tidak puas?</h3>
                        <p class="text-gray-600">Kami menyediakan garansi 7 hari uang kembali untuk paket premium dan business jika tidak puas.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Bagaimana cara pembayaran?</h3>
                        <p class="text-gray-600">Kami menerima pembayaran melalui transfer bank, e-wallet (OVO, GoPay, Dana), dan kartu kredit.</p>
                    </div>
                </div>
            </div>

            <!-- Current plan cancellation -->
            <?php if ($current_plan != 'free'): ?>
            <div class="mt-12 max-w-2xl mx-auto">
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-red-800 mb-2">Batalkan Subscription?</h3>
                    <p class="text-red-700 text-sm mb-4">
                        Jika Anda ingin membatalkan subscription, undangan akan tetap aktif sampai periode berakhir.
                    </p>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="cancel_subscription">
                        <button type="submit" 
                                onclick="return confirm('Yakin ingin membatalkan subscription? Tindakan ini tidak dapat dibatalkan.')"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                            <i class="fas fa-times mr-2"></i>
                            Batalkan Subscription
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upgrade Modal -->
    <div id="upgrade-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeUpgradeModal()"></div>
            
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">Upgrade to Premium</h3>
                    <button onclick="closeUpgradeModal()" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="upgrade">
                    <input type="hidden" name="plan" id="selected-plan" value="">
                    
                    <div class="mb-6">
                        <div id="plan-summary" class="bg-gray-50 rounded-lg p-4">
                            <!-- Plan summary will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Pilih Metode Pembayaran
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="bank_transfer" class="mr-3">
                                <i class="fas fa-university mr-2 text-blue-600"></i>
                                <span>Transfer Bank</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="ovo" class="mr-3">
                                <i class="fas fa-mobile-alt mr-2 text-purple-600"></i>
                                <span>OVO</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="gopay" class="mr-3">
                                <i class="fas fa-mobile-alt mr-2 text-green-600"></i>
                                <span>GoPay</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="dana" class="mr-3">
                                <i class="fas fa-mobile-alt mr-2 text-blue-500"></i>
                                <span>DANA</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="credit_card" class="mr-3">
                                <i class="fas fa-credit-card mr-2 text-red-600"></i>
                                <span>Kartu Kredit</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeUpgradeModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 text-sm font-medium text-white bg-pink-600 rounded-md hover:bg-pink-700">
                            <i class="fas fa-credit-card mr-2"></i>
                            Lanjut Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        const plans = <?= json_encode($plans) ?>;
        
        function openUpgradeModal(planKey) {
            const plan = plans[planKey];
            document.getElementById('selected-plan').value = planKey;
            document.getElementById('modal-title').textContent = `Upgrade to ${plan.name}`;
            
            document.getElementById('plan-summary').innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-medium text-gray-900">${plan.name}</h4>
                    <span class="text-lg font-bold text-gray-900">Rp ${plan.price.toLocaleString('id-ID')}</span>
                </div>
                <p class="text-sm text-gray-600">${plan.duration} • ${plan.features.length} fitur</p>
            `;
            
            document.getElementById('upgrade-modal').classList.remove('hidden');
        }
        
        function closeUpgradeModal() {
            document.getElementById('upgrade-modal').classList.add('hidden');
        }
    </script>
</body>
</html>
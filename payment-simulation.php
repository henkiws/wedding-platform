<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = new Database();

// Get payment details from URL
$plan = $_GET['plan'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$amount = intval($_GET['amount'] ?? 0);

// Validate parameters
if (empty($plan) || empty($order_id) || $amount <= 0) {
    header('Location: /subscription.php');
    exit;
}

// Define plan names
$plan_names = [
    'premium' => 'Premium',
    'business' => 'Business'
];

$plan_name = $plan_names[$plan] ?? 'Unknown';

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'confirm_payment') {
        // Simulate payment processing
        sleep(2); // Simulate processing time
        
        try {
            // Update user subscription
            $expires_date = ($plan == 'premium' || $plan == 'business') ? '2099-12-31' : date('Y-m-d', strtotime('+30 days'));
            
            $db->query(
                "UPDATE users SET subscription_plan = ?, subscription_expires = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$plan, $expires_date, $user['id']]
            );
            
            // In a real implementation, you would:
            // 1. Log the transaction
            // 2. Send confirmation email
            // 3. Update payment records
            // 4. Integrate with actual payment gateway
            
            // Redirect to success page
            header("Location: /payment-success.php?order_id={$order_id}&plan={$plan}");
            exit;
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.';
        }
    }
    
    elseif ($action == 'cancel_payment') {
        header('Location: /subscription.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-pink-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-credit-card text-pink-600 text-xl"></i>
                </div>
                <h2 class="mt-4 text-3xl font-bold text-gray-900">Payment Gateway</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Demo pembayaran untuk upgrade subscription
                </p>
            </div>

            <!-- Payment Details -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <?php if (isset($error)): ?>
                <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                    <div class="font-medium mb-2">Payment Error</div>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <!-- Order Summary -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Order Summary</h3>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Order ID:</span>
                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order_id) ?></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Plan:</span>
                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($plan_name) ?></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">Customer:</span>
                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></span>
                        </div>
                        <hr class="my-3 border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">Total Amount:</span>
                            <span class="text-lg font-bold text-pink-600">Rp <?= number_format($amount, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Simulation Notice -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Demo Payment Gateway</h4>
                            <p class="mt-1 text-sm text-blue-700">
                                Ini adalah simulasi pembayaran untuk demo. Di implementasi nyata, Anda akan diarahkan ke payment gateway seperti Midtrans, Xendit, atau sejenisnya.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Methods</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-gray-50">
                            <i class="fas fa-university text-blue-600 mr-3"></i>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">Bank Transfer</p>
                                <p class="text-sm text-gray-500">BCA, BNI, BRI, Mandiri</p>
                            </div>
                            <span class="text-sm text-gray-400">Available</span>
                        </div>
                        
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-gray-50">
                            <i class="fas fa-mobile-alt text-purple-600 mr-3"></i>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">E-Wallet</p>
                                <p class="text-sm text-gray-500">OVO, GoPay, DANA, LinkAja</p>
                            </div>
                            <span class="text-sm text-gray-400">Available</span>
                        </div>
                        
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-gray-50">
                            <i class="fas fa-credit-card text-red-600 mr-3"></i>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">Credit/Debit Card</p>
                                <p class="text-sm text-gray-500">Visa, Mastercard</p>
                            </div>
                            <span class="text-sm text-gray-400">Available</span>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-shield-alt text-green-500 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">Secure Payment</p>
                            <p class="text-sm text-green-700">
                                Pembayaran dilindungi dengan enkripsi SSL 256-bit dan standar keamanan PCI DSS.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <form method="POST" id="payment-form">
                    <div class="space-y-4">
                        <button type="submit" 
                                name="action" 
                                value="confirm_payment"
                                id="pay-button"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-all">
                            <span id="button-text">
                                <i class="fas fa-credit-card mr-2"></i>
                                Bayar Sekarang - Rp <?= number_format($amount, 0, ',', '.') ?>
                            </span>
                            <span id="loading-text" class="hidden">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Processing Payment...
                            </span>
                        </button>
                        
                        <button type="submit" 
                                name="action" 
                                value="cancel_payment"
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                            <i class="fas fa-times mr-2"></i>
                            Batal Pembayaran
                        </button>
                    </div>
                </form>

                <!-- Terms Notice -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        Dengan melanjutkan pembayaran, Anda menyetujui 
                        <a href="/terms" class="text-pink-600 hover:text-pink-700">Syarat & Ketentuan</a>
                        dan 
                        <a href="/privacy" class="text-pink-600 hover:text-pink-700">Kebijakan Privasi</a>
                        kami.
                    </p>
                </div>
            </div>

            <!-- Support Info -->
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-2">Need help with payment?</p>
                <div class="flex justify-center space-x-4">
                    <a href="mailto:support@<?= strtolower(SITE_NAME) ?>.com" 
                       class="text-pink-600 hover:text-pink-700 text-sm">
                        <i class="fas fa-envelope mr-1"></i>
                        Email Support
                    </a>
                    <a href="https://wa.me/6281234567890" 
                       target="_blank"
                       class="text-green-600 hover:text-green-700 text-sm">
                        <i class="fab fa-whatsapp mr-1"></i>
                        WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Payment form handling
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            const submitButton = e.submitter;
            
            if (submitButton && submitButton.value === 'confirm_payment') {
                // Show loading state
                const buttonText = document.getElementById('button-text');
                const loadingText = document.getElementById('loading-text');
                const payButton = document.getElementById('pay-button');
                
                buttonText.classList.add('hidden');
                loadingText.classList.remove('hidden');
                payButton.disabled = true;
                payButton.classList.add('opacity-75', 'cursor-not-allowed');
                
                // In a real implementation, you would:
                // 1. Validate form data
                // 2. Send payment request to gateway
                // 3. Handle response
                // 4. Redirect or show error
            }
        });

        // Simulate payment gateway redirect (in real implementation)
        function simulatePaymentGateway() {
            // This would typically redirect to Midtrans, Xendit, etc.
            console.log('Redirecting to payment gateway...');
        }

        // Auto-focus and form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Add form validation if needed
            // Add payment method selection logic
            // Add real-time amount formatting
        });

        // Security: Prevent multiple submissions
        let submitted = false;
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            if (submitted) {
                e.preventDefault();
                return false;
            }
            
            if (e.submitter && e.submitter.value === 'confirm_payment') {
                submitted = true;
            }
        });

        // Session timeout warning
        let sessionTimeout = setTimeout(function() {
            alert('Sesi pembayaran akan berakhir dalam 5 menit. Silakan selesaikan pembayaran Anda.');
        }, 15 * 60 * 1000); // 15 minutes

        // Clear timeout if user interacts
        document.addEventListener('click', function() {
            clearTimeout(sessionTimeout);
        });
    </script>
</body>
</html>
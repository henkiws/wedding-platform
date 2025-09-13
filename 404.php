<?php
require_once 'config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <!-- 404 Icon -->
                <div class="mx-auto h-24 w-24 text-pink-500 mb-6">
                    <i class="fas fa-heart-broken text-8xl"></i>
                </div>
                
                <!-- 404 Title -->
                <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Oops! Undangan Tidak Ditemukan</h2>
                
                <!-- Description -->
                <div class="text-gray-600 space-y-2 mb-8">
                    <p>Maaf, undangan yang Anda cari tidak dapat ditemukan.</p>
                    <p>Kemungkinan penyebab:</p>
                    <ul class="text-sm text-left bg-gray-100 rounded-lg p-4 mt-4">
                        <li class="flex items-start mb-2">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2 mt-1"></i>
                            <span>Link undangan salah atau tidak lengkap</span>
                        </li>
                        <li class="flex items-start mb-2">
                            <i class="fas fa-eye-slash text-red-500 mr-2 mt-1"></i>
                            <span>Undangan sudah dihapus atau dinonaktifkan</span>
                        </li>
                        <li class="flex items-start mb-2">
                            <i class="fas fa-clock text-blue-500 mr-2 mt-1"></i>
                            <span>Undangan belum dipublikasikan</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-calendar-times text-purple-500 mr-2 mt-1"></i>
                            <span>Masa aktif undangan sudah berakhir</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="/" 
                           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 transition-colors">
                            <i class="fas fa-home mr-2"></i>
                            Kembali ke Beranda
                        </a>
                        <a href="/login.php" 
                           class="inline-flex items-center px-6 py-3 border border-pink-600 text-base font-medium rounded-md text-pink-600 bg-white hover:bg-pink-50 transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login
                        </a>
                    </div>
                    
                    <div class="text-center">
                        <button onclick="history.back()" 
                                class="text-gray-500 hover:text-gray-700 font-medium">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Kembali ke halaman sebelumnya
                        </button>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <div class="bg-blue-50 rounded-lg p-6">
                        <div class="flex items-center justify-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="fas fa-question-circle text-blue-500 text-2xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-blue-800">Butuh Bantuan?</h3>
                            </div>
                        </div>
                        
                        <div class="text-blue-700 text-sm space-y-2">
                            <p>Jika Anda yakin link undangan benar, silakan:</p>
                            <ul class="list-disc list-inside mt-2">
                                <li>Periksa kembali link yang diberikan</li>
                                <li>Hubungi pemilik undangan</li>
                                <li>Atau hubungi support kami</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4 flex justify-center space-x-4">
                            <a href="mailto:support@<?= strtolower(SITE_NAME) ?>.com" 
                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                <i class="fas fa-envelope mr-1"></i>
                                Email Support
                            </a>
                            <a href="https://wa.me/6281234567890" 
                               target="_blank"
                               class="text-green-600 hover:text-green-800 font-medium text-sm">
                                <i class="fab fa-whatsapp mr-1"></i>
                                WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Create Invitation CTA -->
                <div class="mt-8 bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Buat Undangan Sendiri?</h3>
                    <p class="text-pink-100 text-sm mb-4">
                        Buat undangan pernikahan digital yang indah dan modern dengan <?= SITE_NAME ?>
                    </p>
                    <a href="/register.php" 
                       class="inline-flex items-center px-4 py-2 bg-white text-pink-600 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                        <i class="fas fa-plus mr-2"></i>
                        Mulai Gratis
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fun Animation -->
    <script>
        // Add some subtle floating animation to the heart icon
        const heartIcon = document.querySelector('.fa-heart-broken');
        let floating = false;
        
        heartIcon.addEventListener('mouseenter', function() {
            if (!floating) {
                floating = true;
                this.style.animation = 'bounce 1s ease-in-out';
                setTimeout(() => {
                    this.style.animation = '';
                    floating = false;
                }, 1000);
            }
        });
        
        // Add bounce animation to CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
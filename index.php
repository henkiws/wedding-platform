<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - <?= SITE_DESCRIPTION ?></title>
    <meta name="description" content="Buat undangan pernikahan digital yang indah dan modern. Gratis, praktis, dan kekinian dengan berbagai fitur menarik.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-white border-gray-200 shadow-lg sticky top-0 z-50">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="/" class="flex items-center space-x-3">
                <i class="fas fa-heart text-2xl text-pink-500"></i>
                <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800"><?= SITE_NAME ?></span>
            </a>
            
            <div class="flex md:order-2 space-x-3">
                <?php if (isLoggedIn()): ?>
                    <a href="/dashboard.php" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Dashboard</a>
                    <a href="/logout.php" class="text-gray-500 hover:text-gray-700">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="text-gray-500 hover:text-gray-700 px-3 py-2">Login</a>
                    <a href="/register.php" class="text-white bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:outline-none focus:ring-pink-300 font-medium rounded-lg text-sm px-4 py-2 text-center">Daftar Sekarang</a>
                <?php endif; ?>
                
                <button data-collapse-toggle="navbar-cta" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                    </svg>
                </button>
            </div>
            
            <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-cta">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 md:flex-row md:mt-0 md:border-0 md:bg-white">
                    <li><a href="#home" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Beranda</a></li>
                    <li><a href="#features" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Fitur</a></li>
                    <li><a href="#themes" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Tema</a></li>
                    <li><a href="#pricing" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:hover:text-pink-600 md:p-0">Harga</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="gradient-bg hero-pattern">
        <div class="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16 lg:px-12">
            <h1 class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-white md:text-5xl lg:text-6xl">
                Platform Undangan<br>Pernikahan Digital
            </h1>
            <p class="mb-8 text-lg font-normal text-gray-300 lg:text-xl sm:px-16 xl:px-48">
                Lebih Praktis, hemat dan kekinian. Gratis undangan pernikahan berbasis web dengan fitur lengkap dan tema yang indah.
            </p>
            <div class="flex flex-col mb-8 lg:mb-16 space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4">
                <a href="/register.php" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:ring-pink-300">
                    Buat Undangan Sekarang
                    <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </a>
                <a href="#features" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 hover:text-gray-800">
                    <i class="fas fa-play mr-2"></i>
                    Lihat Demo
                </a>
            </div>
            
            <!-- Hero Image Carousel -->
            <div id="hero-carousel" class="relative w-full max-w-4xl mx-auto" data-carousel="slide">
                <div class="relative h-56 overflow-hidden rounded-lg md:h-96">
                    <div class="hidden duration-700 ease-in-out" data-carousel-item="active">
                        <img src="/assets/images/demo-1.jpg" class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="Template 1">
                    </div>
                    <div class="hidden duration-700 ease-in-out" data-carousel-item>
                        <img src="/assets/images/demo-2.jpg" class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="Template 2">
                    </div>
                    <div class="hidden duration-700 ease-in-out" data-carousel-item>
                        <img src="/assets/images/demo-3.jpg" class="absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="Template 3">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-8 bg-white lg:py-16">
        <div class="mx-auto max-w-screen-xl px-4">
            <div class="mx-auto max-w-screen-sm text-center mb-8 lg:mb-16">
                <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900">Fitur Lengkap</h2>
                <p class="font-light text-gray-500 lg:mb-16 sm:text-xl">Lebih Hemat & Fitur Sangat Lengkap untuk undangan pernikahan impian Anda</p>
            </div>
            
            <div class="grid mb-8 lg:mb-12 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-pink-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-infinity text-pink-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Aktif Selamanya</h3>
                    <p class="text-gray-500">Website undanganmu aktif tanpa ada batasan waktu.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-blue-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-palette text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Atur Tampilan Undangan</h3>
                    <p class="text-gray-500">Kamu bisa edit sendiri tampilan undanganmu.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-green-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-music text-green-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Musik Latar</h3>
                    <p class="text-gray-500">Integrasikan musik di undanganmu dengan mudah.</p>
                </div>
                
                <!-- Feature 4 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-purple-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-comments text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Ucapan & Doa</h3>
                    <p class="text-gray-500">Dapatkan ucapan & doa dari teman-temanmu.</p>
                </div>
                
                <!-- Feature 5 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-yellow-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-gift text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Kado Digital</h3>
                    <p class="text-gray-500">Terima Kado Cashless dari teman-temanmu.</p>
                </div>
                
                <!-- Feature 6 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-red-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-images text-red-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Galeri Foto & Video</h3>
                    <p class="text-gray-500">Tampilkan foto & video terbaik bersama pasanganmu.</p>
                </div>
                
                <!-- Feature 7 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-indigo-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-video text-indigo-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Live Streaming</h3>
                    <p class="text-gray-500">Bagikan link live streaming di undangan.</p>
                </div>
                
                <!-- Feature 8 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-teal-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fab fa-whatsapp text-teal-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Kirim WhatsApp</h3>
                    <p class="text-gray-500">Kirim undangan ke WA temanmu dengan mudah.</p>
                </div>
                
                <!-- Feature 9 -->
                <div class="bg-gray-50 rounded-lg p-8 text-center hover:shadow-lg transition-shadow">
                    <div class="flex justify-center items-center mb-4 w-10 h-10 rounded-full bg-orange-100 lg:h-12 lg:w-12 mx-auto">
                        <i class="fas fa-qrcode text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">QR Code</h3>
                    <p class="text-gray-500">Scanner QR Code untuk konfirmasi kehadiran tamu.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Themes Section -->
    <section id="themes" class="py-8 bg-gray-50 lg:py-16">
        <div class="mx-auto max-w-screen-xl px-4">
            <div class="mx-auto max-w-screen-sm text-center mb-8 lg:mb-16">
                <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900">Tema Indah</h2>
                <p class="font-light text-gray-500 lg:mb-16 sm:text-xl">Pilih dari berbagai tema undangan yang indah dan modern</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                    <img src="/assets/themes/classic/preview.jpg" class="w-full h-48 object-cover" alt="Classic Theme">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Classic Elegant</h3>
                        <p class="text-gray-600 text-sm mb-3">Tema klasik yang elegan dan timeless</p>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Gratis</span>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                    <img src="/assets/themes/modern/preview.jpg" class="w-full h-48 object-cover" alt="Modern Theme">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Modern Minimalist</h3>
                        <p class="text-gray-600 text-sm mb-3">Desain modern dan minimalis</p>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Gratis</span>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow">
                    <img src="/assets/themes/garden/preview.jpg" class="w-full h-48 object-cover" alt="Garden Theme">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Romantic Garden</h3>
                        <p class="text-gray-600 text-sm mb-3">Tema romantis dengan nuansa taman</p>
                        <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Premium</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-8 bg-white lg:py-16">
        <div class="mx-auto max-w-screen-xl px-4">
            <div class="mx-auto max-w-screen-sm text-center mb-8 lg:mb-16">
                <h2 class="mb-4 text-4xl tracking-tight font-extrabold text-gray-900">Paket Harga</h2>
                <p class="font-light text-gray-500 lg:mb-16 sm:text-xl">Pilih paket yang sesuai dengan kebutuhan Anda</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Free Plan -->
                <div class="bg-white p-6 mx-auto max-w-sm text-center text-gray-900 bg-white rounded-lg border border-gray-100 shadow xl:p-8">
                    <h3 class="mb-4 text-2xl font-semibold">Gratis</h3>
                    <p class="font-light text-gray-500 sm:text-lg">Cocok untuk mencoba platform kami</p>
                    <div class="flex justify-center items-baseline my-8">
                        <span class="mr-2 text-5xl font-extrabold">Rp 0</span>
                        <span class="text-gray-500">/selamanya</span>
                    </div>
                    <ul role="list" class="mb-8 space-y-4 text-left">
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>1 Undangan Standar</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Aktif 1 Bulan</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Kuota Tamu Terbatas (50)</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Galeri Foto Terbatas (10)</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Musik Latar</span>
                        </li>
                    </ul>
                    <a href="/register.php" class="text-white bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:ring-pink-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Mulai Gratis</a>
                </div>
                
                <!-- Premium Plan -->
                <div class="bg-white p-6 mx-auto max-w-sm text-center text-gray-900 bg-white rounded-lg border-2 border-pink-500 shadow xl:p-8">
                    <div class="bg-pink-500 text-white py-1 px-3 rounded-full text-sm mb-4">Most Popular</div>
                    <h3 class="mb-4 text-2xl font-semibold">Premium</h3>
                    <p class="font-light text-gray-500 sm:text-lg">Untuk undangan yang lebih istimewa</p>
                    <div class="flex justify-center items-baseline my-8">
                        <span class="mr-2 text-5xl font-extrabold">Rp 99K</span>
                        <span class="text-gray-500">/selamanya</span>
                    </div>
                    <ul role="list" class="mb-8 space-y-4 text-left">
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>1 Undangan Premium</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Aktif Selamanya</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Unlimited Kuota Tamu</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Unlimited Galeri</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Semua Tema Premium</span>
                        </li>
                    </ul>
                    <a href="/register.php" class="text-white bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:ring-pink-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Upgrade Premium</a>
                </div>
                
                <!-- Business Plan -->
                <div class="bg-white p-6 mx-auto max-w-sm text-center text-gray-900 bg-white rounded-lg border border-gray-100 shadow xl:p-8">
                    <h3 class="mb-4 text-2xl font-semibold">Business</h3>
                    <p class="font-light text-gray-500 sm:text-lg">Untuk wedding organizer</p>
                    <div class="flex justify-center items-baseline my-8">
                        <span class="mr-2 text-5xl font-extrabold">Rp 199K</span>
                        <span class="text-gray-500">/selamanya</span>
                    </div>
                    <ul role="list" class="mb-8 space-y-4 text-left">
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>2 Undangan Premium</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Aktif Selamanya</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Unlimited Semua Fitur</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>Priority Support</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-check text-green-500"></i>
                            <span>White Label Option</span>
                        </li>
                    </ul>
                    <a href="/register.php" class="text-white bg-gray-800 hover:bg-gray-900 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Upgrade Business</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900">
        <div class="mx-auto w-full max-w-screen-xl p-4 py-6 lg:py-8">
            <div class="md:flex md:justify-between">
                <div class="mb-6 md:mb-0">
                    <a href="/" class="flex items-center">
                        <i class="fas fa-heart text-2xl text-pink-500 mr-3"></i>
                        <span class="self-center text-2xl font-semibold whitespace-nowrap text-white"><?= SITE_NAME ?></span>
                    </a>
                    <p class="text-gray-400 mt-2">Platform undangan pernikahan digital<br>yang praktis dan modern.</p>
                </div>
                <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
                    <div>
                        <h2 class="mb-6 text-sm font-semibold text-gray-900 uppercase text-white">Produk</h2>
                        <ul class="text-gray-400 font-medium">
                            <li class="mb-4"><a href="#features" class="hover:underline">Fitur</a></li>
                            <li class="mb-4"><a href="#themes" class="hover:underline">Tema</a></li>
                            <li><a href="#pricing" class="hover:underline">Harga</a></li>
                        </ul>
                    </div>
                    <div>
                        <h2 class="mb-6 text-sm font-semibold text-gray-900 uppercase text-white">Support</h2>
                        <ul class="text-gray-400 font-medium">
                            <li class="mb-4"><a href="/help" class="hover:underline">Bantuan</a></li>
                            <li class="mb-4"><a href="/contact" class="hover:underline">Kontak</a></li>
                            <li><a href="/faq" class="hover:underline">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h2 class="mb-6 text-sm font-semibold text-gray-900 uppercase text-white">Legal</h2>
                        <ul class="text-gray-400 font-medium">
                            <li class="mb-4"><a href="/privacy" class="hover:underline">Privacy Policy</a></li>
                            <li><a href="/terms" class="hover:underline">Terms &amp; Conditions</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-200 sm:mx-auto border-gray-700 lg:my-8" />
            <div class="sm:flex sm:items-center sm:justify-between">
                <span class="text-sm text-gray-400 sm:text-center">© 2024 <?= SITE_NAME ?>. All Rights Reserved.</span>
                <div class="flex mt-4 sm:justify-center sm:mt-0">
                    <a href="#" class="text-gray-500 hover:text-white">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-white ms-5">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-white ms-5">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>
</body>
</html>
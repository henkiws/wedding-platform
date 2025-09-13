<?php
require_once 'config.php';

// Get current user if logged in
$user = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & FAQ - <?= SITE_NAME ?></title>
    <meta name="description" content="Bantuan dan Frequently Asked Questions untuk platform undangan pernikahan digital <?= SITE_NAME ?>">
    
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
                <?php if ($user): ?>
                    <a href="/dashboard.php" class="text-gray-500 hover:text-gray-700">Dashboard</a>
                    <a href="/logout.php" class="text-gray-500 hover:text-gray-700">Logout</a>
                <?php else: ?>
                    <a href="/login.php" class="text-gray-500 hover:text-gray-700">Login</a>
                    <a href="/register.php" class="text-white bg-pink-600 hover:bg-pink-700 px-4 py-2 rounded-lg">Daftar Gratis</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-question-circle text-pink-500 mr-3"></i>
                    Help Center
                </h1>
                <p class="text-xl text-gray-600">Temukan jawaban untuk pertanyaan Anda atau hubungi tim support kami</p>
            </div>

            <!-- Search Box -->
            <div class="mb-12">
                <div class="relative max-w-2xl mx-auto">
                    <input type="text" 
                           id="search-input"
                           placeholder="Cari bantuan, contoh: cara upload foto, mengubah tema..."
                           class="w-full px-4 py-3 pl-12 pr-4 text-lg border border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Quick Help Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <a href="#getting-started" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-play text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Getting Started</h3>
                        <p class="text-sm text-gray-600">Cara memulai dan membuat undangan pertama</p>
                    </div>
                </a>

                <a href="#customization" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-palette text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Customization</h3>
                        <p class="text-sm text-gray-600">Mengubah tema, upload foto, dan personalisasi</p>
                    </div>
                </a>

                <a href="#guest-management" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Guest Management</h3>
                        <p class="text-sm text-gray-600">Mengelola tamu dan RSVP</p>
                    </div>
                </a>

                <a href="#subscription" class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-crown text-yellow-600 text-xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Subscription</h3>
                        <p class="text-sm text-gray-600">Paket berlangganan dan billing</p>
                    </div>
                </a>
            </div>

            <!-- FAQ Sections -->
            <div class="space-y-8">
                <!-- Getting Started -->
                <section id="getting-started" class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-play text-blue-600 mr-3"></i>
                        Getting Started
                    </h2>

                    <div class="space-y-4">
                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara membuat undangan pertama saya?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p class="mb-4">Ikuti langkah-langkah berikut untuk membuat undangan pertama:</p>
                                    <ol class="list-decimal list-inside space-y-2">
                                        <li>Daftar akun gratis di <?= SITE_NAME ?></li>
                                        <li>Login ke dashboard Anda</li>
                                        <li>Klik tombol "Buat Undangan Baru"</li>
                                        <li>Isi informasi dasar seperti nama mempelai, tanggal, dan lokasi</li>
                                        <li>Pilih tema yang disukai</li>
                                        <li>Kustomisasi dengan foto dan musik (opsional)</li>
                                        <li>Preview dan publikasikan undangan</li>
                                    </ol>
                                    <p class="mt-4">
                                        <a href="<?= $user ? '/create-invitation.php' : '/register.php' ?>" class="text-pink-600 hover:text-pink-700 font-medium">
                                            <?= $user ? 'Buat Undangan Sekarang' : 'Daftar Gratis untuk Memulai' ?> →
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Apakah gratis untuk membuat undangan?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Ya! Anda bisa membuat 1 undangan gratis dengan fitur:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Aktif selama 1 bulan</li>
                                        <li>Maksimal 50 tamu</li>
                                        <li>10 foto di galeri</li>
                                        <li>Tema dasar</li>
                                        <li>RSVP dan ucapan tamu</li>
                                    </ul>
                                    <p class="mt-3">Untuk fitur unlimited, Anda bisa upgrade ke paket premium mulai Rp 99.000 sekali bayar.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara mengundang tamu?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Ada beberapa cara untuk mengundang tamu:</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li><strong>WhatsApp:</strong> Bagikan link undangan langsung via WA</li>
                                        <li><strong>Email:</strong> Kirim undangan via email</li>
                                        <li><strong>Social Media:</strong> Share di Instagram, Facebook, dll</li>
                                        <li><strong>QR Code:</strong> Print QR code untuk undangan fisik</li>
                                    </ol>
                                    <p class="mt-3">Link undangan Anda akan berbentuk: <?= SITE_URL ?>/invitation/nama-anda</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Customization -->
                <section id="customization" class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-palette text-purple-600 mr-3"></i>
                        Customization
                    </h2>

                    <div class="space-y-4">
                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara mengubah tema undangan?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Masuk ke dashboard dan pilih undangan yang ingin diedit</li>
                                        <li>Klik "Edit Undangan"</li>
                                        <li>Scroll ke bagian "Pilih Tema"</li>
                                        <li>Pilih tema yang diinginkan (tema premium memerlukan upgrade)</li>
                                        <li>Klik "Update Undangan" untuk menyimpan</li>
                                    </ol>
                                    <p class="mt-3 text-sm text-blue-600">💡 Tip: Preview undangan setelah mengubah tema untuk melihat hasilnya!</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara upload foto ke galeri?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Buka halaman edit undangan</li>
                                        <li>Pilih tab "Galeri"</li>
                                        <li>Klik "Upload Foto/Video"</li>
                                        <li>Pilih file dari komputer (JPG, PNG, GIF, MP4)</li>
                                        <li>Tambahkan caption jika diinginkan</li>
                                        <li>Klik "Upload ke Galeri"</li>
                                    </ol>
                                    <p class="mt-3"><strong>Batas upload:</strong></p>
                                    <ul class="list-disc list-inside mt-1 text-sm">
                                        <li>Paket gratis: 10 file, maksimal 5MB per file</li>
                                        <li>Paket premium: Unlimited file, maksimal 10MB per file</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bisakah menambahkan musik latar?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Ya! Anda bisa menambahkan musik latar dengan cara:</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li>Edit undangan → bagian "Detail Tambahan"</li>
                                        <li>Pilih file musik (MP3, WAV, OGG)</li>
                                        <li>Upload maksimal 5MB untuk gratis, 10MB untuk premium</li>
                                        <li>Musik akan otomatis play ketika tamu membuka undangan</li>
                                    </ol>
                                    <p class="mt-3 text-sm text-green-600">🎵 Rekomendasi: Gunakan lagu romantis atau instrumental dengan volume sedang</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Guest Management -->
                <section id="guest-management" class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-users text-green-600 mr-3"></i>
                        Guest Management
                    </h2>

                    <div class="space-y-4">
                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara menambah daftar tamu?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Ada 2 cara menambah tamu:</p>
                                    <div class="mt-3">
                                        <p><strong>1. Manual (satu per satu):</strong></p>
                                        <ul class="list-disc list-inside mt-1 ml-4">
                                            <li>Dashboard → Kelola Tamu</li>
                                            <li>Isi form "Tambah Tamu Baru"</li>
                                            <li>Klik "Tambah Tamu"</li>
                                        </ul>
                                    </div>
                                    <div class="mt-3">
                                        <p><strong>2. Import CSV (banyak sekaligus):</strong></p>
                                        <ul class="list-disc list-inside mt-1 ml-4">
                                            <li>Siapkan file CSV dengan format: Nama,Telepon,Email,Alamat,Kategori</li>
                                            <li>Klik "Import CSV"</li>
                                            <li>Upload file dan proses</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara melihat siapa saja yang sudah RSVP?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Masuk ke Dashboard</li>
                                        <li>Pilih menu "RSVP" di navigation</li>
                                        <li>Pilih undangan yang ingin dilihat</li>
                                        <li>Anda akan melihat statistik dan daftar lengkap RSVP</li>
                                    </ol>
                                    <p class="mt-3">Informasi yang tersedia:</p>
                                    <ul class="list-disc list-inside mt-1 text-sm">
                                        <li>Status kehadiran (Hadir/Tidak Hadir/Mungkin)</li>
                                        <li>Jumlah tamu yang dibawa</li>
                                        <li>Pesan dari tamu</li>
                                        <li>Kontak tamu</li>
                                        <li>Waktu RSVP</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bisakah mengirim reminder ke tamu yang belum RSVP?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Saat ini belum ada fitur otomatis untuk reminder, tapi Anda bisa:</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li>Lihat daftar tamu yang belum RSVP di halaman "Kelola Tamu"</li>
                                        <li>Gunakan tombol WhatsApp untuk mengirim pesan langsung</li>
                                        <li>Copy link undangan dan kirim manual via chat/email</li>
                                    </ol>
                                    <p class="mt-3 text-sm text-blue-600">💡 Fitur reminder otomatis sedang dalam pengembangan!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Subscription -->
                <section id="subscription" class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-crown text-yellow-600 mr-3"></i>
                        Subscription & Billing
                    </h2>

                    <div class="space-y-4">
                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Apa perbedaan paket gratis, premium, dan business?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="border-b">
                                                    <th class="text-left py-2">Fitur</th>
                                                    <th class="text-center py-2">Gratis</th>
                                                    <th class="text-center py-2">Premium</th>
                                                    <th class="text-center py-2">Business</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-sm">
                                                <tr class="border-b">
                                                    <td class="py-2">Jumlah undangan</td>
                                                    <td class="text-center">1</td>
                                                    <td class="text-center">1</td>
                                                    <td class="text-center">2</td>
                                                </tr>
                                                <tr class="border-b">
                                                    <td class="py-2">Durasi aktif</td>
                                                    <td class="text-center">1 bulan</td>
                                                    <td class="text-center">Selamanya</td>
                                                    <td class="text-center">Selamanya</td>
                                                </tr>
                                                <tr class="border-b">
                                                    <td class="py-2">Kuota tamu</td>
                                                    <td class="text-center">50</td>
                                                    <td class="text-center">Unlimited</td>
                                                    <td class="text-center">Unlimited</td>
                                                </tr>
                                                <tr class="border-b">
                                                    <td class="py-2">Tema premium</td>
                                                    <td class="text-center">❌</td>
                                                    <td class="text-center">✅</td>
                                                    <td class="text-center">✅</td>
                                                </tr>
                                                <tr>
                                                    <td class="py-2">Harga</td>
                                                    <td class="text-center">Gratis</td>
                                                    <td class="text-center">Rp 99k</td>
                                                    <td class="text-center">Rp 199k</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Bagaimana cara upgrade ke premium?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <ol class="list-decimal list-inside space-y-1">
                                        <li>Login ke dashboard</li>
                                        <li>Klik "Upgrade Plan" atau menu "Subscription"</li>
                                        <li>Pilih paket Premium atau Business</li>
                                        <li>Pilih metode pembayaran (Transfer Bank, OVO, GoPay, dll)</li>
                                        <li>Lakukan pembayaran sesuai instruksi</li>
                                        <li>Akun otomatis upgrade setelah pembayaran dikonfirmasi</li>
                                    </ol>
                                    <p class="mt-3"><strong>Metode pembayaran yang tersedia:</strong></p>
                                    <ul class="list-disc list-inside mt-1 text-sm">
                                        <li>Transfer Bank (BCA, BNI, BRI, Mandiri)</li>
                                        <li>E-Wallet (OVO, GoPay, DANA)</li>
                                        <li>Kartu Kredit (Visa, Mastercard)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Apakah ada garansi uang kembali?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Ya! Kami menawarkan <strong>garansi 7 hari uang kembali</strong> untuk paket Premium dan Business.</p>
                                    <div class="mt-3">
                                        <p><strong>Syarat refund:</strong></p>
                                        <ul class="list-disc list-inside mt-1">
                                            <li>Permintaan refund dalam 7 hari setelah pembayaran</li>
                                            <li>Belum menggunakan lebih dari 50% fitur premium</li>
                                            <li>Menyertakan alasan yang valid</li>
                                        </ul>
                                    </div>
                                    <p class="mt-3"><strong>Cara request refund:</strong></p>
                                    <p class="text-sm">Hubungi support@<?= strtolower(SITE_NAME) ?>.com dengan menyertakan Order ID dan alasan refund.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Technical Support -->
                <section class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-tools text-red-600 mr-3"></i>
                        Technical Support
                    </h2>

                    <div class="space-y-4">
                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Undangan saya tidak bisa dibuka, kenapa?</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Beberapa kemungkinan penyebab:</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li><strong>Undangan dinonaktifkan:</strong> Cek status di dashboard</li>
                                        <li><strong>Paket gratis expired:</strong> Upgrade atau renew paket</li>
                                        <li><strong>Link salah:</strong> Pastikan URL benar dan lengkap</li>
                                        <li><strong>Masalah browser:</strong> Coba buka di browser lain atau mode incognito</li>
                                        <li><strong>Koneksi internet:</strong> Pastikan koneksi stabil</li>
                                    </ol>
                                    <p class="mt-3">Jika masih bermasalah, hubungi support dengan menyertakan link undangan.</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">Foto yang diupload tidak muncul atau error</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Troubleshooting upload foto:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li><strong>Cek ukuran file:</strong> Maksimal 5MB (gratis) atau 10MB (premium)</li>
                                        <li><strong>Format file:</strong> Hanya JPG, PNG, GIF yang didukung</li>
                                        <li><strong>Koneksi lambat:</strong> Tunggu sampai upload selesai 100%</li>
                                        <li><strong>Browser cache:</strong> Refresh halaman atau clear cache</li>
                                        <li><strong>Kuota tercapai:</strong> Paket gratis maksimal 10 foto</li>
                                    </ul>
                                    <p class="mt-3 text-sm text-blue-600">💡 Tip: Kompres foto terlebih dahulu sebelum upload untuk mempercepat proses</p>
                                </div>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question w-full text-left p-4 bg-gray-50 rounded-lg hover:bg-gray-100 flex items-center justify-between">
                                <span class="font-medium text-gray-900">RSVP dari tamu tidak masuk ke dashboard</span>
                                <i class="fas fa-chevron-down text-gray-500 transform transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden p-4 bg-gray-50 rounded-b-lg">
                                <div class="text-gray-700">
                                    <p>Kemungkinan penyebab:</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li>Tamu tidak mengisi form RSVP dengan lengkap</li>
                                        <li>Ada error saat submit (tamu perlu coba lagi)</li>
                                        <li>Undangan dalam status nonaktif</li>
                                        <li>Cache browser - coba refresh halaman RSVP</li>
                                    </ol>
                                    <p class="mt-3"><strong>Solusi:</strong></p>
                                    <ul class="list-disc list-inside mt-1">
                                        <li>Minta tamu mengisi ulang RSVP</li>
                                        <li>Pastikan undangan aktif di dashboard</li>
                                        <li>Cek di menu "RSVP" dengan pilih undangan yang benar</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Contact Support -->
            <div class="mt-16 bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg p-8 text-white text-center">
                <h2 class="text-2xl font-bold mb-4">Masih Butuh Bantuan?</h2>
                <p class="text-lg mb-6">Tim support kami siap membantu Anda 24/7</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-2xl mx-auto">
                    <a href="mailto:support@<?= strtolower(SITE_NAME) ?>.com" 
                       class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4 hover:bg-opacity-30 transition-all">
                        <div class="text-2xl mb-2"><i class="fas fa-envelope"></i></div>
                        <div class="font-semibold">Email Support</div>
                        <div class="text-sm opacity-90">support@<?= strtolower(SITE_NAME) ?>.com</div>
                    </a>
                    
                    <a href="https://wa.me/6281234567890" 
                       target="_blank"
                       class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4 hover:bg-opacity-30 transition-all">
                        <div class="text-2xl mb-2"><i class="fab fa-whatsapp"></i></div>
                        <div class="font-semibold">WhatsApp</div>
                        <div class="text-sm opacity-90">+62 812-3456-7890</div>
                    </a>
                    
                    <a href="<?= $user ? '/profile.php' : '/register.php' ?>" 
                       class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4 hover:bg-opacity-30 transition-all">
                        <div class="text-2xl mb-2"><i class="fas fa-life-ring"></i></div>
                        <div class="font-semibold">Live Chat</div>
                        <div class="text-sm opacity-90">Available in dashboard</div>
                    </a>
                </div>
                
                <div class="mt-6 text-sm opacity-90">
                    Response time: Email (24 jam) • WhatsApp (2 jam) • Live Chat (Instant)
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div class="mb-4">
                <a href="/" class="text-2xl font-bold text-pink-400"><?= SITE_NAME ?></a>
            </div>
            <div class="flex justify-center space-x-6 text-sm">
                <a href="/help" class="text-gray-300 hover:text-white">Help Center</a>
                <a href="/terms" class="text-gray-300 hover:text-white">Terms of Service</a>
                <a href="/privacy" class="text-gray-300 hover:text-white">Privacy Policy</a>
                <a href="/contact" class="text-gray-300 hover:text-white">Contact Us</a>
            </div>
            <div class="mt-4 text-sm text-gray-400">
                © <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>

    <script>
        // FAQ Toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('i');
                const allAnswers = document.querySelectorAll('.faq-answer');
                const allIcons = document.querySelectorAll('.faq-question i');
                
                // Close all other answers
                allAnswers.forEach(otherAnswer => {
                    if (otherAnswer !== answer) {
                        otherAnswer.classList.add('hidden');
                    }
                });
                
                // Reset all other icons
                allIcons.forEach(otherIcon => {
                    if (otherIcon !== icon) {
                        otherIcon.style.transform = 'rotate(0deg)';
                    }
                });
                
                // Toggle current answer
                answer.classList.toggle('hidden');
                
                // Rotate icon
                if (answer.classList.contains('hidden')) {
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const faqItems = document.querySelectorAll('.faq-item');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question span').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                    // Highlight search term (optional)
                } else {
                    item.style.display = searchTerm === '' ? 'block' : 'none';
                }
            });
        });

        // Smooth scrolling for category links
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

        // Auto-expand FAQ if URL has hash
        window.addEventListener('load', function() {
            const hash = window.location.hash;
            if (hash) {
                const target = document.querySelector(hash);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    </script>
</body>
</html>
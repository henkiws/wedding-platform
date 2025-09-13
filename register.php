<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username harus diisi minimal 3 karakter';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Nama lengkap harus diisi';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Password dan konfirmasi password tidak sama';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $db = new Database();
        
        $existing_user = $db->fetch(
            "SELECT id FROM users WHERE username = ? OR email = ?", 
            [$username, $email]
        );
        
        if ($existing_user) {
            $errors[] = 'Username atau email sudah digunakan';
        }
    }
    
    // Register user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $db->query(
                "INSERT INTO users (username, email, full_name, phone, password, subscription_plan, subscription_expires) 
                VALUES (?, ?, ?, ?, ?, 'free', DATE_ADD(CURDATE(), INTERVAL ? DAY))",
                [$username, $email, $full_name, $phone, $hashed_password, FREE_DURATION_DAYS]
            );
            
            $success = 'Registrasi berhasil! Silakan login.';
        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?= SITE_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Flowbite -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.css" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="flex flex-col min-h-screen">
        <!-- Header -->
        <nav class="bg-white border-gray-200 shadow-sm">
            <div class="max-w-screen-xl flex items-center justify-between mx-auto p-4">
                <a href="/" class="flex items-center space-x-3">
                    <i class="fas fa-heart text-2xl text-pink-500"></i>
                    <span class="self-center text-2xl font-semibold text-gray-800"><?= SITE_NAME ?></span>
                </a>
                <a href="/login.php" class="text-gray-500 hover:text-gray-700">
                    Sudah punya akun? <span class="text-pink-600 font-medium">Login</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center py-12 px-4">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Buat Akun Baru</h1>
                        <p class="text-gray-600">Mulai buat undangan pernikahan digitalmu</p>
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
                            <div class="mt-3">
                                <a href="/login.php" class="text-green-700 underline hover:text-green-800">
                                    Login sekarang →
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" class="space-y-6">
                        <!-- Full Name -->
                        <div>
                            <label for="full_name" class="block mb-2 text-sm font-medium text-gray-900">
                                Nama Lengkap
                            </label>
                            <input type="text" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                   placeholder="Masukkan nama lengkap"
                                   required>
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block mb-2 text-sm font-medium text-gray-900">
                                Username
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                   placeholder="Pilih username unik"
                                   required>
                            <p class="mt-2 text-sm text-gray-500">Username akan digunakan untuk URL undangan Anda</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900">
                                Email
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                   placeholder="nama@email.com"
                                   required>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block mb-2 text-sm font-medium text-gray-900">
                                No. Telepon (Opsional)
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                   placeholder="08xxxxxxxxxx">
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900">
                                Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="confirm_password" class="block mb-2 text-sm font-medium text-gray-900">
                                Konfirmasi Password
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full p-2.5" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" 
                                        onclick="togglePassword('confirm_password')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-eye" id="confirm_password-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" 
                                       type="checkbox" 
                                       class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-pink-300" 
                                       required>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="font-light text-gray-500">
                                    Saya setuju dengan 
                                    <a href="/terms" class="font-medium text-pink-600 hover:underline">Syarat dan Ketentuan</a> 
                                    serta 
                                    <a href="/privacy" class="font-medium text-pink-600 hover:underline">Kebijakan Privasi</a>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full text-white bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:outline-none focus:ring-pink-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Buat Akun
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Login Link -->
                    <div class="text-center mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Sudah punya akun? 
                            <a href="/login.php" class="text-pink-600 font-medium hover:underline">Login di sini</a>
                        </p>
                    </div>
                </div>

                <!-- Benefits -->
                <div class="mt-8 bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-4 text-center">Keuntungan Bergabung</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-sm text-gray-600">Buat undangan digital gratis</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-sm text-gray-600">Template indah dan modern</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-sm text-gray-600">Fitur lengkap dan mudah digunakan</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-sm text-gray-600">Support 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-6">
            <div class="max-w-screen-xl mx-auto px-4 text-center">
                <p class="text-sm">© 2024 <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <!-- Flowbite JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.0/flowbite.min.js"></script>
    
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(inputId + '-eye');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
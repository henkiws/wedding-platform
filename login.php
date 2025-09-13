<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username)) {
        $errors[] = 'Username atau email harus diisi';
    }
    
    if (empty($password)) {
        $errors[] = 'Password harus diisi';
    }
    
    if (empty($errors)) {
        try {
            $db = new Database();
            
            // Find user by username or email
            $user = $db->fetch(
                "SELECT * FROM users WHERE username = ? OR email = ?",
                [$username, $username]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['subscription_plan'] = $user['subscription_plan'];
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                    
                    // You could store this token in database for security
                    // For simplicity, we'll just use the session
                }
                
                // Redirect to dashboard or intended page
                $redirect = $_GET['redirect'] ?? '/dashboard.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Username/email atau password salah';
            }
        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan saat login. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    
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
                <a href="/register.php" class="text-gray-500 hover:text-gray-700">
                    Belum punya akun? <span class="text-pink-600 font-medium">Daftar</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center py-12 px-4">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center mb-8">
                        <i class="fas fa-heart text-4xl text-pink-500 mb-4"></i>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang</h1>
                        <p class="text-gray-600">Masuk ke akun Anda untuk melanjutkan</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                            <div class="font-medium mb-2">Login gagal:</div>
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <!-- Username/Email -->
                        <div>
                            <label for="username" class="block mb-2 text-sm font-medium text-gray-900">
                                Username atau Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full pl-10 p-2.5" 
                                       placeholder="Masukkan username atau email"
                                       required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-900">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" 
                                       id="password" 
                                       name="password"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-pink-500 focus:border-pink-500 block w-full pl-10 pr-10 p-2.5" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me and Forgot Password -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="remember" 
                                           name="remember"
                                           type="checkbox" 
                                           class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-pink-300">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="remember" class="text-gray-500">Ingat saya</label>
                                </div>
                            </div>
                            <a href="/forgot-password.php" class="text-sm text-pink-600 hover:underline">
                                Lupa password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full text-white bg-pink-600 hover:bg-pink-700 focus:ring-4 focus:outline-none focus:ring-pink-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Masuk
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="flex items-center my-6">
                        <div class="flex-1 border-t border-gray-300"></div>
                        <div class="mx-4 text-gray-500 text-sm">atau</div>
                        <div class="flex-1 border-t border-gray-300"></div>
                    </div>

                    <!-- Social Login (Optional) -->
                    <div class="space-y-3">
                        <button type="button" class="w-full text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fab fa-google text-red-500 mr-2"></i>
                            Masuk dengan Google
                        </button>
                        <button type="button" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fab fa-facebook-f mr-2"></i>
                            Masuk dengan Facebook
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Belum punya akun? 
                            <a href="/register.php" class="text-pink-600 font-medium hover:underline">Daftar sekarang</a>
                        </p>
                    </div>
                </div>

                <!-- Demo Login -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <h3 class="font-medium text-blue-800">Demo Login</h3>
                    </div>
                    <p class="text-sm text-blue-700 mb-3">
                        Untuk mencoba platform, gunakan akun demo:
                    </p>
                    <div class="text-sm space-y-1">
                        <div><strong>Username:</strong> demo</div>
                        <div><strong>Password:</strong> demo123</div>
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

        // Auto-fill demo credentials when demo button is clicked
        function fillDemo() {
            document.getElementById('username').value = 'demo';
            document.getElementById('password').value = 'demo123';
        }
    </script>
</body>
</html>
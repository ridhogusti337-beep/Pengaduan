<?php
require_once 'config.php';

// Jika sudah login, langsung ke dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['user_name']    = $user['nama'];
            $_SESSION['user_role']    = $user['role'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['user_foto']    = $user['foto'];
            $_SESSION['pelanggan_id'] = $user['pelanggan_id'] ?? null;
            redirect(BASE_URL . '/dashboard.php');
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Layanan Pengaduan PDAM Bungo</title>
    <link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>/assets/images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .bg-pattern {
            background-color: #0369a1;
            background-image: radial-gradient(at 40% 20%, #0284c7 0px, transparent 50%),
                              radial-gradient(at 80% 0%, #0ea5e9 0px, transparent 50%),
                              radial-gradient(at 0% 50%, #0369a1 0px, transparent 50%),
                              radial-gradient(at 80% 50%, #0c4a6e 0px, transparent 50%),
                              radial-gradient(at 0% 100%, #082f49 0px, transparent 50%);
        }
        .glass-card {
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .wave-shape {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }
        .input-focus:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #0369a1 0%, #082f49 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(3,105,161,0.4);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
    </style>
</head>
<body class="min-h-screen bg-pattern flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Decorative circles -->
    <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full blur-xl animate-float"></div>
    <div class="absolute bottom-20 right-20 w-48 h-48 bg-sky-300/20 rounded-full blur-2xl animate-float" style="animation-delay: 2s;"></div>
    <div class="absolute top-1/2 left-10 w-20 h-20 bg-white/10 rounded-full blur-lg animate-float" style="animation-delay: 4s;"></div>

    <div class="w-full max-w-md z-10">
        <!-- Logo Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center p-2 bg-white rounded-3xl shadow-2xl mb-3 mx-auto">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo PERUMDA Air Minum Pancuran Telago Bungo" class="w-20 h-20 object-contain rounded-2xl">
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Layanan Pengaduan</h1>
            <p class="text-sky-100 text-sm mt-1 font-semibold">PERUMDA Air Minum Pancuran Telago</p>
            <p class="text-sky-200 text-xs">Kabupaten Bungo</p>
        </div>

        <!-- Card Login -->
        <div class="glass-card rounded-3xl shadow-2xl p-8">
            <h2 class="text-xl font-bold text-gray-800 mb-1">Selamat Datang 👋</h2>
            <p class="text-gray-500 text-sm mb-6">Masuk ke akun Anda untuk melanjutkan</p>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 mb-5 flex items-center gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                </div>
                <p class="text-red-700 text-sm font-medium"><?= sanitize($error) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Username -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username / Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-user text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" name="username" id="username"
                               value="<?= sanitize($_POST['username'] ?? '') ?>"
                               class="input-focus w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-gray-800 text-sm bg-gray-50 transition-all duration-200"
                               placeholder="Masukkan username atau email" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400 text-sm"></i>
                        </div>
                        <input type="password" name="password" id="password"
                               class="input-focus w-full pl-10 pr-11 py-3 border border-gray-200 rounded-xl text-gray-800 text-sm bg-gray-50 transition-all duration-200"
                               placeholder="Masukkan password" required>
                        <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i id="eye-icon" class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember + Lupa -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>
                    <a href="#" class="text-sm text-sky-600 font-medium hover:text-sky-800 transition-colors">Lupa password?</a>
                </div>

                <!-- Button Login -->
                <button type="submit" class="btn-login w-full py-3.5 text-white font-semibold rounded-xl text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Masuk ke Dashboard
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-white text-gray-400 font-medium">Akun Demo</span>
                </div>
            </div>

            <!-- Demo Credentials -->
            <div class="grid grid-cols-2 gap-3">
                <button onclick="fillDemo('admin','password')"
                        class="p-3 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-xl text-center transition-colors group">
                    <div class="w-7 h-7 bg-sky-500 rounded-lg flex items-center justify-center mx-auto mb-1.5 group-hover:bg-sky-600 transition-colors">
                        <i class="fa-solid fa-shield-halved text-white text-xs"></i>
                    </div>
                    <p class="text-xs font-semibold text-sky-700">Administrator</p>
                    <p class="text-xs text-sky-500">admin / password</p>
                </button>
                <button onclick="fillDemo('pelanggan','password')"
                        class="p-3 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl text-center transition-colors group">
                    <div class="w-7 h-7 bg-emerald-500 rounded-lg flex items-center justify-center mx-auto mb-1.5 group-hover:bg-emerald-600 transition-colors">
                        <i class="fa-solid fa-mobile-screen-button text-white text-xs"></i>
                    </div>
                    <p class="text-xs font-semibold text-emerald-700">Apk Pelanggan</p>
                    <p class="text-xs text-emerald-500">pelanggan / password</p>
                </button>
            </div>

            <!-- Hotline CS WhatsApp -->
            <div class="mt-5 pt-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-500 mb-1.5 font-medium">Butuh bantuan langsung dari Petugas?</p>
                <a href="<?= WA_CS_URL ?>" target="_blank"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition-all shadow-sm">
                    <i class="fa-brands fa-whatsapp text-emerald-500 text-sm"></i>
                    WhatsApp CS: <?= WA_CS_DISPLAY ?>
                </a>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-sky-200 text-xs mt-6">
            &copy; <?= date('Y') ?> PDAM Kabupaten Bungo. Hak Cipta Dilindungi.
        </p>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function fillDemo(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>

<?php
require_once '../config.php';
requireLogin();
requireRole('admin');

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'petugas';

    if (empty($nama)) $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($username)) $errors[] = 'Username wajib diisi.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    // Cek username unik
    $cekUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $cekUser->execute([$username]);
    if ($cekUser->fetch()) $errors[] = 'Username sudah dipakai.';

    // Cek email unik
    $cekEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $cekEmail->execute([$email]);
    if ($cekEmail->fetch()) $errors[] = 'Email sudah terdaftar.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $username, $email, $hash, $role]);

        flashMessage('success', "Akun pengguna $nama (@$username) berhasil dibuat.");
        redirect(BASE_URL . '/users/index.php');
    }
}

$pageTitle = 'Tambah Pengguna';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <a href="<?= BASE_URL ?>/users/index.php" class="w-9 h-9 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 transition-colors">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Tambah Pengguna Baru</h2>
                    <p class="text-sm text-gray-500">Buat akun untuk petugas atau administrator</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                <ul class="list-disc list-inside space-y-1 text-sm text-red-600">
                    <?php foreach ($errors as $e): ?>
                    <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-500 to-sky-600 px-6 py-4">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Formulir Pengguna Baru
                    </h3>
                </div>

                <form method="POST" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="<?= sanitize($old['nama'] ?? '') ?>" required placeholder="Contoh: Budi Prasetyo" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="<?= sanitize($old['username'] ?? '') ?>" required placeholder="budi_petugas" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?= sanitize($old['email'] ?? '') ?>" required placeholder="budi@pdambungo.id" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Hak Akses (Role) <span class="text-red-500">*</span></label>
                            <select name="role" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                                <option value="petugas" <?= ($old['role'] ?? '') === 'petugas' ? 'selected' : '' ?>>Petugas (Menangani Pengaduan)</option>
                                <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator (Akses Penuh)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/users/index.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="btn-primary text-white px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-save"></i> Buat Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

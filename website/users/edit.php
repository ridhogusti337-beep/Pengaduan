<?php
require_once '../config.php';
requireLogin();
requireRole('admin');

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/users/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) redirect(BASE_URL . '/users/index.php');

$errors = [];
$old = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? 'petugas';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password  = $_POST['password'] ?? '';

    if (empty($nama)) $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

    // Cek email duplikat
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $cek->execute([$email, $id]);
    if ($cek->fetch()) $errors[] = 'Email sudah digunakan oleh akun lain.';

    if (!empty($password) && strlen($password) < 6) {
        $errors[] = 'Password baru minimal 6 karakter.';
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, role = ?, is_active = ?, password = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $role, $is_active, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $role, $is_active, $id]);
        }

        // Update session jika sedang mengedit akun sendiri
        if ($id == $_SESSION['user_id']) {
            $_SESSION['user_name'] = $nama;
            $_SESSION['user_role'] = $role;
        }

        flashMessage('success', "Akun @{$user['username']} berhasil diperbarui.");
        redirect(BASE_URL . '/users/index.php');
    }
    $old = $_POST;
}

$pageTitle = 'Edit Pengguna';
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
                    <h2 class="text-xl font-bold text-gray-800">Edit Pengguna</h2>
                    <p class="text-sm text-gray-500">Perbarui informasi akun @<?= sanitize($user['username']) ?></p>
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
                        <i class="fa-solid fa-user-pen"></i> Form Edit Pengguna
                    </h3>
                </div>

                <form method="POST" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username</label>
                            <input type="text" value="<?= sanitize($user['username']) ?>" disabled class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Hak Akses (Role)</label>
                            <select name="role" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                                <option value="petugas" <?= ($old['role'] ?? $user['role']) === 'petugas' ? 'selected' : '' ?>>Petugas</option>
                                <option value="admin" <?= ($old['role'] ?? $user['role']) === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="nama" value="<?= sanitize($old['nama'] ?? $user['nama']) ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?= sanitize($old['email'] ?? $user['email']) ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password Baru (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" placeholder="••••••••" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= ($old['is_active'] ?? $user['is_active']) ? 'checked' : '' ?> class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-700">Akun Aktif (Bisa login ke sistem)</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/users/index.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="btn-primary text-white px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

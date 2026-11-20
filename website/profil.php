<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama)) $errors[] = 'Nama lengkap tidak boleh kosong.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

        // Cek email unik
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $cek->execute([$email, $user_id]);
        if ($cek->fetch()) $errors[] = 'Email sudah digunakan oleh akun lain.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $user_id]);
            $_SESSION['user_name'] = $nama;
            flashMessage('success', 'Profil berhasil diperbarui.');
            redirect(BASE_URL . '/profil.php');
        }
    } elseif ($action === 'change_password') {
        $old_pwd = $_POST['old_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_pwd, $user['password'])) {
            $errors[] = 'Password saat ini salah.';
        } elseif (strlen($new_pwd) < 6) {
            $errors[] = 'Password baru minimal 6 karakter.';
        } elseif ($new_pwd !== $confirm_pwd) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        } else {
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            flashMessage('success', 'Password berhasil diubah.');
            redirect(BASE_URL . '/profil.php');
        }
    }
}

$pageTitle = 'Profil Saya';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include 'includes/topbar.php'; ?>

    <main class="p-6">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gradient-to-tr from-sky-600 to-sky-400 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-md">
                    <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800"><?= sanitize($user['nama']) ?></h2>
                    <p class="text-sm text-gray-500">@<?= sanitize($user['username']) ?> &bull; <span class="capitalize font-medium text-sky-600"><?= $user['role'] ?></span></p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <ul class="list-disc list-inside space-y-1 text-sm text-red-600">
                    <?php foreach ($errors as $e): ?>
                    <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Info Profil -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-sky-500"></i> Ubah Biodata
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Username</label>
                            <input type="text" value="<?= sanitize($user['username']) ?>" disabled class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama" value="<?= sanitize($user['nama']) ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" name="email" value="<?= sanitize($user['email']) ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Peran / Role</label>
                            <input type="text" value="<?= ucfirst($user['role']) ?>" disabled class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm bg-gray-100 text-gray-500 capitalize cursor-not-allowed">
                        </div>
                        <button type="submit" class="btn-primary w-full py-2.5 text-white font-semibold rounded-xl text-sm mt-2">
                            Simpan Profil
                        </button>
                    </form>
                </div>

                <!-- Ganti Password -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fa-solid fa-key text-amber-500"></i> Ganti Password
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password Saat Ini</label>
                            <input type="password" name="old_password" required placeholder="••••••••" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Password Baru (min. 6)</label>
                            <input type="password" name="new_password" required placeholder="••••••••" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                        </div>
                        <button type="submit" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl text-sm mt-2 transition-colors">
                            Perbarui Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

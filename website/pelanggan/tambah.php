<?php
require_once '../config.php';
requireLogin();

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $no_pelanggan = trim($_POST['no_pelanggan'] ?? '');
    $nama         = trim($_POST['nama'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');
    $kelurahan    = trim($_POST['kelurahan'] ?? '');
    $kecamatan    = trim($_POST['kecamatan'] ?? '');
    $no_telepon   = trim($_POST['no_telepon'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $golongan     = $_POST['golongan'] ?? 'R1';

    if (empty($no_pelanggan)) $errors[] = 'Nomor Pelanggan wajib diisi.';
    if (empty($nama)) $errors[] = 'Nama Pelanggan wajib diisi.';
    if (empty($alamat)) $errors[] = 'Alamat lengkap wajib diisi.';

    // Cek nomor pelanggan unik
    $cek = $pdo->prepare("SELECT id FROM pelanggan WHERE no_pelanggan = ?");
    $cek->execute([$no_pelanggan]);
    if ($cek->fetch()) $errors[] = 'Nomor Pelanggan sudah terdaftar.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO pelanggan (no_pelanggan, nama, alamat, kelurahan, kecamatan, no_telepon, email, golongan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$no_pelanggan, $nama, $alamat, $kelurahan, $kecamatan, $no_telepon, $email, $golongan]);

        flashMessage('success', "Pelanggan $nama ($no_pelanggan) berhasil ditambahkan.");
        redirect(BASE_URL . '/pelanggan/index.php');
    }
} else {
    // Generate nomor pelanggan otomatis rekomendasi
    $lastId = $pdo->query("SELECT MAX(id) FROM pelanggan")->fetchColumn() ?? 0;
    $old['no_pelanggan'] = 'PDM-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
}

$pageTitle = 'Tambah Pelanggan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <a href="<?= BASE_URL ?>/pelanggan/index.php" class="w-9 h-9 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 transition-colors">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Tambah Pelanggan Baru</h2>
                    <p class="text-sm text-gray-500">Daftarkan pelanggan PDAM baru ke sistem</p>
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
                        <i class="fa-solid fa-user-plus"></i> Formulir Pelanggan Baru
                    </h3>
                </div>

                <form method="POST" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">No. Pelanggan <span class="text-red-500">*</span></label>
                            <input type="text" name="no_pelanggan" value="<?= sanitize($old['no_pelanggan'] ?? '') ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Golongan Tarif <span class="text-red-500">*</span></label>
                            <select name="golongan" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                                <option value="R1" <?= ($old['golongan'] ?? '') === 'R1' ? 'selected' : '' ?>>R1 - Rumah Tangga Sangat Sederhana</option>
                                <option value="R2" <?= ($old['golongan'] ?? '') === 'R2' ? 'selected' : '' ?>>R2 - Rumah Tangga Menengah</option>
                                <option value="R3" <?= ($old['golongan'] ?? '') === 'R3' ? 'selected' : '' ?>>R3 - Rumah Tangga Mampu</option>
                                <option value="S1" <?= ($old['golongan'] ?? '') === 'S1' ? 'selected' : '' ?>>S1 - Sosial Khusus</option>
                                <option value="S2" <?= ($old['golongan'] ?? '') === 'S2' ? 'selected' : '' ?>>S2 - Sosial Umum</option>
                                <option value="B1" <?= ($old['golongan'] ?? '') === 'B1' ? 'selected' : '' ?>>B1 - Bisnis Kecil</option>
                                <option value="B2" <?= ($old['golongan'] ?? '') === 'B2' ? 'selected' : '' ?>>B2 - Bisnis Besar</option>
                                <option value="I1" <?= ($old['golongan'] ?? '') === 'I1' ? 'selected' : '' ?>>I1 - Industri</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap Pelanggan <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="<?= sanitize($old['nama'] ?? '') ?>" required placeholder="Contoh: Muhammad Ali" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat Pemasangan <span class="text-red-500">*</span></label>
                        <textarea name="alamat" rows="3" required placeholder="Jl. Jenderal Sudirman No. 123..." class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400 resize-none"><?= sanitize($old['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kelurahan / Desa</label>
                            <input type="text" name="kelurahan" value="<?= sanitize($old['kelurahan'] ?? '') ?>" placeholder="Muara Bungo" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kecamatan</label>
                            <input type="text" name="kecamatan" value="<?= sanitize($old['kecamatan'] ?? '') ?>" placeholder="Bungo Dani" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" value="<?= sanitize($old['no_telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email (opsional)</label>
                            <input type="email" name="email" value="<?= sanitize($old['email'] ?? '') ?>" placeholder="pelanggan@mail.com" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:outline-none focus:border-sky-400">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/pelanggan/index.php" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="btn-primary text-white px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-save"></i> Simpan Pelanggan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

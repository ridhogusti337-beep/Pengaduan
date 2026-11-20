<?php
require_once '../config.php';
requireLogin();
requireRole(['admin', 'petugas', 'pelanggan']);

$isPelanggan = ($_SESSION['user_role'] === 'pelanggan');
$myPelangganId = $_SESSION['pelanggan_id'] ?? 1;

// Get data pelanggan & kategori
$pelanggan = [];
if (!$isPelanggan) {
    $pelanggan = $pdo->query("SELECT * FROM pelanggan ORDER BY nama ASC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ?");
    $stmt->execute([$myPelangganId]);
    $myInfo = $stmt->fetch();
}

$kategori = $pdo->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori ASC")->fetchAll();
$petugas  = $pdo->query("SELECT * FROM users WHERE role != 'pelanggan' AND is_active = 1 ORDER BY nama ASC")->fetchAll();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $pelanggan_id = $isPelanggan ? $myPelangganId : intval($_POST['pelanggan_id'] ?? 0);
    $kategori_id  = intval($_POST['kategori_id']  ?? 0);
    $judul        = trim($_POST['judul']           ?? '');
    $deskripsi    = trim($_POST['deskripsi']       ?? '');
    $lokasi       = trim($_POST['lokasi']          ?? '');
    $prioritas    = $_POST['prioritas']            ?? 'sedang';
    $petugas_id   = (!$isPelanggan && !empty($_POST['petugas_id'])) ? intval($_POST['petugas_id']) : null;

    // Validasi
    if (!$pelanggan_id) $errors[] = 'Data pelanggan tidak valid.';
    if (!$kategori_id)  $errors[] = 'Kategori wajib dipilih.';
    if (empty($judul))  $errors[] = 'Judul pengaduan wajib diisi.';
    if (empty($deskripsi)) $errors[] = 'Deskripsi wajib diisi.';

    // Upload foto
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext       = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png','gif','webp'];
        $maxSize   = 5 * 1024 * 1024; // 5MB
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format foto tidak diizinkan. Gunakan JPG, PNG, atau GIF.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $errors[] = 'Ukuran foto maksimal 5MB.';
        } else {
            $foto = 'uploads/pengaduan/' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto);
        }
    }

    if (empty($errors)) {
        $noPengaduan = 'PGD-' . date('Ym') . '-' . str_pad(rand(1,9999), 4,'0',STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO pengaduan (no_pengaduan, pelanggan_id, kategori_id, judul, deskripsi, lokasi, prioritas, petugas_id, foto_pengaduan, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'baru')
        ");
        $stmt->execute([$noPengaduan, $pelanggan_id, $kategori_id, $judul, $deskripsi, $lokasi, $prioritas, $petugas_id, $foto]);

        $newId = $pdo->lastInsertId();

        // Log riwayat
        $pdo->prepare("INSERT INTO riwayat_pengaduan (pengaduan_id, status_lama, status_baru, keterangan, user_id) VALUES (?, NULL, 'baru', 'Pengaduan baru dibuat oleh pelanggan', ?)")
            ->execute([$newId, $_SESSION['user_id']]);

        flashMessage('success', "Pengaduan $noPengaduan berhasil dikirim ke sistem PDAM.");
        redirect(BASE_URL . '/pengaduan/detail.php?id=' . $newId);
    }
}

$pageTitle = 'Tambah Pengaduan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <a href="<?= BASE_URL ?>/pengaduan/index.php" class="w-9 h-9 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 transition-colors">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        <?= $isPelanggan ? 'Sampaikan Pengaduan Anda' : 'Tambah Pengaduan Baru' ?>
                    </h2>
                    <p class="text-sm text-gray-500">
                        <?= $isPelanggan ? 'Laporkan gangguan aliran air atau keluhan meteran' : 'Isi formulir pengaduan pelanggan' ?>
                    </p>
                </div>
            </div>

            <!-- Emergency WhatsApp Callout -->
            <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-emerald-800">Gangguan Darurat / Butuh Respons Cepat?</p>
                        <p class="text-xs text-emerald-700">Hubungi langsung Customer Service via WhatsApp di <strong><?= WA_CS_DISPLAY ?></strong></p>
                    </div>
                </div>
                <a href="<?= WA_CS_URL ?>" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 w-fit flex-shrink-0 transition-colors">
                    <i class="fa-brands fa-whatsapp text-sm"></i> Chat Sekarang
                </a>
            </div>

            <!-- Error Messages -->
            <?php if(!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                    <p class="font-semibold text-red-700 text-sm">Terdapat <?= count($errors) ?> kesalahan:</p>
                </div>
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach($errors as $err): ?>
                    <li class="text-red-600 text-sm"><?= sanitize($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r <?= $isPelanggan ? 'from-teal-600 to-emerald-600' : 'from-sky-500 to-sky-600' ?> px-6 py-4">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fa-solid <?= $isPelanggan ? 'fa-bullhorn' : 'fa-file-pen' ?>"></i>
                        Formulir Pengaduan Pelanggan
                    </h3>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5">

                    <!-- Pelanggan Info / Select -->
                    <?php if ($isPelanggan): ?>
                    <div class="p-4 rounded-xl bg-teal-50 border border-teal-100">
                        <p class="text-xs font-semibold text-teal-800 mb-1">Identitas Pelanggan:</p>
                        <p class="text-sm font-bold text-gray-800"><?= sanitize($myInfo['nama'] ?? $_SESSION['user_name']) ?> <span class="font-mono text-teal-700 font-normal">(<?= sanitize($myInfo['no_pelanggan'] ?? 'PDM-001') ?>)</span></p>
                        <p class="text-xs text-gray-600 mt-0.5"><?= sanitize($myInfo['alamat'] ?? '') ?>, Kec. <?= sanitize($myInfo['kecamatan'] ?? '') ?></p>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Pelanggan <span class="text-red-500">*</span>
                            </label>
                            <select name="pelanggan_id" required
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                                <option value="">-- Pilih Pelanggan --</option>
                                <?php foreach($pelanggan as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($old['pelanggan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($p['no_pelanggan']) ?> - <?= sanitize($p['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Kategori Masalah / Pengaduan <span class="text-red-500">*</span>
                            </label>
                            <select name="kategori_id" required
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                                <option value="">-- Pilih Kategori Masalah --</option>
                                <?php foreach($kategori as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($old['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($k['nama_kategori']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php if (!$isPelanggan): ?>
                    </div>
                    <?php endif; ?>

                    <!-- Judul -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Ringkasan Keluhan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="judul"
                               value="<?= sanitize($old['judul'] ?? '') ?>"
                               placeholder="Contoh: Air mati sejak kemarin sore / Pipa bocor di depan pagar"
                               required
                               class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Keterangan Lengkap Keluhan <span class="text-red-500">*</span>
                        </label>
                        <textarea name="deskripsi" rows="4"
                                  placeholder="Ceritakan permasalahan secara mendetail..."
                                  required
                                  class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50 resize-none"><?= sanitize($old['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <!-- Lokasi Kejadian -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Titik / Alamat Kejadian</label>
                        <input type="text" name="lokasi"
                               value="<?= sanitize($old['lokasi'] ?? ($isPelanggan ? ($myInfo['alamat'] ?? '') : '')) ?>"
                               placeholder="Contoh: Jl. Merdeka No. 10 (depan masjid)"
                               class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                    </div>

                    <!-- Prioritas & Petugas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tingkat Kepentingan</label>
                            <select name="prioritas"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                                <option value="rendah" <?= ($old['prioritas'] ?? '') === 'rendah' ? 'selected' : '' ?>>Rendah (Dapat menunggu)</option>
                                <option value="sedang" <?= ($old['prioritas'] ?? 'sedang') === 'sedang' ? 'selected' : '' ?>>Sedang (Normal)</option>
                                <option value="tinggi" <?= ($old['prioritas'] ?? '') === 'tinggi' ? 'selected' : '' ?>>Tinggi (Darurat / Pipa Pecah)</option>
                            </select>
                        </div>

                        <?php if (!$isPelanggan): ?>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tugaskan ke Petugas</label>
                            <select name="petugas_id"
                                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-700 focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                                <option value="">-- Belum Ditugaskan --</option>
                                <?php foreach($petugas as $pt): ?>
                                <option value="<?= $pt['id'] ?>" <?= ($old['petugas_id'] ?? '') == $pt['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($pt['nama']) ?> (<?= ucfirst($pt['role']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Foto -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Bukti / Kondisi (Opsional)</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-5 text-center hover:border-sky-300 transition-colors cursor-pointer" onclick="document.getElementById('foto-input').click()">
                            <i class="fa-solid fa-cloud-arrow-up text-gray-400 text-2xl mb-2 block"></i>
                            <p class="text-sm text-gray-500">Klik untuk melampirkan foto</p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (maks. 5MB)</p>
                            <input type="file" id="foto-input" name="foto" accept="image/*" class="hidden" onchange="previewFoto(this)">
                        </div>
                        <div id="foto-preview" class="hidden mt-3 text-center">
                            <img id="foto-img" src="" alt="Preview" class="max-h-48 mx-auto rounded-xl border border-gray-200">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/pengaduan/index.php"
                           class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="<?= $isPelanggan ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700' : 'btn-primary' ?> text-white px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                            <i class="fa-solid fa-paper-plane"></i>
                            <?= $isPelanggan ? 'Kirim Pengaduan' : 'Simpan Pengaduan' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('foto-img').src = e.target.result;
            document.getElementById('foto-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>

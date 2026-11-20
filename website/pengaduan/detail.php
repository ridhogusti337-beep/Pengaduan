<?php
require_once '../config.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/pengaduan/index.php');

// Ambil detail pengaduan
$stmt = $pdo->prepare("
    SELECT p.*, pl.nama as nama_pelanggan, pl.no_pelanggan, pl.alamat, pl.no_telepon, pl.email as email_pelanggan,
           pl.kecamatan, pl.kelurahan, pl.golongan,
           k.nama_kategori, u.nama as nama_petugas
    FROM pengaduan p
    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.petugas_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) redirect(BASE_URL . '/pengaduan/index.php');

// Proteksi akses untuk pelanggan
if ($_SESSION['user_role'] === 'pelanggan' && $p['pelanggan_id'] != ($_SESSION['pelanggan_id'] ?? 0)) {
    flashMessage('error', 'Anda tidak memiliki hak akses untuk melihat pengaduan ini.');
    redirect(BASE_URL . '/pengaduan/index.php');
}

// Riwayat
$riwayat = $pdo->prepare("
    SELECT r.*, u.nama as nama_user
    FROM riwayat_pengaduan r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.pengaduan_id = ?
    ORDER BY r.created_at DESC
");
$riwayat->execute([$id]);
$histori = $riwayat->fetchAll();

// Update status (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus     = $_POST['status']           ?? '';
    $petugas_id    = !empty($_POST['petugas_id']) ? intval($_POST['petugas_id']) : null;
    $catatan       = trim($_POST['catatan']     ?? '');
    $validStatus   = ['baru','proses','selesai','ditolak'];

    if (in_array($newStatus, $validStatus)) {
        $tglSelesai = ($newStatus === 'selesai') ? date('Y-m-d H:i:s') : null;
        $pdo->prepare("UPDATE pengaduan SET status=?, petugas_id=?, catatan_petugas=?, tgl_selesai=?, updated_at=NOW() WHERE id=?")
            ->execute([$newStatus, $petugas_id, $catatan, $tglSelesai, $id]);

        // Log riwayat
        $pdo->prepare("INSERT INTO riwayat_pengaduan (pengaduan_id, status_lama, status_baru, keterangan, user_id) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id, $p['status'], $newStatus, $catatan, $_SESSION['user_id']]);

        flashMessage('success', 'Status pengaduan berhasil diperbarui.');
        redirect(BASE_URL . '/pengaduan/detail.php?id=' . $id);
    }
}

$petugas = $pdo->query("SELECT * FROM users WHERE role != 'pelanggan' AND is_active = 1 ORDER BY nama")->fetchAll();

$pageTitle = 'Detail Pengaduan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6">
        <!-- Back + Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="<?= BASE_URL ?>/pengaduan/index.php" class="w-9 h-9 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 transition-colors">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Detail Pengaduan</h2>
                    <p class="text-sm text-gray-500"><?= sanitize($p['no_pengaduan']) ?></p>
                </div>
            </div>
            <?php if($_SESSION['user_role'] === 'admin'): ?>
            <div class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/pengaduan/edit.php?id=<?= $id ?>"
                   class="flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold transition-colors">
                    <i class="fa-solid fa-pen text-xs"></i> Edit
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Detail Utama -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Info Pengaduan -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Informasi Pengaduan</h3>
                        <div class="flex items-center gap-2">
                            <?= prioritasBadge($p['prioritas']) ?>
                            <?= statusBadge($p['status']) ?>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">No. Pengaduan</p>
                            <p class="font-bold text-sky-600 text-lg"><?= sanitize($p['no_pengaduan']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Judul</p>
                            <p class="font-semibold text-gray-800"><?= sanitize($p['judul']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Kategori</p>
                            <p class="text-gray-700"><?= sanitize($p['nama_kategori']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Deskripsi</p>
                            <p class="text-gray-700 text-sm leading-relaxed bg-gray-50 rounded-xl p-4"><?= nl2br(sanitize($p['deskripsi'])) ?></p>
                        </div>
                        <?php if($p['lokasi']): ?>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Lokasi</p>
                            <p class="text-gray-700 flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-location-dot text-sky-500"></i>
                                <?= sanitize($p['lokasi']) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <?php if($p['foto_pengaduan']): ?>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Foto</p>
                            <img src="<?= BASE_URL ?>/<?= $p['foto_pengaduan'] ?>" alt="Foto Pengaduan" class="max-h-64 rounded-xl border border-gray-200 cursor-pointer" onclick="window.open(this.src)">
                        </div>
                        <?php endif; ?>
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tanggal Masuk</p>
                                <p class="text-sm text-gray-700"><?= formatTanggalWaktu($p['tgl_pengaduan']) ?></p>
                            </div>
                            <?php if($p['tgl_selesai']): ?>
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Tanggal Selesai</p>
                                <p class="text-sm text-gray-700"><?= formatTanggalWaktu($p['tgl_selesai']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Status -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Riwayat Penanganan</h3>
                    </div>
                    <div class="p-6">
                        <?php if(empty($histori)): ?>
                        <p class="text-sm text-gray-400 text-center py-4">Belum ada riwayat</p>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($histori as $h): ?>
                            <?php
                            $dotColors = ['baru'=>'bg-blue-500','proses'=>'bg-amber-500','selesai'=>'bg-emerald-500','ditolak'=>'bg-red-500'];
                            $dotColor  = $dotColors[$h['status_baru']] ?? 'bg-gray-400';
                            ?>
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 <?= $dotColor ?> rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-check text-white text-xs"></i>
                                    </div>
                                    <div class="w-0.5 bg-gray-100 flex-1 mt-1"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-gray-800 capitalize"><?= $h['status_baru'] ?></p>
                                        <p class="text-xs text-gray-400"><?= formatTanggalWaktu($h['created_at']) ?></p>
                                    </div>
                                    <?php if($h['keterangan']): ?>
                                    <p class="text-sm text-gray-600 mt-1 bg-gray-50 rounded-lg px-3 py-2"><?= sanitize($h['keterangan']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-400 mt-1">oleh <?= sanitize($h['nama_user'] ?? 'Sistem') ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="space-y-5">
                <!-- Info Pelanggan -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Data Pelanggan</h3>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-sky-600 font-bold text-sm"><?= strtoupper(substr($p['nama_pelanggan'], 0, 1)) ?></span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm"><?= sanitize($p['nama_pelanggan']) ?></p>
                                <p class="text-xs text-sky-600"><?= sanitize($p['no_pelanggan']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex gap-2">
                                <i class="fa-solid fa-map-marker-alt text-gray-400 w-4 mt-0.5 text-xs flex-shrink-0"></i>
                                <span class="text-gray-600 text-xs"><?= sanitize($p['alamat']) ?>, <?= sanitize($p['kecamatan']) ?></span>
                            </div>
                            <?php if($p['no_telepon']): ?>
                            <div class="flex gap-2 items-center">
                                <i class="fa-solid fa-phone text-gray-400 w-4 text-xs flex-shrink-0"></i>
                                <span class="text-gray-600 text-xs"><?= sanitize($p['no_telepon']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex gap-2 items-center">
                                <i class="fa-solid fa-tag text-gray-400 w-4 text-xs flex-shrink-0"></i>
                                <span class="text-xs bg-sky-50 text-sky-600 font-medium px-2 py-0.5 rounded-full">Golongan <?= sanitize($p['golongan']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Status -->
                <?php if(in_array($_SESSION['user_role'], ['admin','petugas']) && $p['status'] !== 'selesai' && $p['status'] !== 'ditolak'): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Update Status</h3>
                    </div>
                    <form method="POST" class="p-5 space-y-3">
                        <input type="hidden" name="update_status" value="1">

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status Baru</label>
                            <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:border-sky-400 bg-gray-50">
                                <option value="baru" <?= $p['status']==='baru'?'selected':'' ?>>Baru</option>
                                <option value="proses" <?= $p['status']==='proses'?'selected':'' ?>>Sedang Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        <?php if($_SESSION['user_role'] === 'admin'): ?>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tugaskan ke Petugas</label>
                            <select name="petugas_id" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:border-sky-400 bg-gray-50">
                                <option value="">-- Pilih Petugas --</option>
                                <?php foreach($petugas as $pt): ?>
                                <option value="<?= $pt['id'] ?>" <?= $p['petugas_id'] == $pt['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($pt['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Catatan Petugas</label>
                            <textarea name="catatan" rows="3" placeholder="Tambahkan catatan penanganan..."
                                      class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 focus:outline-none focus:border-sky-400 bg-gray-50 resize-none"><?= sanitize($p['catatan_petugas'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary w-full py-2.5 text-white rounded-xl text-sm font-semibold flex items-center justify-center gap-2">
                            <i class="fa-solid fa-rotate text-xs"></i>
                            Update Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Petugas Bertugas -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Petugas Bertugas</p>
                    <?php if($p['nama_petugas']): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center">
                            <span class="text-emerald-600 font-bold text-sm"><?= strtoupper(substr($p['nama_petugas'], 0, 1)) ?></span>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-gray-800"><?= sanitize($p['nama_petugas']) ?></p>
                            <p class="text-xs text-emerald-600">Petugas PDAM</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-400 italic">Belum ada petugas yang ditugaskan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

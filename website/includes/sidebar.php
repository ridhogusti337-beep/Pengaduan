<?php
$currentPage = basename(dirname($_SERVER['PHP_SELF'])) . '/' . basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$role        = $_SESSION['user_role'] ?? 'petugas';

function isActive($page) {
    global $currentPage, $currentDir;
    if (is_array($page)) {
        return in_array($currentDir, $page) ? 'active' : '';
    }
    return (strpos($currentPage, $page) !== false || $currentDir === $page) ? 'active' : '';
}
?>
<!-- Sidebar -->
<aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col h-full border-r border-gray-100 transition-all duration-300 z-50">

    <!-- Logo -->
    <div class="px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-white border border-gray-100 rounded-xl p-1 shadow-sm flex items-center justify-center flex-shrink-0">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo Pancuran Telago" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-bold text-gray-800 leading-tight truncate">Layanan Pengaduan</h1>
                <p class="text-[11px] text-gray-400 leading-tight truncate">Pancuran Telago Bungo</p>
            </div>
        </div>
    </div>

    <!-- User Info Mini -->
    <div class="px-4 py-3 mx-3 mt-3 <?= $role === 'pelanggan' ? 'bg-emerald-50' : 'bg-sky-50' ?> rounded-xl">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 <?= $role === 'pelanggan' ? 'bg-emerald-500' : 'bg-sky-500' ?> rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-700 truncate"><?= sanitize($_SESSION['user_name']) ?></p>
                <p class="text-xs <?= $role === 'pelanggan' ? 'text-emerald-600 font-semibold' : 'text-sky-600 capitalize' ?>">
                    <?= $role === 'pelanggan' ? 'Pelanggan PDAM' : ucfirst($role) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">

        <?php if ($role === 'pelanggan'): ?>
            <!-- ================= KHUSUS APK PELANGGAN ================= -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Layanan Pelanggan</p>

            <a href="<?= BASE_URL ?>/dashboard.php"
               class="sidebar-link <?= isActive('dashboard.php') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-house-chimney-user w-5 text-center"></i>
                Beranda Pelanggan
            </a>

            <a href="<?= BASE_URL ?>/pengaduan/tambah.php"
               class="sidebar-link <?= isActive('pengaduan/tambah.php') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent text-emerald-600 font-semibold">
                <i class="fa-solid fa-circle-plus w-5 text-center text-emerald-500"></i>
                Buat Pengaduan Baru
            </a>

            <a href="<?= BASE_URL ?>/pengaduan/index.php"
               class="sidebar-link <?= (isActive('pengaduan') && !isActive('pengaduan/tambah.php')) ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                Riwayat Pengaduan Saya
                <?php
                global $pdo;
                $pelangganId = $_SESSION['pelanggan_id'] ?? 0;
                $myActiveCount = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE pelanggan_id = ? AND status IN ('baru','proses')");
                $myActiveCount->execute([$pelangganId]);
                $actN = $myActiveCount->fetchColumn();
                if ($actN > 0):
                ?>
                <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $actN ?></span>
                <?php endif; ?>
            </a>

        <?php else: ?>
            <!-- ================= MENU ADMIN & PETUGAS ================= -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2">Menu Utama</p>

            <a href="<?= BASE_URL ?>/dashboard.php"
               class="sidebar-link <?= isActive('dashboard.php') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                Dashboard
            </a>

            <!-- Pengaduan Menu -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2 mt-4">Pengaduan</p>

            <a href="<?= BASE_URL ?>/pengaduan/index.php"
               class="sidebar-link <?= isActive('pengaduan') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-clipboard-list w-5 text-center"></i>
                Daftar Pengaduan
                <?php
                global $pdo;
                $newCount = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='baru'")->fetchColumn();
                if($newCount > 0):
                ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $newCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>/pengaduan/tambah.php"
               class="sidebar-link <?= isActive('pengaduan/tambah.php') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-plus-circle w-5 text-center"></i>
                Tambah Pengaduan
            </a>

            <!-- Pelanggan Menu -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2 mt-4">Data Master</p>

            <a href="<?= BASE_URL ?>/pelanggan/index.php"
               class="sidebar-link <?= isActive('pelanggan') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-users w-5 text-center"></i>
                Data Pelanggan
            </a>

            <?php if ($role === 'admin'): ?>
            <a href="<?= BASE_URL ?>/users/index.php"
               class="sidebar-link <?= isActive('users') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-user-gear w-5 text-center"></i>
                Manajemen User
            </a>

            <!-- Laporan Menu -->
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2 mt-4">Laporan</p>

            <a href="<?= BASE_URL ?>/laporan/index.php"
               class="sidebar-link <?= isActive('laporan') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
                <i class="fa-solid fa-chart-bar w-5 text-center"></i>
                Laporan Pengaduan
            </a>

            <a href="<?= BASE_URL ?>/export_db.php" target="_blank"
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent hover:text-sky-600">
                <i class="fa-solid fa-download w-5 text-center text-sky-500"></i>
                Unduh Database (.sql)
            </a>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Profil & Keluar -->
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 mb-2 mt-4">Akun</p>

        <a href="<?= BASE_URL ?>/profil.php"
           class="sidebar-link <?= isActive('profil.php') ?> flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 border-l-3 border-transparent">
            <i class="fa-solid fa-user-circle w-5 text-center"></i>
            Profil Saya
        </a>

        <a href="<?= BASE_URL ?>/logout.php"
           class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-500 border-l-3 border-transparent hover:bg-red-50 hover:text-red-600"
           onclick="return confirm('Yakin ingin keluar?')">
            <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
            Keluar
        </a>
    </nav>

    <!-- Info CS WhatsApp -->
    <div class="px-3 py-2 mx-3 mb-2 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/60 rounded-2xl">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-sm shadow-sm">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider leading-tight">Hotline CS PDAM</p>
                <a href="<?= WA_CS_URL ?>" target="_blank" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 leading-tight block truncate">
                    <?= WA_CS_DISPLAY ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer Sidebar -->
    <div class="px-4 py-3 border-t border-gray-100">
        <p class="text-xs text-gray-400 text-center">v1.0.0 &bull; PDAM Bungo &copy; <?= date('Y') ?></p>
    </div>
</aside>

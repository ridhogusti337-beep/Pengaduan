<?php
// =============================================
// KONFIGURASI DATABASE - PDAM BUNGO
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pdam_bungo');
define('DB_PORT', 3306);

define('APP_NAME', 'Layanan Pengaduan - PERUMDA Air Minum Pancuran Telago Bungo');
define('APP_SHORT', 'Layanan Pengaduan');
define('APP_VERSION', '1.0.0');

// Kontak Customer Service WhatsApp
define('WA_CS', '08117442828');
define('WA_CS_DISPLAY', '0811-7442-828');
define('WA_CS_URL', 'https://wa.me/628117442828?text=Halo%20CS%20PDAM%20Pancuran%20Telago%20Bungo,%20saya%20ingin%20berkonsultasi/lapor%20pengaduan');

// Deteksi dinamis BASE_URL (support http://localhost/website maupun http://website.test)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    if (strpos($scriptName, '/website') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/website') !== false) {
        define('BASE_URL', rtrim($protocol . $host . '/website', '/'));
    } else {
        define('BASE_URL', rtrim($protocol . $host, '/'));
    }
}

// Koneksi PDO dengan Auto-Init Database jika belum ada
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    // Matikan ONLY_FULL_GROUP_BY untuk kompatibilitas penuh MySQL 8
    $pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
} catch (PDOException $e) {
    // Jika database belum ada (error code 1049), buat otomatis dari database.sql
    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $pdoRoot = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $sqlFile = __DIR__ . '/database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $pdoRoot->exec($sqlContent);
            }
            
            // Re-koneksi setelah database dibuat
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (Exception $ex) {
            die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;border-radius:12px;margin:30px auto;max-width:600px;text-align:center;'>
                <h2>Gagal Inisialisasi Database Otomatis</h2>
                <p>Pesan error: " . htmlspecialchars($ex->getMessage()) . "</p>
                <p>Silakan pastikan service MySQL di Laragon/XAMPP sudah Start.</p>
            </div>");
        }
    } else {
        die("<div style='font-family:sans-serif;padding:30px;background:#fef2f2;border:1px solid #f87171;color:#991b1b;border-radius:12px;margin:30px auto;max-width:600px;text-align:center;'>
            <h2>Koneksi Database Gagal</h2>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Pastikan MySQL di Laragon/XAMPP aktif.</p>
        </div>");
    }
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi Helper
function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], (array)$roles)) {
        redirect(BASE_URL . '/dashboard.php');
    }
}

function getCurrentUser() {
    return $_SESSION ?? [];
}

function formatTanggal($date, $format = 'd F Y') {
    if (!$date) return '-';
    $bulan = [
        '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
        '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
        '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
    ];
    $d = date('d', strtotime($date));
    $m = $bulan[date('m', strtotime($date))];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

function formatTanggalWaktu($date) {
    if (!$date) return '-';
    return formatTanggal($date) . ' ' . date('H:i', strtotime($date));
}

function statusBadge($status) {
    $badges = [
        'baru'    => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>Baru</span>',
        'proses'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-1.5"></span>Proses</span>',
        'selesai' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>Selesai</span>',
        'ditolak' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>Ditolak</span>',
    ];
    return $badges[$status] ?? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' . ucfirst($status) . '</span>';
}

function prioritasBadge($prioritas) {
    $badges = [
        'rendah'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Rendah</span>',
        'sedang'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">Sedang</span>',
        'tinggi'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Tinggi</span>',
    ];
    return $badges[$prioritas] ?? '-';
}

function generateNoPengaduan() {
    $year = date('Y');
    $month = date('m');
    return 'PGD-' . $year . $month . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function flashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

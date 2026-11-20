<?php
/**
 * Download Database SQL Langsung dari Browser
 * Akses URL: http://localhost/website/export_db.php
 */
$file = __DIR__ . '/pdam_bungo.sql';
if (!file_exists($file)) {
    $file = __DIR__ . '/database.sql';
}

if (file_exists($file)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="pdam_bungo.sql"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    die("File database SQL tidak ditemukan.");
}

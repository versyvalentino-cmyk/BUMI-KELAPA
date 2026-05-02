<?php
// ============================================
// config.php — Koneksi Database Railway
// ============================================

define('DB_HOST',    'mysql.railway.internal');
define('DB_NAME',    'railway');
define('DB_USER',    'root');
define('DB_PASS',    'UWfVrQgJWXzFZXbwxiaBDwbqrEozpAdV');
define('DB_PORT',    '3306');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST
                 . ";port=" . DB_PORT
                 . ";dbname=" . DB_NAME
                 . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'sukses' => false,
                'pesan'  => 'Koneksi database gagal: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

// Generate kode pesanan unik: BK-YYYYMMDD-001
function generateKodePesanan() {
    $db     = getDB();
    $tgl    = date('Ymd');
    $prefix = "BK-{$tgl}-";
    $stmt   = $db->prepare("SELECT COUNT(*) FROM pesanan WHERE kode_pesanan LIKE ?");
    $stmt->execute([$prefix . '%']);
    $count  = (int)$stmt->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

// Helper response JSON
function jsonResponse($sukses, $pesan, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['sukses' => $sukses, 'pesan' => $pesan], $data));
    exit;
}

// CORS — izinkan akses dari GitHub Pages
$allowedOrigins = [
    'https://username.github.io',   // ← GANTI dengan URL GitHub Pages Anda
    'http://localhost',
    'http://127.0.0.1',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins) || str_contains($origin, 'github.io')) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *"); // sementara allow all
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
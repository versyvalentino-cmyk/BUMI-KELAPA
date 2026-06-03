<?php
require_once 'Config.php';
header('Content-Type: application/json; charset=utf-8');

$db = getDB();

$db->exec("
  CREATE TABLE IF NOT EXISTS keranjang (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    hp         VARCHAR(20) NOT NULL UNIQUE,
    isi        LONGTEXT    NOT NULL,
    updated_at DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$aksi = $_GET['aksi'] ?? '';

// GET: Ambil keranjang
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $aksi === 'ambil') {
    $hp = trim($_GET['hp'] ?? '');
    if (!$hp) { echo json_encode(['sukses'=>false]); exit; }

    $stmt = $db->prepare("SELECT isi FROM keranjang WHERE hp=?");
    $stmt->execute([$hp]);
    $row = $stmt->fetch();
    $isi = $row ? json_decode($row['isi'], true) : [];

    echo json_encode(['sukses'=>true, 'keranjang'=> $isi ?: []]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $hp   = trim($input['hp'] ?? '');
    $aksi = $input['aksi'] ?? $aksi;
    if (!$hp) { echo json_encode(['sukses'=>false]); exit; }

    // Ambil isi keranjang saat ini dari DB
    $stmt = $db->prepare("SELECT isi FROM keranjang WHERE hp=?");
    $stmt->execute([$hp]);
    $row  = $stmt->fetch();
    $isi  = $row ? (json_decode($row['isi'], true) ?: []) : [];

    if ($aksi === 'tambah') {
        $nama  = trim($input['nama_produk'] ?? '');
        $harga = intval($input['harga'] ?? 0);
        $qty   = intval($input['qty'] ?? 1);
        $jenis = trim($input['jenis'] ?? 'kelapa');

        $found = false;
        foreach ($isi as &$item) {
            if ($item['nama_produk'] === $nama) {
                $item['qty'] += $qty;
                $found = true; break;
            }
        }
        if (!$found) {
            $isi[] = ['nama_produk'=>$nama, 'harga'=>$harga, 'qty'=>$qty, 'jenis'=>$jenis];
        }

    } elseif ($aksi === 'hapus') {
        $nama = trim($input['nama_produk'] ?? '');
        $isi  = array_values(array_filter($isi, fn($k) => $k['nama_produk'] !== $nama));

    } elseif ($aksi === 'kosongkan') {
        $isi = [];
    }

    $upsert = $db->prepare("
        INSERT INTO keranjang (hp, isi) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE isi=VALUES(isi), updated_at=NOW()
    ");
    $upsert->execute([$hp, json_encode($isi, JSON_UNESCAPED_UNICODE)]);

    echo json_encode(['sukses'=>true]);
    exit;
}

echo json_encode(['sukses'=>false, 'pesan'=>'Aksi tidak dikenali']);
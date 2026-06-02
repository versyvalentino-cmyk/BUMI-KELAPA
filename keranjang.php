<?php
require_once 'Config.php';

$db = getDB();

// Buat tabel keranjang jika belum ada
$db->exec("CREATE TABLE IF NOT EXISTS keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hp VARCHAR(20) NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga INT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    jenis VARCHAR(50) DEFAULT 'kelapa',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unik_hp_produk (hp, nama_produk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$aksi = $_GET['aksi'] ?? ($_POST['aksi'] ?? '');

// ===== AMBIL KERANJANG =====
if ($aksi === 'ambil') {
    $hp = trim($_GET['hp'] ?? '');
    if (!$hp) jsonResponse(false, 'HP kosong.');
    $stmt = $db->prepare("SELECT * FROM keranjang WHERE hp = ? ORDER BY id ASC");
    $stmt->execute([$hp]);
    jsonResponse(true, 'OK', ['keranjang' => $stmt->fetchAll()]);
}

// ===== TAMBAH / UPDATE ITEM =====
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $hp   = trim($data['hp'] ?? '');
    $nama = trim($data['nama_produk'] ?? '');
    $harga = intval($data['harga'] ?? 0);
    $qty  = intval($data['qty'] ?? 1);
    $jenis = trim($data['jenis'] ?? 'kelapa');
    if (!$hp || !$nama) jsonResponse(false, 'Data tidak lengkap.');
    // Jika produk sudah ada, tambah qty-nya
    $stmt = $db->prepare("INSERT INTO keranjang (hp, nama_produk, harga, qty, jenis)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
    $stmt->execute([$hp, $nama, $harga, $qty, $jenis]);
    jsonResponse(true, 'Ditambahkan.');
}

// ===== HAPUS SATU ITEM =====
if ($aksi === 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $hp   = trim($data['hp'] ?? '');
    $nama = trim($data['nama_produk'] ?? '');
    if (!$hp || !$nama) jsonResponse(false, 'Data tidak lengkap.');
    $db->prepare("DELETE FROM keranjang WHERE hp = ? AND nama_produk = ?")->execute([$hp, $nama]);
    jsonResponse(true, 'Dihapus.');
}

// ===== KOSONGKAN KERANJANG (setelah checkout) =====
if ($aksi === 'kosongkan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $hp   = trim($data['hp'] ?? '');
    if (!$hp) jsonResponse(false, 'HP kosong.');
    $db->prepare("DELETE FROM keranjang WHERE hp = ?")->execute([$hp]);
    jsonResponse(true, 'Keranjang dikosongkan.');
}
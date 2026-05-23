<?php
require_once 'Config.php';
session_start();

$aksi = $_GET['aksi'] ?? ($_POST['aksi'] ?? '');

// ===== CEK SESSION =====
if ($aksi === 'cek') {
    if (isset($_SESSION['user'])) {
        jsonResponse(true, 'Login', ['user' => $_SESSION['user']]);
    } else {
        jsonResponse(false, 'Belum login');
    }
}

// ===== REGISTER / LOGIN =====
if ($aksi === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $hp   = trim($data['hp'] ?? '');
    $nama = trim($data['nama'] ?? '');

    if (!$hp) jsonResponse(false, 'Nomor HP wajib diisi.');

    $db   = getDB();

    // Buat tabel users jika belum ada
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100),
        hp VARCHAR(20) UNIQUE NOT NULL,
        kecamatan VARCHAR(100),
        kelurahan VARCHAR(100),
        alamat TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Cek apakah HP sudah terdaftar
    $stmt = $db->prepare("SELECT * FROM users WHERE hp = ?");
    $stmt->execute([$hp]);
    $user = $stmt->fetch();

    if ($user) {
        // Login — HP sudah ada
        $_SESSION['user'] = $user;
        jsonResponse(true, 'Login berhasil', ['user' => $user, 'mode' => 'login']);
    } else {
        // Register — HP baru, nama wajib
        if (!$nama) jsonResponse(false, 'Nama wajib diisi untuk pendaftaran.');
        $stmt = $db->prepare("INSERT INTO users (nama, hp) VALUES (?, ?)");
        $stmt->execute([$nama, $hp]);
        $id = $db->lastInsertId();
        $user = ['id'=>$id,'nama'=>$nama,'hp'=>$hp,'kecamatan'=>'','kelurahan'=>'','alamat'=>''];
        $_SESSION['user'] = $user;
        jsonResponse(true, 'Daftar berhasil', ['user' => $user, 'mode' => 'register']);
    }
}

// ===== UPDATE PROFIL =====
if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) jsonResponse(false, 'Belum login.');
    $data      = json_decode(file_get_contents('php://input'), true);
    $nama      = trim($data['nama'] ?? '');
    $kecamatan = trim($data['kecamatan'] ?? '');
    $kelurahan = trim($data['kelurahan'] ?? '');
    $alamat    = trim($data['alamat'] ?? '');
    $id        = $_SESSION['user']['id'];

    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET nama=?, kecamatan=?, kelurahan=?, alamat=? WHERE id=?");
    $stmt->execute([$nama, $kecamatan, $kelurahan, $alamat, $id]);

    $_SESSION['user'] = array_merge($_SESSION['user'], compact('nama','kecamatan','kelurahan','alamat'));
    jsonResponse(true, 'Profil diperbarui', ['user' => $_SESSION['user']]);
}

// ===== LOGOUT =====
if ($aksi === 'logout') {
    session_destroy();
    jsonResponse(true, 'Logout berhasil');
}
<?php
require_once 'Config.php';

$aksi = $_GET['aksi'] ?? ($_POST['aksi'] ?? '');

// ===== CEK SESSION =====
if ($aksi === 'cek') {
    jsonResponse(true, 'Server OK');
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
if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') 
    if ($aksi === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $hp        = trim($data['hp'] ?? '');
    $nama      = trim($data['nama'] ?? '');
    $kecamatan = trim($data['kecamatan'] ?? '');
    $kelurahan = trim($data['kelurahan'] ?? '');
    $alamat    = trim($data['alamat'] ?? '');

    if (!$hp) jsonResponse(false, 'HP tidak ditemukan.');

    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET nama=?, kecamatan=?, kelurahan=?, alamat=? WHERE hp=?");
    $stmt->execute([$nama, $kecamatan, $kelurahan, $alamat, $hp]);

    $stmt2 = $db->prepare("SELECT * FROM users WHERE hp=?");
    $stmt2->execute([$hp]);
    $user = $stmt2->fetch();

    jsonResponse(true, 'Profil diperbarui', ['user' => $user]);
}

// ===== LOGOUT =====
if ($aksi === 'logout') {
    session_destroy();
    jsonResponse(true, 'Logout berhasil');
}

// ===== AMBIL SEMUA ALAMAT =====
if ($aksi === 'alamat_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $hp = trim($_GET['hp'] ?? '');
    if (!$hp) jsonResponse(false, 'HP kosong.');
    $db = getDB();

    // Buat tabel jika belum ada
    $db->exec("CREATE TABLE IF NOT EXISTS alamat_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hp VARCHAR(20) NOT NULL,
        label VARCHAR(50) DEFAULT 'Rumah',
        nama_penerima VARCHAR(100),
        no_hp VARCHAR(20),
        kecamatan VARCHAR(100),
        kelurahan VARCHAR(100),
        alamat TEXT,
        is_utama TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("SELECT * FROM alamat_user WHERE hp=? ORDER BY is_utama DESC, id ASC");
    $stmt->execute([$hp]);
    jsonResponse(true, 'OK', ['alamat' => $stmt->fetchAll()]);
}

// ===== SIMPAN ALAMAT BARU =====
if ($aksi === 'alamat_simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS alamat_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hp VARCHAR(20) NOT NULL,
        label VARCHAR(50) DEFAULT 'Rumah',
        nama_penerima VARCHAR(100),
        no_hp VARCHAR(20),
        kecamatan VARCHAR(100),
        kelurahan VARCHAR(100),
        alamat TEXT,
        is_utama TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $data          = json_decode(file_get_contents('php://input'), true);
    $hp            = trim($data['hp'] ?? '');
    $label         = trim($data['label'] ?? 'Rumah');
    $nama_penerima = trim($data['nama_penerima'] ?? '');
    $no_hp         = trim($data['no_hp'] ?? '');
    $kecamatan     = trim($data['kecamatan'] ?? '');
    $kelurahan     = trim($data['kelurahan'] ?? '');
    $alamat        = trim($data['alamat'] ?? '');
    $is_utama      = intval($data['is_utama'] ?? 0);

    if (!$hp || !$nama_penerima || !$alamat) jsonResponse(false, 'Data tidak lengkap.');

    $db = getDB();

    // Jika is_utama, reset semua dulu
    if ($is_utama) {
        $db->prepare("UPDATE alamat_user SET is_utama=0 WHERE hp=?")->execute([$hp]);
    }

    $stmt = $db->prepare("INSERT INTO alamat_user (hp,label,nama_penerima,no_hp,kecamatan,kelurahan,alamat,is_utama) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$hp,$label,$nama_penerima,$no_hp,$kecamatan,$kelurahan,$alamat,$is_utama]);
    jsonResponse(true, 'Alamat disimpan', ['id' => $db->lastInsertId()]);
}

// ===== HAPUS ALAMAT =====
if ($aksi === 'alamat_hapus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = intval($data['id'] ?? 0);
    $hp   = trim($data['hp'] ?? '');
    if (!$id || !$hp) jsonResponse(false, 'Data tidak lengkap.');
    $db = getDB();
    $db->prepare("DELETE FROM alamat_user WHERE id=? AND hp=?")->execute([$id, $hp]);
    jsonResponse(true, 'Alamat dihapus');
}

// ===== SET UTAMA =====
if ($aksi === 'alamat_utama' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = intval($data['id'] ?? 0);
    $hp   = trim($data['hp'] ?? '');
    if (!$id || !$hp) jsonResponse(false, 'Data tidak lengkap.');
    $db = getDB();
    $db->prepare("UPDATE alamat_user SET is_utama=0 WHERE hp=?")->execute([$hp]);
    $db->prepare("UPDATE alamat_user SET is_utama=1 WHERE id=? AND hp=?")->execute([$id, $hp]);
    jsonResponse(true, 'Alamat utama diperbarui');
}
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// =============================================
// KONFIGURASI DATABASE — sesuaikan di sini
// =============================================
$host   = 'localhost';
$dbname = 'bumi_kelapa';
$user   = 'root';
$pass   = '';
$port   = 3306;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sukses' => false, 'pesan' => 'Method tidak diizinkan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$nama               = trim($data['nama'] ?? '');
$hp                 = trim($data['hp'] ?? '');
$kecamatan          = trim($data['kecamatan'] ?? '');
$kelurahan          = trim($data['kelurahan'] ?? '');
$alamat             = trim($data['alamat'] ?? '');
$total_qty          = intval($data['total_qty'] ?? 1);
$harga_barang       = intval($data['harga_barang'] ?? 0);
$ongkir             = intval($data['ongkir'] ?? 0);
$total_bayar        = intval($data['total_bayar'] ?? 0);
$metode_pembayaran  = trim($data['metode_pembayaran'] ?? '');
$bank               = trim($data['bank'] ?? '');
$ewallet            = trim($data['ewallet'] ?? '');
$nama_produk        = trim($data['nama_produk'] ?? '');

if (!$nama || !$hp || !$kecamatan || !$kelurahan || !$alamat || !$nama_produk) {
    echo json_encode(['sukses' => false, 'pesan' => 'Data tidak lengkap.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Buat tabel jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pesanan (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            hp VARCHAR(20) NOT NULL,
            kecamatan VARCHAR(100),
            kelurahan VARCHAR(100),
            alamat TEXT,
            nama_produk TEXT,
            total_qty INT DEFAULT 1,
            harga_barang INT DEFAULT 0,
            ongkir INT DEFAULT 0,
            total_bayar INT DEFAULT 0,
            metode_pembayaran VARCHAR(50),
            bank VARCHAR(50),
            ewallet VARCHAR(50),
            status VARCHAR(50) DEFAULT 'menunggu_konfirmasi',
            tanggal DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    // Tambah kolom jika belum ada (untuk tabel yang sudah ada)
    $cols = $pdo->query("SHOW COLUMNS FROM pesanan")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('nama_produk', $cols)) {
        $pdo->exec("ALTER TABLE pesanan ADD COLUMN nama_produk TEXT AFTER alamat");
    }
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE pesanan ADD COLUMN status VARCHAR(50) DEFAULT 'menunggu_konfirmasi' AFTER ewallet");
    }

    $stmt = $pdo->prepare("
        INSERT INTO pesanan
            (nama, hp, kecamatan, kelurahan, alamat, nama_produk, total_qty,
             harga_barang, ongkir, total_bayar, metode_pembayaran, bank, ewallet, status)
        VALUES
            (:nama, :hp, :kecamatan, :kelurahan, :alamat, :nama_produk, :total_qty,
             :harga_barang, :ongkir, :total_bayar, :metode_pembayaran, :bank, :ewallet, 'menunggu_konfirmasi')
    ");

    $stmt->execute(compact(
        'nama','hp','kecamatan','kelurahan','alamat','nama_produk','total_qty',
        'harga_barang','ongkir','total_bayar','metode_pembayaran','bank','ewallet'
    ));

    $id_pesanan = $pdo->lastInsertId();

    echo json_encode([
        'sukses'     => true,
        'id_pesanan' => $id_pesanan,
        'pesan'      => 'Pesanan berhasil disimpan!'
    ]);

} catch (PDOException $e) {
    echo json_encode(['sukses' => false, 'pesan' => 'Database error: ' . $e->getMessage()]);
}
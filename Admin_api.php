<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$host   = getenv('MYSQLHOST')     ?: 'mysql.railway.internal';
$dbname = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$user   = getenv('MYSQLUSER')     ?: 'root';
$pass   = getenv('MYSQLPASSWORD') ?: 'UWfVrQgJWXzFZXbwxiaBDwbqrEozpAdV';
$port   = intval(getenv('MYSQLPORT') ?: '3306');

try {
    $dsn  = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo  = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['sukses'=>false,'pesan'=>'Koneksi gagal: '.$e->getMessage()]);
    exit;
}

$statusValid = ['menunggu_konfirmasi','diproses','dikirim','tiba','dibatalkan'];
$aksi = $_GET['aksi'] ?? '';

if ($aksi === 'list') {
    $stmt = $pdo->query("
        SELECT id, nama, hp, kecamatan, kelurahan, alamat,
               nama_produk, total_qty, harga_barang, ongkir, total_bayar,
               metode_pembayaran, bank, ewallet, status, tanggal
        FROM pesanan ORDER BY tanggal DESC LIMIT 500
    ");
    echo json_encode(['sukses'=>true,'pesanan'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $aksiPost = $body['aksi'] ?? '';

    if ($aksiPost === 'update_status') {
        $id     = intval($body['id'] ?? 0);
        $status = trim($body['status'] ?? '');

        if (!$id) { echo json_encode(['sukses'=>false,'pesan'=>'ID tidak valid.']); exit; }
        if (!in_array($status, $statusValid)) { echo json_encode(['sukses'=>false,'pesan'=>'Status tidak valid.']); exit; }

        $stmt = $pdo->prepare("UPDATE pesanan SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);

        if ($stmt->rowCount() === 0) { echo json_encode(['sukses'=>false,'pesan'=>'Pesanan tidak ditemukan.']); exit; }
        echo json_encode(['sukses'=>true,'pesan'=>'Status berhasil diperbarui.']);
        exit;
    }
}

echo json_encode(['sukses'=>false,'pesan'=>'Aksi tidak dikenali.']);
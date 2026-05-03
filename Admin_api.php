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

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    echo json_encode(['sukses'=>false,'pesan'=>'Koneksi gagal: '.mysqli_connect_error()]);
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

$statusValid = ['menunggu_konfirmasi','diproses','dikirim','tiba','dibatalkan'];
$aksi = $_GET['aksi'] ?? '';

if ($aksi === 'list') {
    $result = mysqli_query($conn, "
        SELECT id, nama, hp, kecamatan, kelurahan, alamat,
               nama_produk, total_qty, harga_barang, ongkir, total_bayar,
               metode_pembayaran, bank, ewallet, status, tanggal
        FROM pesanan
        ORDER BY tanggal DESC
        LIMIT 500
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    echo json_encode(['sukses'=>true,'pesanan'=>$rows]);
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

        $stmt = mysqli_prepare($conn, "UPDATE pesanan SET status=? WHERE id=?");
        mysqli_bind_param($stmt, 'si', $status, $id);
        mysqli_execute($stmt);

        if (mysqli_affected_rows($conn) === 0) {
            echo json_encode(['sukses'=>false,'pesan'=>'Pesanan tidak ditemukan.']);
            exit;
        }
        echo json_encode(['sukses'=>true,'pesan'=>'Status berhasil diperbarui.']);
        exit;
    }
}

echo json_encode(['sukses'=>false,'pesan'=>'Aksi tidak dikenali.']);
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// =============================================
// KONFIGURASI DATABASE — sesuaikan di sini
// =============================================
$host   = 'localhost';
$dbname = 'bumi_kelapa';
$user   = 'root';
$pass   = '';
$port   = 3306;

// =============================================
// KEAMANAN SEDERHANA — ganti dengan password Anda
// Uncomment dan isi jika ingin proteksi password:
// =============================================
// $adminPass = 'rahasia123';
// $reqPass   = $_GET['pass'] ?? $_POST['pass'] ?? '';
// if ($reqPass !== $adminPass) {
//     echo json_encode(['sukses'=>false,'pesan'=>'Akses ditolak.']);
//     exit;
// }

$statusValid = ['menunggu_konfirmasi','diproses','dikirim','tiba','dibatalkan'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $aksi = $_GET['aksi'] ?? '';

    // ── GET: daftar semua pesanan ──────────────────────────
    if ($aksi === 'list') {
        $stmt = $pdo->query("
            SELECT id, nama, hp, kecamatan, kelurahan, alamat,
                   nama_produk, total_qty, harga_barang, ongkir, total_bayar,
                   metode_pembayaran, bank, ewallet, status, tanggal
            FROM pesanan
            ORDER BY tanggal DESC
            LIMIT 500
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['sukses' => true, 'pesanan' => $rows]);
        exit;
    }

    // ── POST: update status ────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);
        $aksiPost = $body['aksi'] ?? '';

        if ($aksiPost === 'update_status') {
            $id     = intval($body['id'] ?? 0);
            $status = trim($body['status'] ?? '');

            if (!$id) {
                echo json_encode(['sukses' => false, 'pesan' => 'ID tidak valid.']);
                exit;
            }
            if (!in_array($status, $statusValid)) {
                echo json_encode(['sukses' => false, 'pesan' => 'Status tidak valid.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE pesanan SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['sukses' => false, 'pesan' => 'Pesanan tidak ditemukan.']);
                exit;
            }

            echo json_encode(['sukses' => true, 'pesan' => 'Status berhasil diperbarui.']);
            exit;
        }
    }

    echo json_encode(['sukses' => false, 'pesan' => 'Aksi tidak dikenali.']);

} catch (PDOException $e) {
    echo json_encode(['sukses' => false, 'pesan' => 'Database error: ' . $e->getMessage()]);
}
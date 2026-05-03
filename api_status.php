<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// =============================================
// KONFIGURASI DATABASE — sesuaikan di sini
// =============================================
$host   = getenv('MYSQLHOST')     ?: 'mysql.railway.internal';
$dbname = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
$user   = getenv('MYSQLUSER')     ?: 'root';
$pass   = getenv('MYSQLPASSWORD') ?: 'UWfVrQgJWXzFZXbwxiaBDwbqrEozpAdV';
$port   = intval(getenv('MYSQLPORT') ?: '3306');

$id = intval($_GET['id'] ?? 0);
$hp = trim($_GET['hp'] ?? '');

if (!$id || !$hp) {
    echo json_encode(['sukses' => false, 'pesan' => 'Parameter tidak lengkap.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id = :id AND hp = :hp LIMIT 1");
    $stmt->execute(['id' => $id, 'hp' => $hp]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['sukses' => false, 'pesan' => 'Pesanan tidak ditemukan. Periksa ID dan nomor HP.']);
        exit;
    }

    // Mapping status ke label & deskripsi
    $statusMap = [
        'menunggu_konfirmasi' => [
            'label' => 'Menunggu Konfirmasi',
            'desc'  => 'Pesanan Anda sedang diverifikasi oleh penjual.',
            'step'  => 1,
            'warna' => '#F59E0B'
        ],
        'diproses' => [
            'label' => 'Sedang Diproses',
            'desc'  => 'Pesanan Anda sedang disiapkan dan dikemas.',
            'step'  => 2,
            'warna' => '#3B82F6'
        ],
        'dikirim' => [
            'label' => 'Sedang Dalam Perjalanan',
            'desc'  => 'Pesanan Anda sedang dalam perjalanan menuju alamat Anda.',
            'step'  => 3,
            'warna' => '#8B5CF6'
        ],
        'tiba' => [
            'label' => 'Telah Tiba',
            'desc'  => 'Pesanan Anda telah tiba di tujuan. Terima kasih telah berbelanja!',
            'step'  => 4,
            'warna' => '#10B981'
        ],
        'dibatalkan' => [
            'label' => 'Dibatalkan',
            'desc'  => 'Pesanan ini telah dibatalkan.',
            'step'  => 0,
            'warna' => '#EF4444'
        ],
    ];

    $status     = $row['status'] ?? 'menunggu_konfirmasi';
    $statusInfo = $statusMap[$status] ?? $statusMap['menunggu_konfirmasi'];

    echo json_encode([
        'sukses'   => true,
        'pesanan'  => [
            'id'                 => $row['id'],
            'nama'               => $row['nama'],
            'hp'                 => $row['hp'],
            'kecamatan'          => $row['kecamatan'],
            'kelurahan'          => $row['kelurahan'],
            'alamat'             => $row['alamat'],
            'nama_produk'        => $row['nama_produk'],
            'total_qty'          => $row['total_qty'],
            'harga_barang'       => $row['harga_barang'],
            'ongkir'             => $row['ongkir'],
            'total_bayar'        => $row['total_bayar'],
            'metode_pembayaran'  => $row['metode_pembayaran'],
            'bank'               => $row['bank'],
            'ewallet'            => $row['ewallet'],
            'status'             => $status,
            'status_label'       => $statusInfo['label'],
            'status_desc'        => $statusInfo['desc'],
            'status_step'        => $statusInfo['step'],
            'status_warna'       => $statusInfo['warna'],
            'tanggal'            => $row['tanggal'],
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['sukses' => false, 'pesan' => 'Database error: ' . $e->getMessage()]);
}
<?php
require_once 'Config.php';
header('Content-Type: application/json; charset=utf-8');

define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: '');
define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: '');
define('MIDTRANS_IS_PRODUCTION', false); // false = sandbox, true = production

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$aksi  = $input['aksi'] ?? $_GET['aksi'] ?? '';

// ---- Buat transaksi ----
if ($aksi === 'buat_transaksi') {
    $order_id   = 'BK-' . time() . '-' . rand(100,999);
    $total      = intval($input['total'] ?? 0);
    $nama       = trim($input['nama'] ?? 'Pembeli');
    $hp         = trim($input['hp'] ?? '');
    $produk     = trim($input['produk'] ?? 'Produk Bumi Kelapa');

    if ($total <= 0) { echo json_encode(['sukses'=>false,'pesan'=>'Total tidak valid']); exit; }

    $params = [
        'transaction_details' => [
            'order_id'     => $order_id,
            'gross_amount' => $total,
        ],
        'customer_details' => [
            'first_name' => $nama,
            'phone'      => $hp,
        ],
        'item_details' => [[
            'id'       => 'PRODUK-01',
            'price'    => $total,
            'quantity' => 1,
            'name'     => substr($produk, 0, 50),
        ]],
        'enabled_payments' => [
            'gopay','shopeepay','dana','ovo','linkaja',
            'bank_transfer','bca_va','bni_va','bri_va','mandiri_bill',
            'qris','cstore','alfamart','indomaret'
        ],
    ];

    $base_url = MIDTRANS_IS_PRODUCTION
        ? 'https://app.midtrans.com/snap/v1/transactions'
        : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (isset($data['token'])) {
        echo json_encode(['sukses'=>true, 'token'=>$data['token'], 'order_id'=>$order_id]);
    } else {
        echo json_encode(['sukses'=>false, 'pesan'=>$data['error_messages'][0] ?? 'Gagal buat transaksi']);
    }
    exit;
}

echo json_encode(['sukses'=>false, 'pesan'=>'Aksi tidak dikenali']);

<?php
error_reporting(0);

$conn = mysqli_connect("localhost", "root", "", "bumi_kelapa");

$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo "<pre>";
var_dump($data);
die();
    echo "data_kosong";
    exit;
}

$nama = $data['nama'];
$hp = $data['hp'];
$alamat = $data['alamat'];
$kecamatan = $data['kecamatan'];
$kelurahan = $data['kelurahan'];
$metode = $data['metode'];
$ongkir = $data['ongkir'];
$produk = $data['produk'];

// ✅ HITUNG SENDIRI (INI KUNCI)
$harga_barang = 0;
$total_qty = 0;

foreach ($produk as $item) {
    $harga_barang += $item['harga'] * $item['qty'];
    $total_qty += $item['qty'];
}

$total_bayar = $harga_barang + $ongkir;

// simpan pesanan
$query = "INSERT INTO pesanan 
(nama, hp, alamat, kecamatan, kelurahan, metode_pembayaran, harga_barang, ongkir, total_bayar, total_qty)
VALUES 
('$nama','$hp','$alamat','$kecamatan','$kelurahan','$metode','$harga_barang','$ongkir','$total_bayar','$total_qty')";

mysqli_query($conn, $query);

$id_pesanan = mysqli_insert_id($conn);

// simpan detail
foreach ($produk as $item) {

    $nama_produk = $item['nama'];
    $harga = $item['harga'];
    $qty = $item['qty'];

    mysqli_query($conn, "INSERT INTO detail_pesanan 
    (id_pesanan, nama_produk, harga, qty)
    VALUES 
    ($id_pesanan, '$nama_produk', $harga, $qty)");
}

echo "sukses";
?>

$bank = $data['bank'] ?? '';
$ewallet = $data['ewallet'] ?? '';
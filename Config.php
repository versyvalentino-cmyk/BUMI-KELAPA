<?php
define('DB_HOST',    getenv('MYSQLHOST'));
define('DB_NAME',    getenv('MYSQL_DATABASE'));
define('DB_USER',    getenv('MYSQLUSER'));
define('DB_PASS',    getenv('MYSQLPASSWORD'));
define('DB_PORT',    getenv('MYSQLPORT') ?: '3306');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['sukses'=>false,'pesan'=>'DB error: '.$e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

function generateKodePesanan() {
    $db=getDB();
    $tgl=date('Ymd');
    $prefix="BK-{$tgl}-";
    $stmt=$db->prepare("SELECT COUNT(*) FROM pesanan WHERE kode_pesanan LIKE ?");
    $stmt->execute([$prefix.'%']);
    return $prefix.str_pad((int)$stmt->fetchColumn()+1,3,'0',STR_PAD_LEFT);
}

function jsonResponse($sukses,$pesan,$data=[]) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['sukses'=>$sukses,'pesan'=>$pesan],$data));
    exit;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD']==='OPTIONS') exit;
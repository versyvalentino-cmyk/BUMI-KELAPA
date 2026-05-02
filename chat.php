<?php
// ============================================================
// chat.php — Backend Chat Bumi Kelapa (VERSI GRATIS - Tanpa AI)
// Chatbot menjawab berdasarkan kata kunci
// ============================================================

require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$db   = getDB();
$aksi = $_GET['aksi'] ?? '';
if (!$aksi && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $aksi = $body['aksi'] ?? '';
}

// ---- Buat tabel jika belum ada ----
$db->exec("
  CREATE TABLE IF NOT EXISTS chat_sesi (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    session_id   VARCHAR(64) NOT NULL UNIQUE,
    nama_pembeli VARCHAR(100) DEFAULT 'Tamu',
    hp_pembeli   VARCHAR(20)  DEFAULT '',
    status       ENUM('aktif','eskalasi','selesai') DEFAULT 'aktif',
    mode         ENUM('bot','eskalasi','admin') DEFAULT 'bot',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$db->exec("
  CREATE TABLE IF NOT EXISTS chat_pesan (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    pengirim   ENUM('pembeli','admin','bot') NOT NULL,
    pesan      TEXT NOT NULL,
    sudah_baca TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_baca (sudah_baca)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
try { $db->exec("ALTER TABLE chat_sesi ADD COLUMN mode ENUM('bot','eskalasi','admin') DEFAULT 'bot' AFTER status"); } catch(Exception $e){}

// ============================================================
// HELPER
// ============================================================
function simpanPesan($db, $sid, $pengirim, $pesan) {
    $stmt = $db->prepare("INSERT INTO chat_pesan (session_id, pengirim, pesan) VALUES (?,?,?)");
    $stmt->execute([$sid, $pengirim, $pesan]);
    return (int)$db->lastInsertId();
}

function buatAtauAmbilSesi($db, $sid, $nama = 'Tamu', $hp = '') {
    $cek = $db->prepare("SELECT id, mode FROM chat_sesi WHERE session_id=?");
    $cek->execute([$sid]);
    $row = $cek->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $db->prepare("INSERT INTO chat_sesi (session_id, nama_pembeli, hp_pembeli, mode) VALUES (?,?,?,'bot')")
           ->execute([$sid, $nama ?: 'Tamu', $hp]);
        return 'bot';
    }
    return $row['mode'] ?? 'bot';
}

// ============================================================
// CHATBOT GRATIS — Jawaban berdasarkan kata kunci
// ============================================================
function getChatbotReply($pesan, $nama) {
    $p = strtolower(trim($pesan));
    $sapa = $nama && $nama !== 'Tamu' ? "Kak $nama" : "Kak";

    // ---- Kata kunci eskalasi ke admin ----
    $kataEskalasi = ['admin','operator','manusia','orang','staff','cs',
        'customer service','komplen','komplain','keluhan','bicara langsung',
        'sambungkan','hubungi admin','minta admin','tolong admin','panggil admin'];
    foreach ($kataEskalasi as $k) {
        if (strpos($p, $k) !== false) {
            return [
                'teks'     => "Baik $sapa, saya hubungkan ke admin kami ya! Mohon tunggu sebentar 🙏",
                'eskalasi' => true
            ];
        }
    }

    // ---- Sapaan ----
    if (preg_match('/^(halo|hai|hi|hey|selamat|assalam|hei|p{1,3}|hallo)/i', $p)) {
        return ['teks' => "Halo $sapa! 👋 Selamat datang di *Bumi Kelapa*. Ada yang bisa Koko bantu hari ini? 😊", 'eskalasi' => false];
    }

    // ---- Terima kasih ----
    if (preg_match('/(terima kasih|makasih|thanks|thank you|tq|thx)/i', $p)) {
        return ['teks' => "Sama-sama $sapa! 😊 Senang bisa membantu. Kalau ada pertanyaan lain jangan ragu ya! 🌴", 'eskalasi' => false];
    }

    // ---- Harga kelapa ----
    if (preg_match('/(harga|berapa).*(kelapa|buah)/i', $p) || preg_match('/(kelapa).*(harga|berapa)/i', $p)) {
        return ['teks' => "Harga kelapa sudah dikupas Rp 5.000/buah $sapa 🥥\nStok saat ini: 300 buah. Mau pesan berapa buah?", 'eskalasi' => false];
    }

    // ---- Harga sabut ----
    if (preg_match('/(harga|berapa).*(sabut)/i', $p) || preg_match('/(sabut).*(harga|berapa)/i', $p)) {
        return ['teks' => "Harga Sabut Kelapa Rp 25.000/karung $sapa 🌿\nStok: 10 karung. Mau pesan?", 'eskalasi' => false];
    }

    // ---- Harga tempurung ----
    if (preg_match('/(harga|berapa).*(tempurung)/i', $p) || preg_match('/(tempurung).*(harga|berapa)/i', $p)) {
        return ['teks' => "Harga Tempurung Kelapa Rp 50.000/karung $sapa\nStok: 5 karung. Tertarik? 😊", 'eskalasi' => false];
    }

    // ---- Harga paket ----
    if (preg_match('/(harga|berapa).*(paket)/i', $p) || preg_match('/(paket).*(harga|berapa)/i', $p)) {
        return ['teks' => "Paket Karung harganya Rp 500.000/karung $sapa 📦\nIsi 100 buah kelapa per karung. Hemat banget untuk grosir!", 'eskalasi' => false];
    }

    // ---- Semua harga / daftar produk ----
    if (preg_match('/(daftar|list|produk|semua|apa saja|ada apa|jual apa)/i', $p)) {
        return ['teks' =>
            "Produk Bumi Kelapa $sapa: 🌴\n\n" .
            "1. 🥥 Kelapa dikupas — Rp 5.000/buah (300 buah)\n" .
            "2. 🌿 Sabut Kelapa — Rp 25.000/karung (10 karung)\n" .
            "3. 🪵 Tempurung Kelapa — Rp 50.000/karung (5 karung)\n" .
            "4. 📦 Paket Karung — Rp 500.000/karung (isi 100 buah)\n\n" .
            "Mau pesan yang mana?",
            'eskalasi' => false];
    }

    // ---- Stok ----
    if (preg_match('/(stok|stock|tersedia|masih ada)/i', $p)) {
        return ['teks' =>
            "Stok tersedia $sapa:\n" .
            "🥥 Kelapa dikupas: 300 buah\n" .
            "🌿 Sabut Kelapa: 10 karung\n" .
            "🪵 Tempurung Kelapa: 5 karung\n" .
            "📦 Paket Karung: tersedia\n\n" .
            "Silakan pesan sebelum kehabisan! 😊",
            'eskalasi' => false];
    }

    // ---- Cara pesan ----
    if (preg_match('/(cara|gimana|bagaimana|langkah).*(pesan|order|beli)/i', $p) || preg_match('/(pesan|order|beli).*(gimana|bagaimana|cara)/i', $p)) {
        return ['teks' =>
            "Cara pesan di Bumi Kelapa $sapa:\n\n" .
            "1️⃣ Klik tombol *Pesan* di produk pilihan\n" .
            "2️⃣ Isi nama, kecamatan, kelurahan & alamat\n" .
            "3️⃣ Ongkir otomatis dihitung\n" .
            "4️⃣ Pilih metode bayar\n" .
            "5️⃣ Catat ID pesanan untuk lacak status\n\n" .
            "Mudah kan? 😊",
            'eskalasi' => false];
    }

    // ---- Ongkir / pengiriman ----
    if (preg_match('/(ongkir|ongkos|kirim|pengiriman|antar|delivery)/i', $p)) {
        return ['teks' =>
            "Pengiriman Bumi Kelapa $sapa:\n\n" .
            "🏍️ Motor: maks 30 buah kelapa / 5 karung\n" .
            "🚛 Pickup: untuk jumlah lebih besar\n\n" .
            "Ongkir dihitung otomatis berdasarkan kelurahan tujuan saat kamu pesan. Melayani seluruh Kota Ambon 📍",
            'eskalasi' => false];
    }

    // ---- Area pengiriman ----
    if (preg_match('/(area|wilayah|daerah|jangkauan|sampai|bisa kirim)/i', $p)) {
        return ['teks' =>
            "Kami melayani seluruh Kota Ambon $sapa 📍\n\n" .
            "✅ Nusaniwe\n✅ Sirimau\n✅ Teluk Ambon\n✅ Baguala\n✅ Leitimur Selatan\n\n" .
            "Ongkir berbeda tiap kelurahan, dihitung otomatis saat pesan.",
            'eskalasi' => false];
    }

    // ---- Lacak pesanan ----
    if (preg_match('/(lacak|track|cek|status).*(pesanan|order|paket)/i', $p) || preg_match('/(pesanan|order).*(lacak|track|cek|mana|status)/i', $p)) {
        return ['teks' =>
            "Untuk lacak pesanan $sapa:\n\n" .
            "1️⃣ Klik *Lacak Pesanan* di header website\n" .
            "2️⃣ Masukkan ID Pesanan + Nomor HP\n" .
            "3️⃣ Status pesanan langsung tampil 📦\n\n" .
            "ID pesanan dikirim setelah kamu berhasil checkout.",
            'eskalasi' => false];
    }

    // ---- Pembayaran ----
    if (preg_match('/(bayar|pembayaran|transfer|cod|ewallet|gopay|ovo|dana|shopee|qris)/i', $p)) {
        return ['teks' =>
            "Metode pembayaran yang tersedia $sapa:\n\n" .
            "💚 GoPay  💜 OVO  💙 DANA\n" .
            "🛍️ ShopeePay  🔴 LinkAja\n" .
            "📱 QRIS (semua bank & e-wallet)\n" .
            "🏦 Transfer Bank (BCA/Mandiri/BNI/BRI/BSI)\n" .
            "🤝 COD (bayar di tempat)\n\n" .
            "Pilih yang paling nyaman! 😊",
            'eskalasi' => false];
    }

    // ---- Lokasi toko ----
    if (preg_match('/(lokasi|alamat|dimana|toko|tempat|where)/i', $p)) {
        return ['teks' =>
            "Bumi Kelapa berlokasi di:\n📍 Latuhalat, Dusun Omputty, Kota Ambon\n\n" .
            "Kami melayani pengiriman ke seluruh Kota Ambon. Tidak perlu datang ke toko, pesan online saja! 😊",
            'eskalasi' => false];
    }

    // ---- Jam operasional ----
    if (preg_match('/(jam|buka|tutup|operasional|waktu|open)/i', $p)) {
        return ['teks' =>
            "Bumi Kelapa buka setiap hari $sapa ⏰\n\n" .
            "🕗 Senin - Sabtu: 07.00 - 17.00 WIT\n" .
            "🕗 Minggu: 08.00 - 14.00 WIT\n\n" .
            "Pesan bisa kapan saja, pengiriman sesuai jam operasional 😊",
            'eskalasi' => false];
    }

    // ---- Default / tidak dikenali ----
    return ['teks' =>
        "Halo $sapa! 😊 Maaf, Koko kurang mengerti pertanyaannya.\n\n" .
        "Koko bisa bantu info tentang:\n" .
        "• 🥥 Harga & stok produk\n" .
        "• 🚚 Ongkir & area pengiriman\n" .
        "• 📦 Cara pesan & lacak pesanan\n" .
        "• 💳 Metode pembayaran\n\n" .
        "Atau ketik *hubungi admin* untuk bicara langsung dengan admin kami 🙏",
        'eskalasi' => false];
}

// ============================================================
// GET: Ambil pesan
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $aksi === 'ambil') {
    $sid   = trim($_GET['session_id'] ?? '');
    $sejak = (int)($_GET['sejak_id'] ?? 0);
    if (!$sid) { echo json_encode(['sukses'=>false,'pesan'=>'session_id kosong']); exit; }

    buatAtauAmbilSesi($db, $sid, trim($_GET['nama'] ?? 'Tamu'), trim($_GET['hp'] ?? ''));

    $stmt = $db->prepare("
        SELECT id, pengirim, pesan, sudah_baca,
               DATE_FORMAT(created_at,'%H:%i') AS waktu
        FROM chat_pesan WHERE session_id=? AND id>?
        ORDER BY created_at ASC LIMIT 100
    ");
    $stmt->execute([$sid, $sejak]);
    $pesan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sesiStmt = $db->prepare("SELECT mode, status FROM chat_sesi WHERE session_id=?");
    $sesiStmt->execute([$sid]);
    $sesiData = $sesiStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'sukses' => true,
        'pesan'  => $pesan,
        'mode'   => $sesiData['mode']   ?? 'bot',
        'status' => $sesiData['status'] ?? 'aktif'
    ]);
    exit;
}

// ============================================================
// GET: Daftar sesi untuk admin
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $aksi === 'daftar_sesi') {
    $stmt = $db->query("
        SELECT s.session_id, s.nama_pembeli, s.hp_pembeli, s.status, s.mode, s.updated_at,
          (SELECT COUNT(*) FROM chat_pesan p
           WHERE p.session_id=s.session_id AND p.pengirim='pembeli' AND p.sudah_baca=0) AS belum_baca,
          (SELECT pesan FROM chat_pesan p
           WHERE p.session_id=s.session_id ORDER BY p.created_at DESC LIMIT 1) AS pesan_terakhir,
          (SELECT DATE_FORMAT(created_at,'%H:%i') FROM chat_pesan p
           WHERE p.session_id=s.session_id ORDER BY p.created_at DESC LIMIT 1) AS waktu_terakhir
        FROM chat_sesi s ORDER BY s.updated_at DESC LIMIT 200
    ");
    echo json_encode(['sukses'=>true,'sesi'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ============================================================
// GET: Jumlah belum baca
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $aksi === 'jumlah_belum_baca') {
    $stmt = $db->query("SELECT COUNT(*) FROM chat_pesan WHERE pengirim='pembeli' AND sudah_baca=0");
    echo json_encode(['sukses'=>true,'jumlah'=>(int)$stmt->fetchColumn()]);
    exit;
}

// ============================================================
// POST: Kirim pesan dari pembeli
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'kirim') {
    $input    = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid      = trim($input['session_id'] ?? '');
    $pengirim = trim($input['pengirim']   ?? 'pembeli');
    $pesan    = trim($input['pesan']      ?? '');
    $nama     = trim($input['nama']       ?? 'Tamu');
    $hp       = trim($input['hp']         ?? '');

    if (!$sid || !$pesan) { echo json_encode(['sukses'=>false,'pesan'=>'Data tidak lengkap']); exit; }

    $mode = buatAtauAmbilSesi($db, $sid, $nama, $hp);

    // Update nama & hp
    $db->prepare("UPDATE chat_sesi SET nama_pembeli=?, hp_pembeli=?, updated_at=NOW() WHERE session_id=?")
       ->execute([$nama ?: 'Tamu', $hp, $sid]);

    // Simpan pesan pembeli
    $idBaru = simpanPesan($db, $sid, $pengirim, htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    $balasanBot  = null;
    $eskalasiBot = false;

    // Auto-reply bot hanya jika mode = 'bot'
    if ($pengirim === 'pembeli' && $mode === 'bot') {
        $hasil      = getChatbotReply($pesan, $nama);
        $balasanBot = $hasil['teks'];
        $eskalasiBot = $hasil['eskalasi'];

        // Simpan balasan bot
        simpanPesan($db, $sid, 'bot', $balasanBot);

        // Jika perlu eskalasi
        if ($eskalasiBot) {
            $db->prepare("UPDATE chat_sesi SET mode='eskalasi', status='eskalasi', updated_at=NOW() WHERE session_id=?")
               ->execute([$sid]);
        }
    }

    echo json_encode([
        'sukses'      => true,
        'id'          => $idBaru,
        'balasan_bot' => $balasanBot,
        'eskalasi'    => $eskalasiBot,
        'mode'        => $mode
    ]);
    exit;
}

// ============================================================
// POST: Eskalasi manual (tombol "Hubungi Admin")
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'eskalasi') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid   = trim($input['session_id'] ?? '');
    if (!$sid) { echo json_encode(['sukses'=>false,'pesan'=>'session_id kosong']); exit; }

    buatAtauAmbilSesi($db, $sid);
    $db->prepare("UPDATE chat_sesi SET mode='eskalasi', status='eskalasi', updated_at=NOW() WHERE session_id=?")
       ->execute([$sid]);

    $teks   = '⚡ Permintaan disambungkan ke admin. Admin akan segera membalas! Mohon tunggu ya Kak 🙏';
    $idBaru = simpanPesan($db, $sid, 'bot', $teks);

    echo json_encode(['sukses'=>true,'id'=>$idBaru,'pesan'=>$teks]);
    exit;
}

// ============================================================
// POST: Admin balas pesan
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'balas_admin') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid   = trim($input['session_id'] ?? '');
    $pesan = trim($input['pesan']      ?? '');
    if (!$sid || !$pesan) { echo json_encode(['sukses'=>false,'pesan'=>'Data tidak lengkap']); exit; }

    $db->prepare("UPDATE chat_sesi SET mode='admin', updated_at=NOW() WHERE session_id=?")->execute([$sid]);
    $id = simpanPesan($db, $sid, 'admin', htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    echo json_encode(['sukses'=>true,'id'=>(int)$id]);
    exit;
}

// ============================================================
// POST: Tandai sudah dibaca
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'tandai_baca') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $sid   = trim($input['session_id'] ?? '');
    if (!$sid) { echo json_encode(['sukses'=>false]); exit; }
    $db->prepare("UPDATE chat_pesan SET sudah_baca=1 WHERE session_id=? AND pengirim='pembeli'")->execute([$sid]);
    echo json_encode(['sukses'=>true]);
    exit;
}

echo json_encode(['sukses'=>false,'pesan'=>'Aksi tidak dikenali: '.$aksi]);
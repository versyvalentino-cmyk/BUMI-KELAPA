// ============================================================
// SISTEM ONGKIR BUMI KELAPA - DARI LATUHALAT (DUSUN OMPUTTY)
// Motor : maks 30 buah kelapa | maks 5 karung sabut/tempurung
// Pickup: wajib jika melebihi batas motor
// Paket Karung: 1 karung = 100 buah kelapa → selalu pickup
// ============================================================

let ongkir = 0;
let kendaraanDipilih = "";

// ============================================================
// BATAS KAPASITAS KENDARAAN
// ============================================================
const BATAS_MOTOR = {
    "kelapa":    30,   // buah
    "sabut":     5,    // karung
    "tempurung": 5,    // karung
    "paket":     0     // selalu pickup (1 karung = 100 buah)
};

// ============================================================
// TARIF DASAR PER KELURAHAN (untuk motor)
// ============================================================
const dataOngkir = {

    // === NUSANIWE ===
    "latuhalat":      { jarak: 0,    tarifMotor: 5000  },
    "seilale":        { jarak: 1.5,  tarifMotor: 5000  },
    "amahusu":        { jarak: 3,    tarifMotor: 9000  },
    "air salobar":    { jarak: 4,    tarifMotor: 12000 },
    "nusaniwe":       { jarak: 5,    tarifMotor: 15000 },
    "benteng":        { jarak: 5.5,  tarifMotor: 16500 },
    "kudamati":       { jarak: 6,    tarifMotor: 18000 },
    "wainitu":        { jarak: 6.5,  tarifMotor: 19500 },
    "mangga dua":     { jarak: 7,    tarifMotor: 21000 },
    "urimessing":     { jarak: 7,    tarifMotor: 21000 },
    "waihaong":       { jarak: 7.5,  tarifMotor: 22500 },
    "silale":         { jarak: 8,    tarifMotor: 24000 },
    "wakasihu":       { jarak: 9,    tarifMotor: 27000 },

    // === SIRIMAU ===
    "honipopu":       { jarak: 8,    tarifMotor: 24000 },
    "ahusen":         { jarak: 8.5,  tarifMotor: 25500 },
    "batu meja":      { jarak: 9,    tarifMotor: 27000 },
    "karang panjang": { jarak: 9.5,  tarifMotor: 28500 },
    "amantelu":       { jarak: 9.5,  tarifMotor: 28500 },
    "rijali":         { jarak: 10,   tarifMotor: 30000 },
    "uritetu":        { jarak: 10,   tarifMotor: 30000 },
    "batu gajah":     { jarak: 10.5, tarifMotor: 31500 },
    "batu merah":     { jarak: 11,   tarifMotor: 33000 },
    "pandan kasturi": { jarak: 11,   tarifMotor: 33000 },
    "galala":         { jarak: 12,   tarifMotor: 36000 },
    "hative kecil":   { jarak: 12,   tarifMotor: 36000 },
    "soya":           { jarak: 13,   tarifMotor: 39000 },
    "waihoka":        { jarak: 13,   tarifMotor: 39000 },

    // === TELUK AMBON ===
    "poka":           { jarak: 13,   tarifMotor: 39000 },
    "rumah tiga":     { jarak: 14,   tarifMotor: 42000 },
    "wayame":         { jarak: 15,   tarifMotor: 45000 },
    "hative besar":   { jarak: 15,   tarifMotor: 45000 },
    "hunuth":         { jarak: 14,   tarifMotor: 42000 },
    "tawiri":         { jarak: 17,   tarifMotor: 51000 },
    "laha":           { jarak: 20,   tarifMotor: 60000 },
    "tihu":           { jarak: 22,   tarifMotor: 66000 },

    // === BAGUALA ===
    "halong":         { jarak: 14,   tarifMotor: 42000 },
    "lateri":         { jarak: 15,   tarifMotor: 45000 },
    "latta":          { jarak: 15,   tarifMotor: 45000 },
    "nania":          { jarak: 16,   tarifMotor: 48000 },
    "negeri lama":    { jarak: 17,   tarifMotor: 51000 },
    "passo":          { jarak: 17,   tarifMotor: 51000 },
    "waiheru":        { jarak: 18,   tarifMotor: 54000 },

    // === LEITIMUR SELATAN ===
    "kilang":         { jarak: 10,   tarifMotor: 30000 },
    "hutumuri":       { jarak: 12,   tarifMotor: 36000 },
    "naku":           { jarak: 14,   tarifMotor: 42000 },
    "seri":           { jarak: 16,   tarifMotor: 48000 },
    "eirene":         { jarak: 18,   tarifMotor: 54000 },
};

// ============================================================
// TARIF PICKUP PER KECAMATAN
// biayaPickup  = biaya sewa pickup (flat, sekali jalan)
// perButir     = tambahan per buah kelapa
// perKarung    = tambahan per karung sabut/tempurung
// perKarungPkt = tambahan per karung Paket Karung (isi 100 buah)
// ============================================================
const tarifPickup = {
    "nusaniwe":         { biayaPickup: 50000,  perButir: 500,  perKarung: 5000, perKarungPkt: 10000 },
    "sirimau":          { biayaPickup: 75000,  perButir: 500,  perKarung: 6000, perKarungPkt: 12000 },
    "teluk ambon":      { biayaPickup: 100000, perButir: 1000, perKarung: 8000, perKarungPkt: 15000 },
    "baguala":          { biayaPickup: 100000, perButir: 1000, perKarung: 8000, perKarungPkt: 15000 },
    "leitimur selatan": { biayaPickup: 90000,  perButir: 1000, perKarung: 7000, perKarungPkt: 13000 },
};

// ============================================================
// DATA WILAYAH LENGKAP KOTA AMBON
// ============================================================
const dataWilayah = {
    "nusaniwe":         ["latuhalat","seilale","amahusu","air salobar","nusaniwe","benteng","kudamati","wainitu","mangga dua","urimessing","waihaong","silale","wakasihu"],
    "sirimau":          ["honipopu","ahusen","batu meja","karang panjang","amantelu","rijali","uritetu","batu gajah","batu merah","pandan kasturi","galala","hative kecil","soya","waihoka"],
    "teluk ambon":      ["poka","rumah tiga","wayame","hative besar","hunuth","tawiri","laha","tihu"],
    "baguala":          ["halong","lateri","latta","nania","negeri lama","passo","waiheru"],
    "leitimur selatan": ["kilang","hutumuri","naku","seri","eirene"]
};

// ============================================================
// STATE GLOBAL
// ============================================================
let keranjang = [];
let produkPesanLangsung = null;

// ============================================================
// FUNGSI ISI KELURAHAN
// ============================================================
function isiKelurahan() {
    let kecamatan = document.getElementById("kecamatan").value;
    let kelurahanSelect = document.getElementById("kelurahan");
    kelurahanSelect.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';

    if (dataWilayah[kecamatan]) {
        dataWilayah[kecamatan].forEach(function(kel) {
            let option = document.createElement("option");
            option.value = kel;
            option.text = kel.replace(/\b\w/g, c => c.toUpperCase());
            kelurahanSelect.appendChild(option);
        });
    }

    // Reset saat kecamatan berubah
    ongkir = 0;
    kendaraanDipilih = "";
    document.getElementById("ongkir").innerText = "Rp 0";
    resetInfoKendaraan();
    hitungTotal();
}

// ============================================================
// FUNGSI UTAMA: HITUNG ONGKIR + TENTUKAN KENDARAAN
// ============================================================
function hitungOngkir() {
    let kelurahan   = document.getElementById("kelurahan").value;
    let kecamatan   = document.getElementById("kecamatan").value;
    let jenisProduk = document.getElementById("jenisProduk").value; // kelapa | sabut | tempurung | paket
    let jumlah      = parseInt(document.getElementById("jumlahProduk").value) || 0;

    // Belum lengkap
    if (!kelurahan || !kecamatan || jumlah <= 0) {
        ongkir = 0;
        kendaraanDipilih = "";
        document.getElementById("ongkir").innerText = "Rp 0";
        resetInfoKendaraan();
        hitungTotal();
        return;
    }

    let infoEl   = document.getElementById("infoKendaraan");
    let dataLok  = dataOngkir[kelurahan];
    let tp       = tarifPickup[kecamatan];

    if (!dataLok || !tp) return;

    // -------------------------------------------------------
    // PAKET KARUNG — selalu pickup, 1 karung = 100 buah
    // -------------------------------------------------------
    if (jenisProduk === "paket") {
        kendaraanDipilih = "pickup";
        let totalButir   = jumlah * 100;
        ongkir           = tp.biayaPickup + (jumlah * tp.perKarungPkt);

        infoEl.className = "info-kendaraan pickup show";
        infoEl.innerHTML =
            `🚛 <strong>Kendaraan: Mobil Pickup</strong><br>` +
            `<em>Paket Karung selalu diantar dengan pickup</em><br>` +
            `Jumlah: <strong>${jumlah} karung</strong> (= ${totalButir} buah kelapa)<br>` +
            `Biaya sewa pickup: Rp ${tp.biayaPickup.toLocaleString("id-ID")}<br>` +
            `Biaya per karung: Rp ${tp.perKarungPkt.toLocaleString("id-ID")} × ${jumlah} = ` +
                `Rp ${(jumlah * tp.perKarungPkt).toLocaleString("id-ID")}<br>` +
            `Jarak: ±${dataLok.jarak} km dari Latuhalat`;
    }

    // -------------------------------------------------------
    // KELAPA ECERAN (buah)
    // -------------------------------------------------------
    else if (jenisProduk === "kelapa") {
        let batas = BATAS_MOTOR["kelapa"];

        if (jumlah <= batas) {
            // Motor
            kendaraanDipilih = "motor";
            ongkir           = dataLok.tarifMotor;

            infoEl.className = "info-kendaraan motor show";
            infoEl.innerHTML =
                `🏍️ <strong>Kendaraan: Motor</strong><br>` +
                `Jumlah: <strong>${jumlah} buah</strong> (maks ${batas} buah untuk motor)<br>` +
                `Jarak: ±${dataLok.jarak} km dari Latuhalat`;
        } else {
            // Pickup
            kendaraanDipilih = "pickup";
            ongkir           = tp.biayaPickup + (jumlah * tp.perButir);

            infoEl.className = "info-kendaraan pickup show";
            infoEl.innerHTML =
                `🚛 <strong>Kendaraan: Mobil Pickup</strong><br>` +
                `Jumlah: <strong>${jumlah} buah</strong> — melebihi batas motor (${batas} buah)<br>` +
                `Biaya sewa pickup: Rp ${tp.biayaPickup.toLocaleString("id-ID")}<br>` +
                `Biaya per buah: Rp ${tp.perButir.toLocaleString("id-ID")} × ${jumlah} = ` +
                    `Rp ${(jumlah * tp.perButir).toLocaleString("id-ID")}<br>` +
                `Jarak: ±${dataLok.jarak} km dari Latuhalat`;
        }
    }

    // -------------------------------------------------------
    // SABUT / TEMPURUNG (karung)
    // -------------------------------------------------------
    else {
        let batas  = BATAS_MOTOR[jenisProduk]; // 5 karung
        let satuan = "karung";

        if (jumlah <= batas) {
            // Motor
            kendaraanDipilih = "motor";
            ongkir           = dataLok.tarifMotor;

            infoEl.className = "info-kendaraan motor show";
            infoEl.innerHTML =
                `🏍️ <strong>Kendaraan: Motor</strong><br>` +
                `Jumlah: <strong>${jumlah} ${satuan}</strong> (maks ${batas} ${satuan} untuk motor)<br>` +
                `Jarak: ±${dataLok.jarak} km dari Latuhalat`;
        } else {
            // Pickup
            kendaraanDipilih = "pickup";
            ongkir           = tp.biayaPickup + (jumlah * tp.perKarung);

            infoEl.className = "info-kendaraan pickup show";
            infoEl.innerHTML =
                `🚛 <strong>Kendaraan: Mobil Pickup</strong><br>` +
                `Jumlah: <strong>${jumlah} ${satuan}</strong> — melebihi batas motor (${batas} ${satuan})<br>` +
                `Biaya sewa pickup: Rp ${tp.biayaPickup.toLocaleString("id-ID")}<br>` +
                `Biaya per ${satuan}: Rp ${tp.perKarung.toLocaleString("id-ID")} × ${jumlah} = ` +
                    `Rp ${(jumlah * tp.perKarung).toLocaleString("id-ID")}<br>` +
                `Jarak: ±${dataLok.jarak} km dari Latuhalat`;
        }
    }

    document.getElementById("ongkir").innerText = "Rp " + ongkir.toLocaleString("id-ID");
    hitungTotal();
}

// ============================================================
// FUNGSI HITUNG TOTAL
// ============================================================
function hitungTotal() {
    let totalBarang = 0;

    if (produkPesanLangsung) {
        totalBarang = produkPesanLangsung.harga * produkPesanLangsung.qty;
    } else {
        keranjang.forEach(item => {
            totalBarang += item.harga * item.qty;
        });
    }

    let totalBayar = totalBarang + ongkir;
    document.getElementById("hargaBarang").innerText = "Rp " + totalBarang.toLocaleString("id-ID");
    document.getElementById("totalBayar").innerText  = "Rp " + totalBayar.toLocaleString("id-ID");
}

// ============================================================
// HELPER: RESET INFO KENDARAAN
// ============================================================
function resetInfoKendaraan() {
    let infoEl = document.getElementById("infoKendaraan");
    if (infoEl) {
        infoEl.className = "info-kendaraan";
        infoEl.innerHTML = "";
    }
}

// ============================================================
// KERANJANG
// ============================================================
function tambah(btn) {
    let qtyElement = btn.parentElement.querySelector(".qty");
    qtyElement.innerText = parseInt(qtyElement.innerText) + 1;
}

function kurang(btn) {
    let qtyElement = btn.parentElement.querySelector(".qty");
    let jumlah = parseInt(qtyElement.innerText);
    if (jumlah > 1) qtyElement.innerText = jumlah - 1;
}

function tambahKeranjang(nama, harga, btn) {
    let qty = parseInt(btn.parentElement.parentElement.querySelector(".qty").innerText);
    keranjang.push({ nama, harga, qty });
    document.getElementById("jumlahKeranjang").innerText = keranjang.length;
}

function bukaKeranjang() {
    let html = "";
    let totalBarang = 0;

    keranjang.forEach((item, index) => {
        totalBarang += item.harga * item.qty;
        html += `<p>${item.nama} x${item.qty} - Rp ${(item.harga * item.qty).toLocaleString("id-ID")}
                 <button onclick="hapusItem(${index})">Hapus</button></p>`;
    });

    document.getElementById("isiKeranjang").innerHTML = html;
    document.getElementById("totalHarga").innerText = "Rp " + totalBarang.toLocaleString("id-ID");
    document.getElementById("popupKeranjang").style.display = "block";
}

function tutupKeranjang() {
    document.getElementById("popupKeranjang").style.display = "none";
}

function hapusItem(index) {
    keranjang.splice(index, 1);
    document.getElementById("jumlahKeranjang").innerText = keranjang.length;
    bukaKeranjang();
}

function checkout() {
    document.getElementById("popupKeranjang").style.display = "none";
    document.getElementById("popupLogin").style.display = "flex";
}

// ============================================================
// PESAN LANGSUNG (tombol Pesan di card produk)
// jenis: "kelapa" | "sabut" | "tempurung" | "paket"
// ============================================================
function pesanLogin(nama, harga, btn, jenis) {
    let card = btn.closest(".card");
    let qty  = parseInt(card.querySelector(".qty").innerText);

    produkPesanLangsung = { nama, harga, qty, jenis: jenis || "kelapa" };

    // Set dropdown jenis produk
    let jenisEl = document.getElementById("jenisProduk");
    if (jenisEl) jenisEl.value = jenis || "kelapa";

    // Set jumlah otomatis
    let jumlahEl = document.getElementById("jumlahProduk");
    if (jenis === "paket") {
        // Paket karung: isi jumlah dalam satuan KARUNG
        if (jumlahEl) jumlahEl.value = qty;
    } else {
        if (jumlahEl) jumlahEl.value = qty;
    }

    hitungTotal();
    document.getElementById("popupLogin").style.display = "flex";

    // Trigger hitung ongkir jika kelurahan sudah dipilih
    if (document.getElementById("kelurahan").value) {
        hitungOngkir();
    }
}

// ============================================================
// KHUSUS PAKET KARUNG
// 1 karung = 100 buah → selalu pickup
// ============================================================
function pesanPaket(nama, harga, btn) {
    let card   = btn.closest(".card");
    let karung = parseInt(card.querySelector(".qty").innerText);

    produkPesanLangsung = {
        nama:  `${nama} (${karung} karung / ${karung * 100} buah)`,
        harga: harga,
        qty:   karung,
        jenis: "paket"
    };

    // Set jenis & jumlah di form
    let jenisEl  = document.getElementById("jenisProduk");
    let jumlahEl = document.getElementById("jumlahProduk");
    if (jenisEl)  jenisEl.value  = "paket";
    if (jumlahEl) jumlahEl.value = karung;

    hitungTotal();
    document.getElementById("popupLogin").style.display = "flex";

    // Trigger hitung ongkir jika kelurahan sudah dipilih
    if (document.getElementById("kelurahan").value) {
        hitungOngkir();
    }
}

// ============================================================
// KIRIM PESANAN KE SERVER
// ============================================================
function kirimPesanan() {
    let nama        = document.getElementById("namaPembeli").value.trim();
    let hp          = document.getElementById("hpPembeli").value.trim();
    let kecamatan   = document.getElementById("kecamatan").value;
    let kelurahan   = document.getElementById("kelurahan").value;
    let alamat      = document.getElementById("alamatDetail").value.trim();
    let metode      = document.getElementById("metodePembayaran").value;
    let jenis       = document.getElementById("jenisProduk").value;
    let jumlah      = parseInt(document.getElementById("jumlahProduk").value) || 0;

    if (!nama || !hp || !kecamatan || !kelurahan || !alamat || !metode) {
        alert("Harap isi semua data terlebih dahulu!");
        return;
    }
    if (jumlah <= 0) {
        alert("Masukkan jumlah produk yang dipesan!");
        return;
    }

    let totalBarang = 0;
    let produkData  = [];

    if (produkPesanLangsung) {
        totalBarang = produkPesanLangsung.harga * produkPesanLangsung.qty;
        produkData  = [produkPesanLangsung];
    } else {
        keranjang.forEach(item => {
            totalBarang += item.harga * item.qty;
        });
        produkData = keranjang;
    }

    fetch("simpan_pesanan.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            nama,
            hp,
            alamat,
            kecamatan,
            kelurahan,
            metode,
            jenis_produk:  jenis,
            jumlah_pesan:  jumlah,
            kendaraan:     kendaraanDipilih,   // "motor" atau "pickup"
            total_barang:  totalBarang,
            ongkir:        ongkir,
            total_bayar:   totalBarang + ongkir,
            produk:        produkData
        })
    })
    .then(res => res.text())
    .then(res => {
        if (res === "sukses") {
            alert("Pesanan berhasil disimpan!");
            keranjang = [];
            produkPesanLangsung = null;
            document.getElementById("jumlahKeranjang").innerText = 0;
            tutupLogin();
        } else {
            alert("Error: " + res);
        }
    })
    .catch(() => alert("Gagal koneksi ke server!"));
}

// ============================================================
// METODE PEMBAYARAN
// ============================================================
function tampilkanOpsi() {
    let metode = document.getElementById("metodePembayaran").value;
    let opsi   = document.getElementById("opsiPembayaran");

    if (metode === "transfer") {
        opsi.innerHTML = `
            <label>Pilih Bank</label>
            <select id="bank">
                <option value="">-- Pilih Bank --</option>
                <option value="bca">BCA</option>
                <option value="bri">BRI</option>
                <option value="mandiri">Mandiri</option>
                <option value="bni">BNI</option>
            </select>`;
    } else if (metode === "ewallet") {
        opsi.innerHTML = `
            <label>Pilih E-Wallet</label>
            <select id="ewallet">
                <option value="">-- Pilih E-Wallet --</option>
                <option value="dana">DANA</option>
                <option value="ovo">OVO</option>
                <option value="gopay">GoPay</option>
                <option value="shopeepay">ShopeePay</option>
            </select>`;
    } else {
        opsi.innerHTML = "";
    }
}

// ============================================================
// UTILITAS LAIN
// ============================================================
function tutupLogin() {
    document.getElementById("popupLogin").style.display = "none";
}

function lihatGambar(img) {
    document.getElementById("popupGambar").style.display = "block";
    document.getElementById("gambarBesar").src = img.src;
}

function tutupGambar() {
    document.getElementById("popupGambar").style.display = "none";
}

function ambilLokasi() {
    if (!navigator.geolocation) return alert("Browser tidak mendukung geolokasi.");
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById("alamatDetail").value =
            `Lat: ${pos.coords.latitude.toFixed(5)}, Lon: ${pos.coords.longitude.toFixed(5)}`;
    }, () => alert("Gagal mengambil lokasi. Izinkan akses lokasi di browser Anda."));
}
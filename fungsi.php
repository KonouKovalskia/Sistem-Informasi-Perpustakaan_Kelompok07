<?php
// Satu proses DFD level terbawah = satu fungsi, isi mengikuti PSPEC nomor yang sama.
// Aliran data input = parameter, aliran data output = nilai kembalian.
// Kalau proses menampilkan pesan gagal (DISPLAY), hasilnya ["gagal" => pesan].

// PSPEC 1.1 Registrasi Anggota Baru
function registrasiAnggota($db, $namaAnggota, $alamat, $noTelepon, $email)
{
    if ($namaAnggota === "" || $alamat === "" || $noTelepon === "") {
        return ["gagal" => "Data registrasi belum lengkap"];
    }
    // validasi dari Kamus Data: no-telepon 10-15 digit, email boleh kosong
    if (!preg_match('/^[0-9]{10,15}$/', $noTelepon)) {
        return ["gagal" => "No. telepon harus 10 sampai 15 digit angka"];
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ["gagal" => "Format email salah"];
    }
    $idAnggota = idBaru($db, "anggota", "id_anggota", "AGT", 5);
    $tanggalDaftar = date("Y-m-d");
    mysqli_execute_query($db, "INSERT INTO anggota VALUES (?, ?, ?, ?, ?, ?, 'aktif', 0)",
        [$idAnggota, $namaAnggota, $alamat, $noTelepon, $email === "" ? null : $email, $tanggalDaftar]);
    // Kartu Anggota
    return ["id_anggota" => $idAnggota, "nama_anggota" => $namaAnggota, "tanggal_daftar" => $tanggalDaftar];
}

// PSPEC 1.2 Cek Status Anggota
function cekStatusAnggota($db, $idAnggota)
{
    $hasil = mysqli_execute_query($db, "SELECT id_anggota, nama_anggota, status_anggota, denda_tertunggak FROM anggota WHERE id_anggota = ?", [$idAnggota]);
    $anggota = mysqli_fetch_assoc($hasil);
    if ($anggota === null) {
        return ["gagal" => "Anggota tidak terdaftar"];
    }
    // Info Status Anggota, dan valid = Status Anggota Valid untuk proses 2.0
    $anggota["valid"] = $anggota["status_anggota"] === "aktif" && $anggota["denda_tertunggak"] == 0;
    return $anggota;
}

// PSPEC 2.1 Cek Ketersediaan Buku
function cekKetersediaanBuku($db, $idBuku)
{
    $hasil = mysqli_execute_query($db, "SELECT judul FROM buku WHERE id_buku = ?", [$idBuku]);
    $buku = mysqli_fetch_assoc($hasil);
    if ($buku === null) {
        return ["gagal" => "Buku tidak ada di katalog"];
    }
    $hasil = mysqli_execute_query($db, "SELECT id_eksemplar FROM eksemplar WHERE id_buku = ? AND status_eksemplar = 'tersedia' ORDER BY id_eksemplar", [$idBuku]);
    $tersedia = mysqli_fetch_all($hasil);
    // Info Ketersediaan Buku, dan id_eksemplar = Buku Tersedia untuk proses 2.2
    return [
        "id_buku" => $idBuku,
        "judul" => $buku["judul"],
        "jumlah_tersedia" => count($tersedia),
        "id_eksemplar" => $tersedia ? $tersedia[0][0] : null,
    ];
}

// PSPEC 2.2 Catat Peminjaman
function catatPeminjaman($db, $idEksemplar, $idAnggota)
{
    // tambalan celah 1: eksemplar ditandai dipinjam; kalau baris tidak berubah, sudah dipinjam orang lain
    mysqli_execute_query($db, "UPDATE eksemplar SET status_eksemplar = 'dipinjam' WHERE id_eksemplar = ? AND status_eksemplar = 'tersedia'", [$idEksemplar]);
    if (mysqli_affected_rows($db) === 0) {
        return ["gagal" => "Eksemplar sudah tidak tersedia"];
    }
    $idPeminjaman = idBaru($db, "peminjaman", "id_peminjaman", "PJM", 6);
    $tanggalPinjam = date("Y-m-d");
    $tanggalJatuhTempo = date("Y-m-d", strtotime("+" . LAMA_PINJAM . " days"));
    mysqli_execute_query($db, "INSERT INTO peminjaman VALUES (?, ?, ?, ?, ?, NULL, 'dipinjam')",
        [$idPeminjaman, $idAnggota, $idEksemplar, $tanggalPinjam, $tanggalJatuhTempo]);
    // Data Transaksi
    return [
        "id_peminjaman" => $idPeminjaman,
        "id_anggota" => $idAnggota,
        "id_eksemplar" => $idEksemplar,
        "tanggal_pinjam" => $tanggalPinjam,
        "tanggal_jatuh_tempo" => $tanggalJatuhTempo,
    ];
}

// PSPEC 2.3 Buat Bukti Peminjaman
function buatBuktiPeminjaman($dataTransaksi)
{
    return [
        "No. Peminjaman" => $dataTransaksi["id_peminjaman"],
        "ID Anggota" => $dataTransaksi["id_anggota"],
        "ID Eksemplar" => $dataTransaksi["id_eksemplar"],
        "Tanggal Pinjam" => $dataTransaksi["tanggal_pinjam"],
        "Jatuh Tempo" => $dataTransaksi["tanggal_jatuh_tempo"],
    ];
}

// PSPEC 3.1 Catat Pengembalian
function catatPengembalian($db, $idPeminjaman)
{
    $hasil = mysqli_execute_query($db, "SELECT p.tanggal_jatuh_tempo, p.status_peminjaman, p.id_eksemplar, e.id_buku FROM peminjaman p JOIN eksemplar e ON e.id_eksemplar = p.id_eksemplar WHERE p.id_peminjaman = ?", [$idPeminjaman]);
    $pinjam = mysqli_fetch_assoc($hasil);
    if ($pinjam === null || $pinjam["status_peminjaman"] !== "dipinjam") {
        return ["gagal" => "Data peminjaman tidak valid"];
    }
    $tanggalKembali = date("Y-m-d");
    // tambalan celah 1: peminjaman ditutup dan eksemplar tersedia lagi
    mysqli_execute_query($db, "UPDATE peminjaman SET tanggal_kembali = ?, status_peminjaman = 'dikembalikan' WHERE id_peminjaman = ?", [$tanggalKembali, $idPeminjaman]);
    mysqli_execute_query($db, "UPDATE eksemplar SET status_eksemplar = 'tersedia' WHERE id_eksemplar = ?", [$pinjam["id_eksemplar"]]);
    // tambalan celah 5: reservasi paling awal untuk buku ini jadi siap diambil
    mysqli_execute_query($db, "UPDATE reservasi SET status_reservasi = 'siap diambil' WHERE id_buku = ? AND status_reservasi = 'menunggu' ORDER BY tanggal_reservasi, id_reservasi LIMIT 1", [$pinjam["id_buku"]]);
    // Data Keterlambatan
    return [
        "id_peminjaman" => $idPeminjaman,
        "tanggal_jatuh_tempo" => $pinjam["tanggal_jatuh_tempo"],
        "tanggal_kembali" => $tanggalKembali,
    ];
}

// PSPEC 3.2.1 Hitung Hari Terlambat
function hitungHariTerlambat($dataKeterlambatan)
{
    $selisih = date_diff(date_create($dataKeterlambatan["tanggal_jatuh_tempo"]), date_create($dataKeterlambatan["tanggal_kembali"]));
    $jumlahHariTerlambat = (int) $selisih->format("%r%a");
    if ($jumlahHariTerlambat < 0) {
        $jumlahHariTerlambat = 0;
    }
    return ["id_peminjaman" => $dataKeterlambatan["id_peminjaman"], "jumlah_hari_terlambat" => $jumlahHariTerlambat];
}

// PSPEC 3.2.2 Hitung Nominal Denda
function hitungNominalDenda($db, $jumlahHariTerlambat)
{
    $idPeminjaman = $jumlahHariTerlambat["id_peminjaman"];
    $hari = $jumlahHariTerlambat["jumlah_hari_terlambat"];
    if ($hari > 0) {
        $nominalDenda = $hari * TARIF_DENDA;
        $idDenda = idBaru($db, "denda", "id_denda", "DND", 6);
        mysqli_execute_query($db, "INSERT INTO denda VALUES (?, ?, ?, ?, 'belum lunas', NULL)", [$idDenda, $idPeminjaman, $hari, $nominalDenda]);
        // tambalan celah 2: denda_tertunggak anggota bertambah
        mysqli_execute_query($db, "UPDATE anggota a JOIN peminjaman p ON p.id_anggota = a.id_anggota SET a.denda_tertunggak = a.denda_tertunggak + ? WHERE p.id_peminjaman = ?", [$nominalDenda, $idPeminjaman]);
    } else {
        $nominalDenda = 0;
    }
    // Nominal Denda (Data Denda untuk 3.4 dibaca laporan langsung dari D4)
    return ["id_peminjaman" => $idPeminjaman, "jumlah_hari_terlambat" => $hari, "nominal_denda" => $nominalDenda];
}

// PSPEC 3.2.3 Buat Info Denda
function buatInfoDenda($nominalDenda)
{
    if ($nominalDenda["nominal_denda"] > 0) {
        return "Peminjaman " . $nominalDenda["id_peminjaman"] . " terlambat " . $nominalDenda["jumlah_hari_terlambat"]
            . " hari, denda " . rupiah($nominalDenda["nominal_denda"]);
    }
    return "Buku dikembalikan tepat waktu, tidak ada denda";
}

// PSPEC 3.3 Catat Pembayaran Denda
function catatPembayaranDenda($db, $idDenda, $jumlahBayar)
{
    if ($jumlahBayar <= 0) {
        return ["gagal" => "Jumlah pembayaran tidak valid"];
    }
    $hasil = mysqli_execute_query($db, "SELECT nominal_denda, status_bayar FROM denda WHERE id_denda = ?", [$idDenda]);
    $denda = mysqli_fetch_assoc($hasil);
    if ($denda === null || $denda["status_bayar"] !== "belum lunas") {
        return ["gagal" => "Denda tidak ditemukan atau sudah lunas"];
    }
    // D4 tidak menyimpan cicilan, jadi pembayaran harus sebesar nominal denda
    if ($jumlahBayar != $denda["nominal_denda"]) {
        return ["gagal" => "Jumlah pembayaran harus " . rupiah($denda["nominal_denda"])];
    }
    $tanggalBayar = date("Y-m-d");
    mysqli_execute_query($db, "UPDATE denda SET status_bayar = 'lunas', tanggal_bayar = ? WHERE id_denda = ?", [$tanggalBayar, $idDenda]);
    // tambalan celah 2: denda_tertunggak anggota berkurang
    mysqli_execute_query($db, "UPDATE anggota a JOIN peminjaman p ON p.id_anggota = a.id_anggota JOIN denda d ON d.id_peminjaman = p.id_peminjaman SET a.denda_tertunggak = a.denda_tertunggak - ? WHERE d.id_denda = ?", [$denda["nominal_denda"], $idDenda]);
    // Data Pembayaran Denda
    return ["id_denda" => $idDenda, "jumlah_bayar" => $jumlahBayar, "tanggal_bayar" => $tanggalBayar];
}

// PSPEC 3.4 Buat Laporan, periode = "YYYY-MM"
function buatLaporan($db, $periode)
{
    $hasil = mysqli_execute_query($db, "SELECT id_peminjaman, id_anggota, tanggal_pinjam, tanggal_kembali FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = ? ORDER BY id_peminjaman", [$periode]);
    $peminjaman = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
    // denda tercatat saat buku dikembalikan, jadi periodenya ikut tanggal_kembali
    $hasil = mysqli_execute_query($db, "SELECT COALESCE(SUM(d.nominal_denda), 0) AS total FROM denda d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman WHERE DATE_FORMAT(p.tanggal_kembali, '%Y-%m') = ?", [$periode]);
    $totalDenda = (int) mysqli_fetch_assoc($hasil)["total"];
    $hasil = mysqli_execute_query($db, "SELECT COALESCE(SUM(nominal_denda), 0) AS total FROM denda WHERE status_bayar = 'lunas' AND DATE_FORMAT(tanggal_bayar, '%Y-%m') = ?", [$periode]);
    $totalDendaTerbayar = (int) mysqli_fetch_assoc($hasil)["total"];
    // Laporan Peminjaman & Denda
    return [
        "periode" => $periode,
        "total_peminjaman" => count($peminjaman),
        "total_denda" => $totalDenda,
        "total_denda_terbayar" => $totalDendaTerbayar,
        "peminjaman" => $peminjaman,
    ];
}

// PSPEC 4.1 Terima Permintaan Reservasi
function terimaPermintaanReservasi($idAnggota, $idBuku)
{
    if ($idAnggota === "" || $idBuku === "") {
        return ["gagal" => "Data reservasi belum lengkap"];
    }
    // Permintaan Reservasi
    return ["id_anggota" => $idAnggota, "id_buku" => $idBuku, "tanggal_reservasi" => date("Y-m-d")];
}

// PSPEC 4.2 Catat Reservasi
function catatReservasi($db, $permintaan)
{
    $anggota = cekStatusAnggota($db, $permintaan["id_anggota"]);
    if (isset($anggota["gagal"])) {
        return $anggota;
    }
    // tambalan celah 5: reservasi hanya untuk buku yang semua eksemplarnya sedang dipinjam
    $buku = cekKetersediaanBuku($db, $permintaan["id_buku"]);
    if (isset($buku["gagal"])) {
        return $buku;
    }
    if ($buku["jumlah_tersedia"] > 0) {
        return ["gagal" => "Buku masih tersedia, bisa langsung dipinjam"];
    }
    $idReservasi = idBaru($db, "reservasi", "id_reservasi", "RSV", 6);
    mysqli_execute_query($db, "INSERT INTO reservasi VALUES (?, ?, ?, ?, 'menunggu')",
        [$idReservasi, $permintaan["id_anggota"], $permintaan["id_buku"], $permintaan["tanggal_reservasi"]]);
    // Status Reservasi
    return ["id_reservasi" => $idReservasi, "id_buku" => $permintaan["id_buku"], "status_reservasi" => "menunggu"];
}

// PSPEC 5.1 Kelola Data Buku, $data = isi form Data Buku
function kelolaDataBuku($db, $aksi, $data)
{
    $idBuku = $data["id_buku"] ?? "";
    if ($aksi === "tambah" || $aksi === "ubah") {
        if ($data["judul"] === "" || $data["pengarang"] === "" || $data["penerbit"] === "") {
            return ["gagal" => "Judul, pengarang, dan penerbit wajib diisi"];
        }
        if (!preg_match('/^[0-9]{4}$/', $data["tahun_terbit"])) {
            return ["gagal" => "Tahun terbit harus 4 digit"];
        }
    }
    if ($aksi === "tambah") {
        $jumlah = (int) $data["jumlah_eksemplar"];
        if ($jumlah < 1 || $jumlah > 99) {
            return ["gagal" => "Jumlah eksemplar harus 1 sampai 99"];
        }
        $idBuku = idBaru($db, "buku", "id_buku", "BK", 5);
        mysqli_execute_query($db, "INSERT INTO buku VALUES (?, ?, ?, ?, ?)",
            [$idBuku, $data["judul"], $data["pengarang"], $data["penerbit"], $data["tahun_terbit"]]);
        tambahEksemplar($db, $idBuku, $jumlah);
        $pesanHasil = "Data buku berhasil disimpan";
    } elseif ($aksi === "ubah") {
        mysqli_execute_query($db, "UPDATE buku SET judul = ?, pengarang = ?, penerbit = ?, tahun_terbit = ? WHERE id_buku = ?",
            [$data["judul"], $data["pengarang"], $data["penerbit"], $data["tahun_terbit"], $idBuku]);
        $pesanHasil = "Data buku berhasil diubah";
    } elseif ($aksi === "hapus") {
        // buku yang sudah punya riwayat tidak boleh hilang dari catatan
        $hasil = mysqli_execute_query($db, "SELECT (SELECT COUNT(*) FROM peminjaman p JOIN eksemplar e ON e.id_eksemplar = p.id_eksemplar WHERE e.id_buku = ?)
            + (SELECT COUNT(*) FROM reservasi WHERE id_buku = ?) + (SELECT COUNT(*) FROM detail_pengadaan WHERE id_buku = ?) AS jumlah", [$idBuku, $idBuku, $idBuku]);
        if (mysqli_fetch_assoc($hasil)["jumlah"] > 0) {
            return ["gagal" => "Buku sudah punya riwayat peminjaman, reservasi, atau pengadaan, jadi tidak bisa dihapus"];
        }
        mysqli_execute_query($db, "DELETE FROM eksemplar WHERE id_buku = ?", [$idBuku]);
        mysqli_execute_query($db, "DELETE FROM buku WHERE id_buku = ?", [$idBuku]);
        $pesanHasil = "Data buku berhasil dihapus";
    } else {
        return ["gagal" => "Aksi tidak dikenal"];
    }
    // Info Data Buku
    return ["id_buku" => $idBuku, "aksi" => $aksi, "pesan_hasil" => $pesanHasil];
}

// PSPEC 5.2 Kelola Akun Pengguna
function kelolaAkunPengguna($db, $aksi, $username, $password, $peran)
{
    if ($aksi === "tambah" || $aksi === "ubah") {
        if (!in_array($peran, ["admin", "petugas", "kepala"])) {
            return ["gagal" => "Peran harus admin, petugas, atau kepala"];
        }
        if (($aksi === "tambah" || $password !== "") && strlen($password) < 8) {
            return ["gagal" => "Password minimal 8 karakter"];
        }
    }
    if ($aksi === "tambah") {
        if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) {
            return ["gagal" => "Username 4 sampai 30 karakter (huruf, angka, titik, garis bawah)"];
        }
        $hasil = mysqli_execute_query($db, "SELECT id_akun FROM akun_pengguna WHERE username = ?", [$username]);
        if (mysqli_fetch_assoc($hasil) !== null) {
            return ["gagal" => "Username sudah dipakai"];
        }
        $idAkun = idBaru($db, "akun_pengguna", "id_akun", "AKN", 5);
        mysqli_execute_query($db, "INSERT INTO akun_pengguna VALUES (?, ?, ?, ?, NULL)",
            [$idAkun, $username, password_hash($password, PASSWORD_DEFAULT), $peran]);
        $pesanHasil = "Akun berhasil dibuat";
    } elseif ($aksi === "ubah") {
        if ($password !== "") {
            mysqli_execute_query($db, "UPDATE akun_pengguna SET password = ? WHERE username = ?", [password_hash($password, PASSWORD_DEFAULT), $username]);
        }
        mysqli_execute_query($db, "UPDATE akun_pengguna SET peran = ? WHERE username = ?", [$peran, $username]);
        $pesanHasil = "Akun berhasil diubah";
    } elseif ($aksi === "hapus") {
        if ($username === ($_SESSION["username"] ?? "")) {
            return ["gagal" => "Akun yang sedang dipakai tidak bisa dihapus"];
        }
        mysqli_execute_query($db, "DELETE FROM akun_pengguna WHERE username = ?", [$username]);
        $pesanHasil = "Akun berhasil dihapus";
    } else {
        return ["gagal" => "Aksi tidak dikenal"];
    }
    // Info Akun Pengguna
    return ["username" => $username, "aksi" => $aksi, "pesan_hasil" => $pesanHasil];
}

// tambalan celah 4: Admin/Petugas membuat usulan, $daftarBuku = [id_buku => jumlah_pesan]
function usulPengadaan($db, $daftarBuku)
{
    $daftarBuku = array_filter(array_map("intval", $daftarBuku));
    if (!$daftarBuku) {
        return ["gagal" => "Isi jumlah pesan minimal satu buku"];
    }
    foreach ($daftarBuku as $jumlah) {
        if ($jumlah < 1 || $jumlah > 99) {
            return ["gagal" => "Jumlah pesan harus 1 sampai 99"];
        }
    }
    $idPengadaan = idBaru($db, "pengadaan", "id_pengadaan", "PGD", 5);
    mysqli_execute_query($db, "INSERT INTO pengadaan VALUES (?, NULL, 'diusulkan', NULL, NULL)", [$idPengadaan]);
    foreach ($daftarBuku as $idBuku => $jumlah) {
        mysqli_execute_query($db, "INSERT INTO detail_pengadaan VALUES (?, ?, ?, NULL)", [$idPengadaan, $idBuku, $jumlah]);
    }
    return ["id_pengadaan" => $idPengadaan];
}

function daftarBukuPengadaan($db, $idPengadaan)
{
    $hasil = mysqli_execute_query($db, "SELECT d.id_buku, b.judul, d.jumlah_pesan, d.jumlah_diterima FROM detail_pengadaan d JOIN buku b ON b.id_buku = d.id_buku WHERE d.id_pengadaan = ?", [$idPengadaan]);
    return mysqli_fetch_all($hasil, MYSQLI_ASSOC);
}

function statusPengadaan($db, $idPengadaan)
{
    $hasil = mysqli_execute_query($db, "SELECT status_pengadaan FROM pengadaan WHERE id_pengadaan = ?", [$idPengadaan]);
    return mysqli_fetch_assoc($hasil)["status_pengadaan"] ?? null;
}

// PSPEC 6.1 Proses Persetujuan
function prosesPersetujuan($db, $idPengadaan, $statusPersetujuan)
{
    if (statusPengadaan($db, $idPengadaan) !== "diusulkan") {
        return ["gagal" => "Pengadaan tidak menunggu persetujuan"];
    }
    if ($statusPersetujuan === "disetujui") {
        // Pengadaan Disetujui
        return ["id_pengadaan" => $idPengadaan, "daftar_buku" => daftarBukuPengadaan($db, $idPengadaan)];
    }
    // tambalan celah 4: usulan yang ditolak dicatat supaya tidak menunggu terus
    mysqli_execute_query($db, "UPDATE pengadaan SET status_pengadaan = 'ditolak' WHERE id_pengadaan = ?", [$idPengadaan]);
    return ["gagal" => "Pengadaan tidak disetujui"];
}

// PSPEC 6.2 Kirim Pesanan
function kirimPesanan($db, $pengadaanDisetujui)
{
    $tanggalPesan = date("Y-m-d");
    mysqli_execute_query($db, "UPDATE pengadaan SET tanggal_pesan = ?, status_pengadaan = 'dipesan' WHERE id_pengadaan = ?",
        [$tanggalPesan, $pengadaanDisetujui["id_pengadaan"]]);
    // Pesanan Pengadaan Buku
    return [
        "id_pengadaan" => $pengadaanDisetujui["id_pengadaan"],
        "daftar_buku" => $pengadaanDisetujui["daftar_buku"],
        "tanggal_pesan" => $tanggalPesan,
    ];
}

// PSPEC 6.3 Terima Buku, $daftarBukuDiterima = [id_buku => jumlah_diterima]
function terimaBuku($db, $noFaktur, $idPengadaan, $daftarBukuDiterima)
{
    if ($noFaktur === "") {
        return ["gagal" => "No. faktur wajib diisi"];
    }
    if (statusPengadaan($db, $idPengadaan) !== "dipesan") {
        return ["gagal" => "Pengadaan belum dipesan atau sudah diterima"];
    }
    foreach (daftarBukuPengadaan($db, $idPengadaan) as $detail) {
        // jumlah diterima dibatasi 0 sampai jumlah pesan
        $jumlah = min($detail["jumlah_pesan"], max(0, (int) ($daftarBukuDiterima[$detail["id_buku"]] ?? 0)));
        mysqli_execute_query($db, "UPDATE detail_pengadaan SET jumlah_diterima = ? WHERE id_pengadaan = ? AND id_buku = ?", [$jumlah, $idPengadaan, $detail["id_buku"]]);
        // tambalan celah 3: buku yang diterima masuk katalog sebagai eksemplar baru
        tambahEksemplar($db, $detail["id_buku"], $jumlah);
    }
    mysqli_execute_query($db, "UPDATE pengadaan SET no_faktur = ?, tanggal_terima = ?, status_pengadaan = 'diterima' WHERE id_pengadaan = ?",
        [$noFaktur, date("Y-m-d"), $idPengadaan]);
    return ["id_pengadaan" => $idPengadaan];
}

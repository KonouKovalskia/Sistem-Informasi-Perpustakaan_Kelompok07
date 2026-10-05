<?php
// Koneksi database dan fungsi bantu yang dipakai semua halaman
// cookie login tidak bisa dibaca JavaScript dan tidak ikut dikirim dari form situs lain
session_set_cookie_params(["httponly" => true, "samesite" => "Lax", "secure" => isset($_SERVER["HTTPS"])]);
session_start();
date_default_timezone_set("Asia/Jakarta");

$db = mysqli_connect("localhost", "root", "", "perpustakaan");

// Istilah lokal di PSPEC 2.2 dan 3.2.2
const LAMA_PINJAM = 7;
const TARIF_DENDA = 1000;

// halaman pertama setelah login, per peran
const HALAMAN_AWAL = ["petugas" => "anggota.php", "admin" => "buku.php", "kepala" => "laporan.php", "anggota" => "katalog.php"];

function masukSesi($username, $peran, $idAnggota)
{
    session_regenerate_id(true);
    $_SESSION["username"] = $username;
    $_SESSION["peran"] = $peran;
    $_SESSION["id_anggota"] = $idAnggota;
}

function e($teks)
{
    return htmlspecialchars((string) $teks);
}

function rupiah($angka)
{
    return "Rp" . number_format($angka, 0, ",", ".");
}

// Nomor baru sesuai format id di Kamus Data, contoh AGT00001
function idBaru($db, $tabel, $kolom, $awalan, $digit)
{
    $mulai = strlen($awalan) + 1;
    $hasil = mysqli_query($db, "SELECT MAX(CAST(SUBSTRING($kolom, $mulai) AS UNSIGNED)) AS terakhir FROM $tabel");
    $terakhir = mysqli_fetch_assoc($hasil)["terakhir"];
    return $awalan . str_pad($terakhir + 1, $digit, "0", STR_PAD_LEFT);
}

// id-eksemplar = id-buku + "-" + 2 digit, dipakai proses 5.1 dan 6.3
// catatan: format DD membatasi 99 eksemplar per buku; lebih dari itu INSERT gagal, ubah format id kalau perlu
function tambahEksemplar($db, $idBuku, $jumlah)
{
    $hasil = mysqli_execute_query($db, "SELECT MAX(CAST(SUBSTRING(id_eksemplar, 9) AS UNSIGNED)) AS terakhir FROM eksemplar WHERE id_buku = ?", [$idBuku]);
    $nomor = (int) mysqli_fetch_assoc($hasil)["terakhir"];
    for ($i = 0; $i < $jumlah; $i++) {
        $nomor++;
        $idEksemplar = $idBuku . "-" . str_pad($nomor, 2, "0", STR_PAD_LEFT);
        mysqli_execute_query($db, "INSERT INTO eksemplar VALUES (?, ?, 'tersedia')", [$idEksemplar, $idBuku]);
    }
}

// tambalan celah 6: halaman hanya untuk peran tertentu
// Peran dibaca ulang dari database, jadi akun yang diubah atau dihapus admin langsung terdampak
function wajibLogin(...$peran)
{
    global $db;
    $akun = null;
    if (isset($_SESSION["username"])) {
        $hasil = mysqli_execute_query($db, "SELECT peran, id_anggota FROM akun_pengguna WHERE username = ?", [$_SESSION["username"]]);
        $akun = mysqli_fetch_assoc($hasil);
    }
    if ($akun === null) {
        $_SESSION = [];
        header("Location: index.php");
        exit;
    }
    $_SESSION["peran"] = $akun["peran"];
    $_SESSION["id_anggota"] = $akun["id_anggota"];
    if (!in_array($akun["peran"], $peran)) {
        header("Location: index.php");
        exit;
    }
}

function awalHalaman($judul)
{
    $menu = [
        "petugas" => ["anggota.php" => "Anggota", "peminjaman.php" => "Peminjaman", "pengembalian.php" => "Pengembalian & Denda", "reservasi.php" => "Reservasi", "pengadaan.php" => "Pengadaan"],
        "admin" => ["buku.php" => "Data Buku", "akun.php" => "Akun Pengguna", "pengadaan.php" => "Pengadaan"],
        "kepala" => ["laporan.php" => "Laporan", "pengadaan.php" => "Pengadaan"],
        "anggota" => ["katalog.php" => "Katalog", "pinjaman-saya.php" => "Pinjaman Saya"],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> - SI Perpustakaan</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand bg-white border-bottom mb-4">
        <div class="container">
            <span class="navbar-brand">SI Perpustakaan</span>
            <ul class="navbar-nav me-auto">
                <?php foreach ($menu[$_SESSION["peran"]] as $file => $nama) { ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $file ?>"><?= $nama ?></a></li>
                <?php } ?>
            </ul>
            <span class="me-3"><?= e($_SESSION["username"]) ?> (<?= e($_SESSION["peran"]) ?>)</span>
            <a class="btn btn-outline-secondary btn-sm" href="logout.php">Keluar</a>
        </div>
    </nav>
    <main class="container mb-5">
        <h4 class="mb-3"><?= e($judul) ?></h4>
    <?php
}

function akhirHalaman()
{
    ?>
    </main>
</body>
</html>
    <?php
}

function pesan($teks, $jenis = "danger")
{
    if ($teks !== "") {
        echo '<div class="alert alert-' . $jenis . '">' . e($teks) . '</div>';
    }
}

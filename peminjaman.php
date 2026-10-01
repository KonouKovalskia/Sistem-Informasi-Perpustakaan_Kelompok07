<?php
// Proses 2.0 Peminjaman: 1.2 -> 2.1 -> 2.2 -> 2.3, dilayani Petugas
require "koneksi.php";
require "fungsi.php";
wajibLogin("petugas");

$pesan = "";
$info = null;
$bukti = null;
if (isset($_POST["id_anggota"])) {
    $anggota = cekStatusAnggota($db, trim($_POST["id_anggota"]));
    $info = cekKetersediaanBuku($db, trim($_POST["id_buku"]));
    if (isset($anggota["gagal"])) {
        $pesan = $anggota["gagal"];
    } elseif (!$anggota["valid"]) {
        $pesan = "Anggota tidak boleh meminjam (status " . $anggota["status_anggota"] . ", denda tertunggak " . rupiah($anggota["denda_tertunggak"]) . ")";
    } elseif (isset($info["gagal"])) {
        $pesan = $info["gagal"];
    } elseif ($info["jumlah_tersedia"] > 0) {
        $transaksi = catatPeminjaman($db, $info["id_eksemplar"], $anggota["id_anggota"]);
        if (isset($transaksi["gagal"])) {
            $pesan = $transaksi["gagal"];
        } else {
            $bukti = buatBuktiPeminjaman($transaksi);
        }
    }
}
$daftar = mysqli_fetch_all(mysqli_query($db, "SELECT p.*, a.nama_anggota, b.judul FROM peminjaman p JOIN anggota a ON a.id_anggota = p.id_anggota
    JOIN eksemplar e ON e.id_eksemplar = p.id_eksemplar JOIN buku b ON b.id_buku = e.id_buku WHERE p.status_peminjaman = 'dipinjam' ORDER BY p.id_peminjaman"), MYSQLI_ASSOC);
$katalog = mysqli_fetch_all(mysqli_query($db, "SELECT b.id_buku, b.judul, SUM(e.status_eksemplar = 'tersedia') AS tersedia, COUNT(e.id_eksemplar) AS total
    FROM buku b LEFT JOIN eksemplar e ON e.id_buku = b.id_buku GROUP BY b.id_buku ORDER BY b.id_buku"), MYSQLI_ASSOC);

awalHalaman("Peminjaman");
pesan($pesan);
?>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Catat Peminjaman</h6>
                <form method="post">
                    <input class="form-control mb-2" name="id_anggota" placeholder="ID anggota" required>
                    <input class="form-control mb-2" name="id_buku" placeholder="ID buku, contoh BK00001" required>
                    <button class="btn btn-primary">Pinjam</button>
                </form>
                <?php if ($info && !isset($info["gagal"])) { ?>
                    <div class="border rounded p-3 mt-3">
                        <strong>Info Ketersediaan Buku</strong><br>
                        <?= e($info["id_buku"]) ?> - <?= e($info["judul"]) ?><br>
                        Eksemplar tersedia: <?= $info["jumlah_tersedia"] ?>
                        <?php if ($info["jumlah_tersedia"] == 0) { ?>
                            <br><span class="text-danger">Semua eksemplar sedang dipinjam, anggota bisa reservasi.</span>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <?php if ($bukti) { ?>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Bukti Peminjaman</h6>
                    <table class="table table-sm mb-0">
                        <?php foreach ($bukti as $label => $nilai) { ?>
                            <tr><th><?= $label ?></th><td><?= e($nilai) ?></td></tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<h6 class="mt-4">Katalog</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID Buku</th><th>Judul</th><th>Tersedia</th></tr>
    <?php foreach ($katalog as $b) { ?>
        <tr><td><?= e($b["id_buku"]) ?></td><td><?= e($b["judul"]) ?></td><td><?= (int) $b["tersedia"] ?> dari <?= $b["total"] ?></td></tr>
    <?php } ?>
</table>

<h6 class="mt-4">Sedang Dipinjam</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>No. Peminjaman</th><th>Anggota</th><th>Eksemplar</th><th>Judul</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th></tr>
    <?php foreach ($daftar as $p) { ?>
        <tr>
            <td><?= e($p["id_peminjaman"]) ?></td>
            <td><?= e($p["id_anggota"]) ?> - <?= e($p["nama_anggota"]) ?></td>
            <td><?= e($p["id_eksemplar"]) ?></td>
            <td><?= e($p["judul"]) ?></td>
            <td><?= e($p["tanggal_pinjam"]) ?></td>
            <td><?= e($p["tanggal_jatuh_tempo"]) ?></td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

<?php
// Info Pinjaman Anggota (RANCANGAN-SIGNUP 4.2), hanya untuk dilihat
require "koneksi.php";
require "fungsi.php";
wajibLogin("anggota");

$info = infoPinjamanAnggota($db, $_SESSION["id_anggota"]);

awalHalaman("Pinjaman Saya");
?>
<p>ID anggota: <strong><?= e($_SESSION["id_anggota"]) ?></strong></p>

<h6>Pinjaman Aktif</h6>
<table class="table table-bordered table-sm bg-white mb-4">
    <tr><th>Judul</th><th>Eksemplar</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th></th></tr>
    <?php foreach ($info["pinjaman"] as $p) { ?>
        <tr>
            <td><?= e($p["judul"]) ?></td>
            <td><?= e($p["id_eksemplar"]) ?></td>
            <td><?= e($p["tanggal_pinjam"]) ?></td>
            <td><?= e($p["tanggal_jatuh_tempo"]) ?></td>
            <td><?= $p["terlambat"] ? '<span class="text-danger">terlambat</span>' : "" ?></td>
        </tr>
    <?php } ?>
    <?php if (!$info["pinjaman"]) { ?>
        <tr><td colspan="5">Tidak ada pinjaman</td></tr>
    <?php } ?>
</table>

<h6>Denda</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID Peminjaman</th><th>Hari Terlambat</th><th>Nominal</th><th>Status</th></tr>
    <?php foreach ($info["denda"] as $d) { ?>
        <tr>
            <td><?= e($d["id_peminjaman"]) ?></td>
            <td><?= e($d["jumlah_hari_terlambat"]) ?></td>
            <td><?= rupiah($d["nominal_denda"]) ?></td>
            <td><?= e($d["status_bayar"]) ?></td>
        </tr>
    <?php } ?>
    <?php if (!$info["denda"]) { ?>
        <tr><td colspan="4">Tidak ada denda</td></tr>
    <?php } ?>
</table>
<p class="mb-4">Denda belum lunas: <strong><?= rupiah($info["denda_belum_lunas"]) ?></strong>. Pembayaran di meja petugas.</p>

<h6>Reservasi</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID Reservasi</th><th>Judul</th><th>Tanggal</th><th>Status</th></tr>
    <?php foreach ($info["reservasi"] as $r) { ?>
        <tr>
            <td><?= e($r["id_reservasi"]) ?></td>
            <td><?= e($r["judul"]) ?></td>
            <td><?= e($r["tanggal_reservasi"]) ?></td>
            <td><?= e($r["status_reservasi"]) ?></td>
        </tr>
    <?php } ?>
    <?php if (!$info["reservasi"]) { ?>
        <tr><td colspan="4">Tidak ada reservasi</td></tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

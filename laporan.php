<?php
// Proses 3.4 Buat Laporan, untuk Kepala Perpustakaan
require "koneksi.php";
require "fungsi.php";
wajibLogin("kepala");

$periode = $_GET["periode"] ?? date("Y-m");
if (!preg_match('/^[0-9]{4}-[0-9]{2}$/', $periode)) {
    $periode = date("Y-m");
}
$laporan = buatLaporan($db, $periode);

awalHalaman("Laporan Peminjaman & Denda");
?>
<form method="get" class="d-flex gap-2 mb-3" style="max-width: 320px">
    <input class="form-control" type="month" name="periode" value="<?= e($periode) ?>">
    <button class="btn btn-primary">Tampilkan</button>
</form>
<table class="table table-bordered bg-white" style="max-width: 480px">
    <tr><th>Periode</th><td><?= e($laporan["periode"]) ?></td></tr>
    <tr><th>Total peminjaman</th><td><?= $laporan["total_peminjaman"] ?></td></tr>
    <tr><th>Total denda</th><td><?= rupiah($laporan["total_denda"]) ?></td></tr>
    <tr><th>Total denda terbayar</th><td><?= rupiah($laporan["total_denda_terbayar"]) ?></td></tr>
</table>
<h6>Peminjaman pada periode ini</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>No. Peminjaman</th><th>Anggota</th><th>Tanggal Pinjam</th><th>Tanggal Kembali</th></tr>
    <?php foreach ($laporan["peminjaman"] as $p) { ?>
        <tr>
            <td><?= e($p["id_peminjaman"]) ?></td>
            <td><?= e($p["id_anggota"]) ?></td>
            <td><?= e($p["tanggal_pinjam"]) ?></td>
            <td><?= e($p["tanggal_kembali"] ?? "belum kembali") ?></td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

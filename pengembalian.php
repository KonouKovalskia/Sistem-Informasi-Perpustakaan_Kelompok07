<?php
// Proses 3.0: 3.1 -> 3.2.1 -> 3.2.2 -> 3.2.3, dan 3.3 pembayaran denda, dilayani Petugas
require "koneksi.php";
require "fungsi.php";
wajibLogin("petugas");

$pesan = "";
$sukses = "";
if (isset($_POST["kembali"])) {
    $keterlambatan = catatPengembalian($db, trim($_POST["id_peminjaman"]));
    if (isset($keterlambatan["gagal"])) {
        $pesan = $keterlambatan["gagal"];
    } else {
        $nominal = hitungNominalDenda($db, hitungHariTerlambat($keterlambatan));
        $sukses = buatInfoDenda($nominal);
    }
}
if (isset($_POST["bayar"])) {
    $hasil = catatPembayaranDenda($db, $_POST["id_denda"], (int) $_POST["jumlah_bayar"]);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = "Denda " . $hasil["id_denda"] . " lunas, dibayar " . rupiah($hasil["jumlah_bayar"]) . " pada " . $hasil["tanggal_bayar"];
    }
}
$dipinjam = mysqli_fetch_all(mysqli_query($db, "SELECT p.id_peminjaman, p.id_anggota, p.id_eksemplar, p.tanggal_jatuh_tempo FROM peminjaman p
    WHERE p.status_peminjaman = 'dipinjam' ORDER BY p.tanggal_jatuh_tempo"), MYSQLI_ASSOC);
$denda = mysqli_fetch_all(mysqli_query($db, "SELECT d.*, p.id_anggota FROM denda d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
    WHERE d.status_bayar = 'belum lunas' ORDER BY d.id_denda"), MYSQLI_ASSOC);

awalHalaman("Pengembalian & Denda");
pesan($pesan);
pesan($sukses, "success");
?>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Catat Pengembalian</h6>
                <form method="post" class="d-flex gap-2">
                    <input class="form-control" name="id_peminjaman" placeholder="No. peminjaman, contoh PJM000001" required>
                    <button class="btn btn-primary" name="kembali" value="1">Kembalikan</button>
                </form>
            </div>
        </div>
        <h6 class="mt-4">Sedang Dipinjam</h6>
        <table class="table table-bordered table-sm bg-white">
            <tr><th>No. Peminjaman</th><th>Anggota</th><th>Eksemplar</th><th>Jatuh Tempo</th></tr>
            <?php foreach ($dipinjam as $p) { ?>
                <tr>
                    <td><?= e($p["id_peminjaman"]) ?></td>
                    <td><?= e($p["id_anggota"]) ?></td>
                    <td><?= e($p["id_eksemplar"]) ?></td>
                    <td><?= e($p["tanggal_jatuh_tempo"]) ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Denda Belum Lunas</h6>
                <table class="table table-sm">
                    <tr><th>ID Denda</th><th>Anggota</th><th>Terlambat</th><th>Nominal</th><th></th></tr>
                    <?php foreach ($denda as $d) { ?>
                        <tr>
                            <td><?= e($d["id_denda"]) ?></td>
                            <td><?= e($d["id_anggota"]) ?></td>
                            <td><?= $d["jumlah_hari_terlambat"] ?> hari</td>
                            <td><?= rupiah($d["nominal_denda"]) ?></td>
                            <td>
                                <form method="post" class="d-flex gap-1">
                                    <input type="hidden" name="id_denda" value="<?= e($d["id_denda"]) ?>">
                                    <input class="form-control form-control-sm" type="number" name="jumlah_bayar" value="<?= $d["nominal_denda"] ?>" style="width: 100px">
                                    <button class="btn btn-success btn-sm" name="bayar" value="1">Bayar</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php akhirHalaman(); ?>

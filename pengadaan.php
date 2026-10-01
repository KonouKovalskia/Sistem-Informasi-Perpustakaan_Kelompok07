<?php
// Proses 6.0 Pengadaan Buku
// Admin/Petugas: usulan (tambalan celah 4) dan 6.3 Terima Buku; Kepala: 6.1 -> 6.2
require "koneksi.php";
require "fungsi.php";
wajibLogin("admin", "petugas", "kepala");
$kepala = $_SESSION["peran"] === "kepala";

$pesan = "";
$sukses = "";
$pesanan = null;
if (isset($_POST["usul"]) && !$kepala) {
    $hasil = usulPengadaan($db, $_POST["jumlah"] ?? []);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = "Usulan " . $hasil["id_pengadaan"] . " dikirim ke Kepala Perpustakaan";
    }
}
if (isset($_POST["persetujuan"]) && $kepala) {
    $disetujui = prosesPersetujuan($db, $_POST["id_pengadaan"], $_POST["persetujuan"]);
    if (isset($disetujui["gagal"])) {
        $pesan = $disetujui["gagal"];
    } else {
        $pesanan = kirimPesanan($db, $disetujui);
    }
}
if (isset($_POST["terima"]) && !$kepala) {
    $hasil = terimaBuku($db, trim($_POST["no_faktur"]), $_POST["id_pengadaan"], $_POST["diterima"] ?? []);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = "Buku pengadaan " . $hasil["id_pengadaan"] . " diterima dan masuk katalog";
    }
}
$semua = mysqli_fetch_all(mysqli_query($db, "SELECT * FROM pengadaan ORDER BY id_pengadaan DESC"), MYSQLI_ASSOC);
$katalog = mysqli_fetch_all(mysqli_query($db, "SELECT id_buku, judul FROM buku ORDER BY id_buku"), MYSQLI_ASSOC);

awalHalaman("Pengadaan Buku");
pesan($pesan);
pesan($sukses, "success");
?>
<?php if ($pesanan) { ?>
    <div class="card mb-4" style="max-width: 560px">
        <div class="card-body">
            <h6 class="card-title">Pesanan Pengadaan Buku <?= e($pesanan["id_pengadaan"]) ?></h6>
            <p class="mb-2">Tanggal pesan: <?= e($pesanan["tanggal_pesan"]) ?>. Kirim daftar ini ke penerbit/supplier.</p>
            <table class="table table-sm mb-0">
                <tr><th>ID Buku</th><th>Judul</th><th>Jumlah</th></tr>
                <?php foreach ($pesanan["daftar_buku"] as $d) { ?>
                    <tr><td><?= e($d["id_buku"]) ?></td><td><?= e($d["judul"]) ?></td><td><?= $d["jumlah_pesan"] ?></td></tr>
                <?php } ?>
            </table>
        </div>
    </div>
<?php } ?>

<?php if (!$kepala) { ?>
    <div class="card mb-4" style="max-width: 560px">
        <div class="card-body">
            <h6 class="card-title">Buat Usulan Pengadaan</h6>
            <form method="post">
                <table class="table table-sm">
                    <tr><th>Buku</th><th style="width: 110px">Jumlah</th></tr>
                    <?php foreach ($katalog as $b) { ?>
                        <tr>
                            <td><?= e($b["id_buku"]) ?> - <?= e($b["judul"]) ?></td>
                            <td><input class="form-control form-control-sm" type="number" min="0" max="99" name="jumlah[<?= e($b["id_buku"]) ?>]" value="0"></td>
                        </tr>
                    <?php } ?>
                </table>
                <button class="btn btn-primary" name="usul" value="1">Kirim Usulan</button>
            </form>
        </div>
    </div>
<?php } ?>

<?php foreach ($semua as $p) {
    $daftarBuku = daftarBukuPengadaan($db, $p["id_pengadaan"]); ?>
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title"><?= e($p["id_pengadaan"]) ?> <span class="badge text-bg-secondary"><?= e($p["status_pengadaan"]) ?></span></h6>
            <p class="mb-2 small">
                Tanggal pesan: <?= e($p["tanggal_pesan"] ?? "-") ?>,
                no. faktur: <?= e($p["no_faktur"] ?? "-") ?>,
                tanggal terima: <?= e($p["tanggal_terima"] ?? "-") ?>
            </p>
            <form method="post">
                <input type="hidden" name="id_pengadaan" value="<?= e($p["id_pengadaan"]) ?>">
                <table class="table table-sm">
                    <tr><th>Buku</th><th>Jumlah Pesan</th><th>Jumlah Diterima</th></tr>
                    <?php foreach ($daftarBuku as $d) { ?>
                        <tr>
                            <td><?= e($d["id_buku"]) ?> - <?= e($d["judul"]) ?></td>
                            <td><?= $d["jumlah_pesan"] ?></td>
                            <td>
                                <?php if (!$kepala && $p["status_pengadaan"] === "dipesan") { ?>
                                    <input class="form-control form-control-sm" type="number" min="0" max="<?= $d["jumlah_pesan"] ?>" name="diterima[<?= e($d["id_buku"]) ?>]" value="<?= $d["jumlah_pesan"] ?>" style="width: 100px">
                                <?php } else { ?>
                                    <?= $d["jumlah_diterima"] ?? "-" ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
                <?php if ($kepala && $p["status_pengadaan"] === "diusulkan") { ?>
                    <button class="btn btn-success btn-sm" name="persetujuan" value="disetujui">Setujui dan pesan</button>
                    <button class="btn btn-outline-danger btn-sm" name="persetujuan" value="ditolak">Tolak</button>
                <?php } ?>
                <?php if (!$kepala && $p["status_pengadaan"] === "dipesan") { ?>
                    <div class="d-flex gap-2" style="max-width: 420px">
                        <input class="form-control form-control-sm" name="no_faktur" placeholder="No. faktur dari penerbit" maxlength="30" required>
                        <button class="btn btn-primary btn-sm" name="terima" value="1">Terima Buku</button>
                    </div>
                <?php } ?>
            </form>
        </div>
    </div>
<?php } ?>
<?php akhirHalaman(); ?>

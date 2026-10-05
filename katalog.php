<?php
// Katalog dan reservasi oleh Anggota (RANCANGAN-SIGNUP 4.1), sesuai DFD: Anggota ke 4.1
require "koneksi.php";
require "fungsi.php";
wajibLogin("anggota");

$pesan = "";
$sukses = $_SESSION["pesan"] ?? "";
unset($_SESSION["pesan"]);
if (isset($_POST["id_buku"])) {
    // id anggota selalu dari session, bukan dari form
    $permintaan = terimaPermintaanReservasi($_SESSION["id_anggota"], trim($_POST["id_buku"]));
    $hasil = isset($permintaan["gagal"]) ? $permintaan : catatReservasi($db, $permintaan);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = "Reservasi " . $hasil["id_reservasi"] . " untuk buku " . $hasil["id_buku"] . " tercatat, status " . $hasil["status_reservasi"];
    }
}
$kata = trim($_GET["q"] ?? "");
$daftar = cariBuku($db, $kata);

awalHalaman("Katalog");
pesan($pesan);
pesan($sukses, "success");
?>
<form class="d-flex gap-2 mb-3" style="max-width: 480px">
    <input class="form-control" name="q" value="<?= e($kata) ?>" placeholder="Cari judul atau pengarang">
    <button class="btn btn-outline-primary">Cari</button>
</form>
<table class="table table-bordered table-sm bg-white">
    <tr><th>Judul</th><th>Pengarang</th><th>Penerbit</th><th>Tahun</th><th>Tersedia</th><th></th></tr>
    <?php foreach ($daftar as $b) { ?>
        <tr>
            <td><?= e($b["judul"]) ?></td>
            <td><?= e($b["pengarang"]) ?></td>
            <td><?= e($b["penerbit"]) ?></td>
            <td><?= e($b["tahun_terbit"]) ?></td>
            <td><?= e($b["tersedia"]) ?></td>
            <td>
                <?php if ($b["tersedia"] == 0) { ?>
                    <form method="post">
                        <input type="hidden" name="id_buku" value="<?= e($b["id_buku"]) ?>">
                        <button class="btn btn-outline-primary btn-sm">Reservasi</button>
                    </form>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
    <?php if (!$daftar) { ?>
        <tr><td colspan="6">Buku tidak ditemukan</td></tr>
    <?php } ?>
</table>
<p class="small text-muted">Buku yang masih tersedia bisa langsung dipinjam di meja petugas.</p>
<?php akhirHalaman(); ?>

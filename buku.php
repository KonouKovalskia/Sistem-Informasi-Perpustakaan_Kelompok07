<?php
// Proses 5.1 Kelola Data Buku, untuk Admin
require "koneksi.php";
require "fungsi.php";
wajibLogin("admin");

$pesan = "";
$sukses = "";
if (isset($_POST["aksi"])) {
    $data = array_map("trim", $_POST);
    $hasil = kelolaDataBuku($db, $_POST["aksi"], $data);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = $hasil["pesan_hasil"] . " (" . $hasil["id_buku"] . ")";
    }
}
// form diisi data lama kalau sedang mengubah
$ubah = null;
if (isset($_GET["ubah"])) {
    $ubah = mysqli_fetch_assoc(mysqli_execute_query($db, "SELECT * FROM buku WHERE id_buku = ?", [$_GET["ubah"]]));
}
$daftar = mysqli_fetch_all(mysqli_query($db, "SELECT b.*, COUNT(e.id_eksemplar) AS eksemplar FROM buku b LEFT JOIN eksemplar e ON e.id_buku = b.id_buku
    GROUP BY b.id_buku ORDER BY b.id_buku"), MYSQLI_ASSOC);

awalHalaman("Data Buku");
pesan($pesan);
pesan($sukses, "success");
?>
<div class="card mb-4" style="max-width: 560px">
    <div class="card-body">
        <h6 class="card-title"><?= $ubah ? "Ubah Buku " . e($ubah["id_buku"]) : "Tambah Buku" ?></h6>
        <form method="post" action="buku.php">
            <input type="hidden" name="aksi" value="<?= $ubah ? "ubah" : "tambah" ?>">
            <input type="hidden" name="id_buku" value="<?= e($ubah["id_buku"] ?? "") ?>">
            <input class="form-control mb-2" name="judul" placeholder="Judul" maxlength="200" value="<?= e($ubah["judul"] ?? "") ?>" required>
            <input class="form-control mb-2" name="pengarang" placeholder="Pengarang" maxlength="100" value="<?= e($ubah["pengarang"] ?? "") ?>" required>
            <input class="form-control mb-2" name="penerbit" placeholder="Penerbit" maxlength="100" value="<?= e($ubah["penerbit"] ?? "") ?>" required>
            <input class="form-control mb-2" name="tahun_terbit" placeholder="Tahun terbit" maxlength="4" value="<?= e($ubah["tahun_terbit"] ?? "") ?>" required>
            <?php if (!$ubah) { ?>
                <input class="form-control mb-2" type="number" name="jumlah_eksemplar" placeholder="Jumlah eksemplar" min="1" max="99" required>
            <?php } ?>
            <button class="btn btn-primary">Simpan</button>
            <?php if ($ubah) { ?><a class="btn btn-secondary" href="buku.php">Batal</a><?php } ?>
        </form>
    </div>
</div>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID</th><th>Judul</th><th>Pengarang</th><th>Penerbit</th><th>Tahun</th><th>Eksemplar</th><th></th></tr>
    <?php foreach ($daftar as $b) { ?>
        <tr>
            <td><?= e($b["id_buku"]) ?></td>
            <td><?= e($b["judul"]) ?></td>
            <td><?= e($b["pengarang"]) ?></td>
            <td><?= e($b["penerbit"]) ?></td>
            <td><?= e($b["tahun_terbit"]) ?></td>
            <td><?= $b["eksemplar"] ?></td>
            <td class="d-flex gap-1">
                <a class="btn btn-outline-primary btn-sm" href="buku.php?ubah=<?= e($b["id_buku"]) ?>">Ubah</a>
                <form method="post">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id_buku" value="<?= e($b["id_buku"]) ?>">
                    <button class="btn btn-outline-danger btn-sm">Hapus</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

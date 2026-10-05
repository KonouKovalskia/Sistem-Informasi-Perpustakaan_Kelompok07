<?php
// Proses 5.2 Kelola Akun Pengguna, untuk Admin
require "koneksi.php";
require "fungsi.php";
wajibLogin("admin");

$pesan = "";
$sukses = "";
if (isset($_POST["aksi"])) {
    $hasil = kelolaAkunPengguna($db, $_POST["aksi"], trim($_POST["username"]), $_POST["password"] ?? "", $_POST["peran"] ?? "");
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $sukses = $hasil["pesan_hasil"] . " (" . $hasil["username"] . ")";
    }
}
$daftar = mysqli_fetch_all(mysqli_query($db, "SELECT id_akun, username, peran, id_anggota FROM akun_pengguna ORDER BY id_akun"), MYSQLI_ASSOC);
// akun anggota hanya dibuat lewat sign up (daftar.php)
$peranTambah = ["admin", "petugas", "kepala"];
$semuaPeran = ["admin", "petugas", "kepala", "anggota"];

awalHalaman("Akun Pengguna");
pesan($pesan);
pesan($sukses, "success");
?>
<div class="card mb-4" style="max-width: 480px">
    <div class="card-body">
        <h6 class="card-title">Tambah Akun</h6>
        <form method="post">
            <input type="hidden" name="aksi" value="tambah">
            <input class="form-control mb-2" name="username" placeholder="Username" maxlength="30" required>
            <input class="form-control mb-2" type="password" name="password" placeholder="Password (minimal 8 karakter)" required>
            <select class="form-select mb-2" name="peran">
                <?php foreach ($peranTambah as $p) { ?><option><?= $p ?></option><?php } ?>
            </select>
            <button class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID</th><th>Username</th><th>ID Anggota</th><th>Ubah peran / password</th><th></th></tr>
    <?php foreach ($daftar as $a) { ?>
        <tr>
            <td><?= e($a["id_akun"]) ?></td>
            <td><?= e($a["username"]) ?></td>
            <td><?= e($a["id_anggota"] ?? "-") ?></td>
            <td>
                <form method="post" class="d-flex gap-1">
                    <input type="hidden" name="aksi" value="ubah">
                    <input type="hidden" name="username" value="<?= e($a["username"]) ?>">
                    <select class="form-select form-select-sm" name="peran">
                        <?php foreach ($semuaPeran as $p) { ?>
                            <option <?= $p === $a["peran"] ? "selected" : "" ?>><?= $p ?></option>
                        <?php } ?>
                    </select>
                    <input class="form-control form-control-sm" type="password" name="password" placeholder="Password baru (boleh kosong)">
                    <button class="btn btn-outline-primary btn-sm">Ubah</button>
                </form>
            </td>
            <td>
                <form method="post">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="username" value="<?= e($a["username"]) ?>">
                    <button class="btn btn-outline-danger btn-sm">Hapus</button>
                </form>
            </td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

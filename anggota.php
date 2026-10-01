<?php
// Proses 1.0 Verifikasi Anggota (1.1 dan 1.2), dilayani Petugas (tambalan celah 7)
require "koneksi.php";
require "fungsi.php";
wajibLogin("petugas");

$pesan = "";
$kartu = null;
$status = null;
if (isset($_POST["registrasi"])) {
    $hasil = registrasiAnggota($db, trim($_POST["nama_anggota"]), trim($_POST["alamat"]), trim($_POST["no_telepon"]), trim($_POST["email"]));
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $kartu = $hasil;
    }
}
if (isset($_GET["cek"])) {
    $hasil = cekStatusAnggota($db, trim($_GET["cek"]));
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $status = $hasil;
    }
}
$daftar = mysqli_fetch_all(mysqli_query($db, "SELECT * FROM anggota ORDER BY id_anggota"), MYSQLI_ASSOC);

awalHalaman("Anggota");
pesan($pesan);
?>
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Registrasi Anggota Baru</h6>
                <form method="post">
                    <input class="form-control mb-2" name="nama_anggota" placeholder="Nama" maxlength="100" required>
                    <input class="form-control mb-2" name="alamat" placeholder="Alamat" maxlength="200" required>
                    <input class="form-control mb-2" name="no_telepon" placeholder="No. telepon" maxlength="15" required>
                    <input class="form-control mb-2" type="email" name="email" placeholder="Email (boleh kosong)" maxlength="130">
                    <button class="btn btn-primary" name="registrasi" value="1">Daftarkan</button>
                </form>
                <?php if ($kartu) { ?>
                    <div class="border rounded p-3 mt-3">
                        <strong>Kartu Anggota</strong><br>
                        ID: <?= e($kartu["id_anggota"]) ?><br>
                        Nama: <?= e($kartu["nama_anggota"]) ?><br>
                        Tanggal daftar: <?= e($kartu["tanggal_daftar"]) ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Cek Status Anggota</h6>
                <form method="get" class="d-flex gap-2">
                    <input class="form-control" name="cek" placeholder="ID anggota, contoh AGT00001" required>
                    <button class="btn btn-primary">Cek</button>
                </form>
                <?php if ($status) { ?>
                    <div class="border rounded p-3 mt-3">
                        <?= e($status["id_anggota"]) ?> - <?= e($status["nama_anggota"]) ?><br>
                        Status: <?= e($status["status_anggota"]) ?><br>
                        Denda tertunggak: <?= rupiah($status["denda_tertunggak"]) ?><br>
                        <?php if ($status["valid"]) { ?>
                            <span class="text-success">Boleh meminjam</span>
                        <?php } else { ?>
                            <span class="text-danger">Anggota tidak boleh meminjam</span>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<h6 class="mt-4">Daftar Anggota</h6>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID</th><th>Nama</th><th>No. Telepon</th><th>Tanggal Daftar</th><th>Status</th><th>Denda Tertunggak</th></tr>
    <?php foreach ($daftar as $a) { ?>
        <tr>
            <td><?= e($a["id_anggota"]) ?></td>
            <td><?= e($a["nama_anggota"]) ?></td>
            <td><?= e($a["no_telepon"]) ?></td>
            <td><?= e($a["tanggal_daftar"]) ?></td>
            <td><?= e($a["status_anggota"]) ?></td>
            <td><?= rupiah($a["denda_tertunggak"]) ?></td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

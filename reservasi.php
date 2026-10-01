<?php
// Proses 4.0 Reservasi: 4.1 -> 4.2, dilayani Petugas (tambalan celah 7)
require "koneksi.php";
require "fungsi.php";
wajibLogin("petugas");

$pesan = "";
$status = null;
if (isset($_POST["id_anggota"])) {
    $permintaan = terimaPermintaanReservasi(trim($_POST["id_anggota"]), trim($_POST["id_buku"]));
    $hasil = isset($permintaan["gagal"]) ? $permintaan : catatReservasi($db, $permintaan);
    if (isset($hasil["gagal"])) {
        $pesan = $hasil["gagal"];
    } else {
        $status = $hasil;
    }
}
$daftar = mysqli_fetch_all(mysqli_query($db, "SELECT r.*, a.nama_anggota, b.judul FROM reservasi r JOIN anggota a ON a.id_anggota = r.id_anggota
    JOIN buku b ON b.id_buku = r.id_buku ORDER BY r.id_reservasi DESC"), MYSQLI_ASSOC);

awalHalaman("Reservasi");
pesan($pesan);
if ($status) {
    pesan("Status Reservasi: " . $status["id_reservasi"] . " untuk buku " . $status["id_buku"] . ", status " . $status["status_reservasi"], "success");
}
?>
<div class="card mb-4" style="max-width: 480px">
    <div class="card-body">
        <h6 class="card-title">Reservasi Buku</h6>
        <form method="post">
            <input class="form-control mb-2" name="id_anggota" placeholder="ID anggota" required>
            <input class="form-control mb-2" name="id_buku" placeholder="ID buku" required>
            <button class="btn btn-primary">Reservasi</button>
        </form>
    </div>
</div>
<table class="table table-bordered table-sm bg-white">
    <tr><th>ID Reservasi</th><th>Anggota</th><th>Buku</th><th>Tanggal</th><th>Status</th></tr>
    <?php foreach ($daftar as $r) { ?>
        <tr>
            <td><?= e($r["id_reservasi"]) ?></td>
            <td><?= e($r["id_anggota"]) ?> - <?= e($r["nama_anggota"]) ?></td>
            <td><?= e($r["id_buku"]) ?> - <?= e($r["judul"]) ?></td>
            <td><?= e($r["tanggal_reservasi"]) ?></td>
            <td><?= e($r["status_reservasi"]) ?></td>
        </tr>
    <?php } ?>
</table>
<?php akhirHalaman(); ?>

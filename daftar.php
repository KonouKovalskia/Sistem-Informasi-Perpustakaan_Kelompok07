<?php
// Sign up anggota (RANCANGAN-SIGNUP 3.2): 1.1 Registrasi Anggota Baru langsung oleh Anggota, sesuai DFD
require "koneksi.php";
require "fungsi.php";

if (isset($_SESSION["peran"])) {
    header("Location: " . HALAMAN_AWAL[$_SESSION["peran"]]);
    exit;
}

$pesan = "";
$isian = ["username" => "", "nama_anggota" => "", "alamat" => "", "no_telepon" => "", "email" => ""];
if (isset($_POST["username"])) {
    foreach ($isian as $kolom => $nilai) {
        $isian[$kolom] = trim($_POST[$kolom] ?? "");
    }
    // anggota dan akun dibuat bersama; kalau salah satu gagal, keduanya batal
    mysqli_begin_transaction($db);
    $hasil = daftarAnggota($db, $isian + ["password" => $_POST["password"] ?? "", "ulangi_password" => $_POST["ulangi_password"] ?? ""]);
    if (isset($hasil["gagal"])) {
        mysqli_rollback($db);
        $pesan = $hasil["gagal"];
    } else {
        mysqli_commit($db);
        masukSesi($hasil["username"], "anggota", $hasil["id_anggota"]);
        $_SESSION["pesan"] = "Pendaftaran berhasil. ID anggota: " . $hasil["id_anggota"];
        header("Location: katalog.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - SI Perpustakaan</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Daftar Anggota</h5>
                        <?php pesan($pesan); ?>
                        <form method="post">
                            <div class="mb-2">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" maxlength="30" value="<?= e($isian["username"]) ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="password">Password (minimal 8 karakter)</label>
                                <input class="form-control" type="password" id="password" name="password" minlength="8" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="ulangi_password">Ulangi password</label>
                                <input class="form-control" type="password" id="ulangi_password" name="ulangi_password" minlength="8" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="nama_anggota">Nama</label>
                                <input class="form-control" id="nama_anggota" name="nama_anggota" maxlength="100" value="<?= e($isian["nama_anggota"]) ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="alamat">Alamat</label>
                                <input class="form-control" id="alamat" name="alamat" maxlength="200" value="<?= e($isian["alamat"]) ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" for="no_telepon">No. telepon</label>
                                <input class="form-control" id="no_telepon" name="no_telepon" maxlength="15" inputmode="numeric" value="<?= e($isian["no_telepon"]) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="email">Email (boleh kosong)</label>
                                <input class="form-control" type="email" id="email" name="email" maxlength="130" value="<?= e($isian["email"]) ?>">
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Daftar</button>
                        </form>
                        <p class="mt-3 mb-0 small">Sudah punya akun? <a href="index.php">Masuk</a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

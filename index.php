<?php
// tambalan celah 6: login semua pengguna (Admin, Petugas, Kepala, Anggota)
require "koneksi.php";

if (isset($_SESSION["peran"])) {
    header("Location: " . HALAMAN_AWAL[$_SESSION["peran"]]);
    exit;
}

$pesan = "";
if (isset($_POST["username"])) {
    $hasil = mysqli_execute_query($db, "SELECT username, password, peran, id_anggota FROM akun_pengguna WHERE username = ?", [$_POST["username"]]);
    $akun = mysqli_fetch_assoc($hasil);
    if ($akun !== null && password_verify($_POST["password"], $akun["password"])) {
        masukSesi($akun["username"], $akun["peran"], $akun["id_anggota"]);
        header("Location: " . HALAMAN_AWAL[$akun["peran"]]);
        exit;
    }
    $pesan = "Username atau password salah.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SI Perpustakaan</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">SI Perpustakaan</h5>
                        <?php pesan($pesan); ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" type="text" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" type="password" id="password" name="password" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Masuk</button>
                        </form>
                        <p class="mt-3 mb-1 small">Belum punya akun? <a href="daftar.php">Daftar</a></p>
                        <p class="mb-0 small text-muted">Lupa password? Hubungi admin.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

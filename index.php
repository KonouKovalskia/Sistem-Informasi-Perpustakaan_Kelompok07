<?php
// tambalan celah 6: login Admin, Petugas, dan Kepala Perpustakaan
require "koneksi.php";

$halamanAwal = ["petugas" => "anggota.php", "admin" => "buku.php", "kepala" => "laporan.php"];
if (isset($_SESSION["peran"])) {
    header("Location: " . $halamanAwal[$_SESSION["peran"]]);
    exit;
}

$pesan = "";
if (isset($_POST["username"])) {
    $hasil = mysqli_execute_query($db, "SELECT username, password, peran FROM akun_pengguna WHERE username = ?", [$_POST["username"]]);
    $akun = mysqli_fetch_assoc($hasil);
    if ($akun !== null && password_verify($_POST["password"], $akun["password"])) {
        session_regenerate_id(true);
        $_SESSION["username"] = $akun["username"];
        $_SESSION["peran"] = $akun["peran"];
        header("Location: " . $halamanAwal[$akun["peran"]]);
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
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

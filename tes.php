<?php
// Uji semua fungsi proses. Dijalankan dalam transaksi lalu di-rollback, jadi data tidak berubah.
// Buka: http://localhost/perpustakaan/tes.php
require "koneksi.php";
require "fungsi.php";
header("Content-Type: text/plain");

$gagal = 0;
function cek($nama, $kondisi)
{
    global $gagal;
    echo ($kondisi ? "OK    " : "GAGAL ") . $nama . "\n";
    if (!$kondisi) {
        $gagal++;
    }
}
function ambil($db, $sql, $param = [])
{
    return mysqli_fetch_assoc(mysqli_execute_query($db, $sql, $param));
}

mysqli_begin_transaction($db);

// 1.1 dan 1.2
cek("1.1 data kurang ditolak", isset(registrasiAnggota($db, "Budi", "", "081234567890", "")["gagal"]));
cek("1.1 telepon salah ditolak", isset(registrasiAnggota($db, "Budi", "Bandung", "08ab", "")["gagal"]));
$kartu = registrasiAnggota($db, "Budi", "Bandung", "081234567890", "budi@contoh.id");
cek("1.1 kartu anggota format AGT+5 digit", preg_match('/^AGT[0-9]{5}$/', $kartu["id_anggota"]) === 1);
$kartu2 = registrasiAnggota($db, "Sari", "Bandung", "081234567891", "");
$budi = $kartu["id_anggota"];
cek("1.2 anggota tidak ada", isset(cekStatusAnggota($db, "AGT99999")["gagal"]));
cek("1.2 anggota baru valid", cekStatusAnggota($db, $budi)["valid"] === true);

// 2.1, 2.2, 2.3
$info = cekKetersediaanBuku($db, "BK00003");
cek("2.1 buku tidak ada", isset(cekKetersediaanBuku($db, "BK99999")["gagal"]));
cek("2.1 BK00003 tersedia 1", $info["jumlah_tersedia"] === 1);
$transaksi = catatPeminjaman($db, $info["id_eksemplar"], $budi);
cek("2.2 jatuh tempo = pinjam + 7 hari", $transaksi["tanggal_jatuh_tempo"] === date("Y-m-d", strtotime("+7 days")));
cek("2.2 eksemplar jadi dipinjam (celah 1)", ambil($db, "SELECT status_eksemplar FROM eksemplar WHERE id_eksemplar = ?", [$info["id_eksemplar"]])["status_eksemplar"] === "dipinjam");
cek("2.2 eksemplar yang sama tidak bisa dipinjam lagi", isset(catatPeminjaman($db, $info["id_eksemplar"], $budi)["gagal"]));
cek("2.1 BK00003 tersedia 0", cekKetersediaanBuku($db, "BK00003")["jumlah_tersedia"] === 0);
cek("2.3 bukti peminjaman", buatBuktiPeminjaman($transaksi)["No. Peminjaman"] === $transaksi["id_peminjaman"]);

// 4.1 dan 4.2
cek("4.1 data kosong ditolak", isset(terimaPermintaanReservasi("", "BK00003")["gagal"]));
cek("4.2 buku masih tersedia ditolak (celah 5)", isset(catatReservasi($db, terimaPermintaanReservasi($budi, "BK00001"))["gagal"]));
$reservasi = catatReservasi($db, terimaPermintaanReservasi($kartu2["id_anggota"], "BK00003"));
cek("4.2 reservasi menunggu", $reservasi["status_reservasi"] === "menunggu");

// 3.1, 3.2.1, 3.2.2, 3.2.3: dibuat terlambat 3 hari
mysqli_execute_query($db, "UPDATE peminjaman SET tanggal_jatuh_tempo = ? WHERE id_peminjaman = ?", [date("Y-m-d", strtotime("-3 days")), $transaksi["id_peminjaman"]]);
$keterlambatan = catatPengembalian($db, $transaksi["id_peminjaman"]);
cek("3.1 kembali dua kali ditolak", isset(catatPengembalian($db, $transaksi["id_peminjaman"])["gagal"]));
cek("3.1 eksemplar tersedia lagi (celah 1)", cekKetersediaanBuku($db, "BK00003")["jumlah_tersedia"] === 1);
cek("3.1 reservasi siap diambil (celah 5)", ambil($db, "SELECT status_reservasi FROM reservasi WHERE id_reservasi = ?", [$reservasi["id_reservasi"]])["status_reservasi"] === "siap diambil");
$hari = hitungHariTerlambat($keterlambatan);
cek("3.2.1 terlambat 3 hari", $hari["jumlah_hari_terlambat"] === 3);
cek("3.2.1 kembali lebih awal = 0", hitungHariTerlambat(["id_peminjaman" => "x", "tanggal_jatuh_tempo" => "2026-10-10", "tanggal_kembali" => "2026-10-01"])["jumlah_hari_terlambat"] === 0);
$nominal = hitungNominalDenda($db, $hari);
cek("3.2.2 denda Rp3.000", $nominal["nominal_denda"] === 3000);
cek("3.2.2 denda_tertunggak naik (celah 2)", ambil($db, "SELECT denda_tertunggak FROM anggota WHERE id_anggota = ?", [$budi])["denda_tertunggak"] == 3000);
cek("1.2 anggota berdenda tidak valid", cekStatusAnggota($db, $budi)["valid"] === false);
cek("3.2.2 tidak terlambat, tidak ada denda", hitungNominalDenda($db, ["id_peminjaman" => "x", "jumlah_hari_terlambat" => 0])["nominal_denda"] === 0);
cek("3.2.3 info denda", buatInfoDenda($nominal) === "Peminjaman " . $transaksi["id_peminjaman"] . " terlambat 3 hari, denda Rp3.000");

// 3.3 dan 3.4
$idDenda = ambil($db, "SELECT id_denda FROM denda WHERE id_peminjaman = ?", [$transaksi["id_peminjaman"]])["id_denda"];
cek("3.3 bayar 0 ditolak", isset(catatPembayaranDenda($db, $idDenda, 0)["gagal"]));
cek("3.3 bayar kurang ditolak", isset(catatPembayaranDenda($db, $idDenda, 1000)["gagal"]));
cek("3.3 bayar lunas", catatPembayaranDenda($db, $idDenda, 3000)["jumlah_bayar"] === 3000);
cek("3.3 bayar dua kali ditolak", isset(catatPembayaranDenda($db, $idDenda, 3000)["gagal"]));
cek("3.3 denda_tertunggak kembali 0 (celah 2)", cekStatusAnggota($db, $budi)["valid"] === true);
$laporan = buatLaporan($db, date("Y-m"));
cek("3.4 laporan memuat peminjaman", in_array($transaksi["id_peminjaman"], array_column($laporan["peminjaman"], "id_peminjaman")));
cek("3.4 total denda dan terbayar", $laporan["total_denda"] >= 3000 && $laporan["total_denda_terbayar"] >= 3000);

// 5.1
$data = ["judul" => "Buku Uji", "pengarang" => "Penguji", "penerbit" => "Uji", "tahun_terbit" => "2025", "jumlah_eksemplar" => "2"];
cek("5.1 tahun salah ditolak", isset(kelolaDataBuku($db, "tambah", ["tahun_terbit" => "25"] + $data)["gagal"]));
$buku = kelolaDataBuku($db, "tambah", $data);
cek("5.1 tambah buku + 2 eksemplar", ambil($db, "SELECT COUNT(*) AS n FROM eksemplar WHERE id_buku = ?", [$buku["id_buku"]])["n"] == 2);
kelolaDataBuku($db, "ubah", ["id_buku" => $buku["id_buku"], "judul" => "Buku Uji Baru"] + $data);
cek("5.1 ubah judul", ambil($db, "SELECT judul FROM buku WHERE id_buku = ?", [$buku["id_buku"]])["judul"] === "Buku Uji Baru");
cek("5.1 buku berriwayat tidak bisa dihapus", isset(kelolaDataBuku($db, "hapus", ["id_buku" => "BK00003"])["gagal"]));
kelolaDataBuku($db, "hapus", ["id_buku" => $buku["id_buku"]]);
cek("5.1 hapus buku", ambil($db, "SELECT COUNT(*) AS n FROM buku WHERE id_buku = ?", [$buku["id_buku"]])["n"] == 0);
cek("5.1 aksi tidak dikenal", isset(kelolaDataBuku($db, "salin", $data)["gagal"]));

// 5.2
cek("5.2 peran salah ditolak", isset(kelolaAkunPengguna($db, "tambah", "ujicoba", "rahasia123", "tamu")["gagal"]));
cek("5.2 password pendek ditolak", isset(kelolaAkunPengguna($db, "tambah", "ujicoba", "123", "petugas")["gagal"]));
kelolaAkunPengguna($db, "tambah", "ujicoba", "rahasia123", "petugas");
$akun = ambil($db, "SELECT * FROM akun_pengguna WHERE username = 'ujicoba'");
cek("5.2 password disimpan sebagai hash", password_verify("rahasia123", $akun["password"]));
cek("5.2 id akun AKN+5 digit", preg_match('/^AKN[0-9]{5}$/', $akun["id_akun"]) === 1);
cek("5.2 username dobel ditolak", isset(kelolaAkunPengguna($db, "tambah", "ujicoba", "rahasia123", "petugas")["gagal"]));
kelolaAkunPengguna($db, "ubah", "ujicoba", "", "kepala");
cek("5.2 ubah peran", ambil($db, "SELECT peran FROM akun_pengguna WHERE username = 'ujicoba'")["peran"] === "kepala");
kelolaAkunPengguna($db, "hapus", "ujicoba", "", "");
cek("5.2 hapus akun", ambil($db, "SELECT COUNT(*) AS n FROM akun_pengguna WHERE username = 'ujicoba'")["n"] == 0);

// usulan (celah 4), 6.1, 6.2, 6.3
$eksemplarAwal = ambil($db, "SELECT COUNT(*) AS n FROM eksemplar WHERE id_buku = 'BK00001'")["n"];
cek("usulan kosong ditolak", isset(usulPengadaan($db, ["BK00001" => "0"])["gagal"]));
$tolak = usulPengadaan($db, ["BK00002" => "1"]);
cek("6.1 ditolak", isset(prosesPersetujuan($db, $tolak["id_pengadaan"], "ditolak")["gagal"]) && statusPengadaan($db, $tolak["id_pengadaan"]) === "ditolak");
$usul = usulPengadaan($db, ["BK00001" => "3"]);
cek("6.3 belum dipesan ditolak", isset(terimaBuku($db, "F-001", $usul["id_pengadaan"], ["BK00001" => 3])["gagal"]));
$pesanan = kirimPesanan($db, prosesPersetujuan($db, $usul["id_pengadaan"], "disetujui"));
cek("6.2 status dipesan", statusPengadaan($db, $usul["id_pengadaan"]) === "dipesan" && $pesanan["daftar_buku"][0]["jumlah_pesan"] == 3);
cek("6.1 disetujui dua kali ditolak", isset(prosesPersetujuan($db, $usul["id_pengadaan"], "disetujui")["gagal"]));
cek("6.3 tanpa faktur ditolak", isset(terimaBuku($db, "", $usul["id_pengadaan"], ["BK00001" => 2])["gagal"]));
terimaBuku($db, "F-001", $usul["id_pengadaan"], ["BK00001" => 2]);
cek("6.3 status diterima", statusPengadaan($db, $usul["id_pengadaan"]) === "diterima");
cek("6.3 2 eksemplar masuk katalog (celah 3)", ambil($db, "SELECT COUNT(*) AS n FROM eksemplar WHERE id_buku = 'BK00001'")["n"] == $eksemplarAwal + 2);

mysqli_rollback($db);
echo "\n" . ($gagal === 0 ? "Semua uji lulus" : "$gagal uji gagal") . " (data di-rollback)\n";

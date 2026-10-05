# Cara Menjalankan SI Perpustakaan

Aplikasi PHP native + MySQL, dijalankan dengan XAMPP (Apache + MySQL).

## 1. Nyalakan XAMPP

1. Buka `/Applications/XAMPP/manager-osx.app`.
2. Tab **Manage Servers**: pastikan **Apache Web Server** dan **MySQL Database** berstatus Running. Kalau belum, pilih lalu klik **Start**.

## 2. Pasang folder aplikasi ke htdocs (sekali saja)

Sudah dilakukan pada 2026-10-01. Ulangi hanya kalau XAMPP diinstal ulang.

Buka Terminal, lalu jalankan:

```
sudo ln -s "/Users/konou/VSCode/LockedIn/Kulyeah/Rekayasa Perangkat Lunak/SI Perpustakaan/aplikasi" /Applications/XAMPP/xamppfiles/htdocs/perpustakaan
chmod +a "daemon allow search" /Users/konou
```

Baris pertama membuat `htdocs/perpustakaan` menunjuk ke folder `aplikasi/`, jadi perubahan kode langsung terbaca tanpa menyalin. Baris kedua mengizinkan Apache (user `daemon`) melewati folder home. Izin ini hanya untuk lewat, isi folder home tetap tidak bisa dilihat. Untuk membatalkannya: `chmod -a "daemon allow search" /Users/konou`.

## 3. Buat database (sekali, atau untuk reset data)

1. Buka http://localhost/phpmyadmin
2. Klik tab **Import**, pilih file `aplikasi/database.sql`, lalu klik **Import** (atau **Go**).
3. Database `perpustakaan` akan muncul dengan 9 tabel.

File ini menghapus lalu membuat ulang database perpustakaan, jadi mengimpornya lagi akan mengembalikan semua data ke kondisi awal (3 akun staf, 3 buku, 4 eksemplar, belum ada anggota).

## 4. Buka aplikasi

http://localhost/perpustakaan

| Username | Password | Peran | Menu |
|---|---|---|---|
| petugas | petugas123 | Petugas | Anggota, Peminjaman, Pengembalian & Denda, Reservasi, Pengadaan |
| admin | admin123 | Admin | Data Buku, Akun Pengguna, Pengadaan |
| kepala | kepala123 | Kepala Perpustakaan | Laporan, Pengadaan |
| (daftar sendiri) | (pilihan sendiri) | Anggota | Katalog, Pinjaman Saya |

Akun anggota dibuat lewat tautan Daftar di halaman login. Lupa password: admin mengganti password di halaman Akun Pengguna.

## 5. Uji otomatis

Buka http://localhost/perpustakaan/tes.php

Halaman ini menjalankan semua fungsi proses (PSPEC 1.1 sampai 6.3) dan menampilkan `OK` atau `GAGAL` per uji. Baris terakhir harus `Semua uji lulus`. Semua uji berjalan di dalam transaksi lalu dibatalkan (rollback), jadi data tidak berubah.

## 6. Skenario uji manual

Jalankan berurutan dari data awal (impor ulang `database.sql` dulu). Hasil yang diharapkan ada di kolom kanan.

| No | Login | Langkah | Hasil yang diharapkan |
|---|---|---|---|
| 1 | petugas | Anggota: registrasi dengan no. telepon `08ab` | Pesan "No. telepon harus 10 sampai 15 digit angka" |
| 2 | petugas | Anggota: registrasi nama, alamat, telepon `081234567890` | Kartu Anggota `AGT00001` muncul |
| 3 | petugas | Anggota: cek `AGT00001` | Status aktif, denda Rp0, "Boleh meminjam" |
| 4 | petugas | Peminjaman: `AGT00001` + `BK00003` | Bukti Peminjaman `PJM000001`, jatuh tempo 7 hari lagi |
| 5 | petugas | Peminjaman: `AGT00001` + `BK00003` lagi | Eksemplar tersedia 0, saran reservasi |
| 6 | petugas | Reservasi: `AGT00001` + `BK00001` | Ditolak, buku masih tersedia |
| 7 | petugas | Reservasi: `AGT00001` + `BK00003` | `RSV000001` status menunggu |
| 8 | petugas | Pengembalian: `PJM000001` | "Buku dikembalikan tepat waktu, tidak ada denda"; reservasi `RSV000001` jadi siap diambil |
| 9 | petugas | Pengadaan: isi jumlah BK00001 = 2, Kirim Usulan | Usulan `PGD00001` status diusulkan |
| 10 | kepala | Pengadaan: Setujui dan pesan | Pesanan Pengadaan Buku tampil, status dipesan |
| 11 | petugas | Pengadaan: isi no. faktur, Terima Buku | Status diterima; di Peminjaman, BK00001 tersedia 4 dari 4 |
| 12 | admin | Data Buku: tambah buku, 1 eksemplar | `BK00004` muncul dengan 1 eksemplar |
| 13 | admin | Data Buku: hapus `BK00003` | Ditolak, buku sudah punya riwayat |
| 14 | admin | Akun Pengguna: hapus `admin` | Ditolak, akun sedang dipakai |
| 15 | kepala | Laporan: periode bulan ini | Total peminjaman 1, daftar berisi `PJM000001` |
| 16 | (belum login) | Klik Daftar, isi semua data, ulangi password berbeda | Pesan "Password dan ulangi password tidak sama", isian lain tetap terisi |
| 17 | (belum login) | Daftar dengan username `admin` | Pesan "Username sudah dipakai" |
| 18 | (belum login) | Daftar dengan username baru `siti.a` | Masuk ke Katalog, pesan "Pendaftaran berhasil. ID anggota: AGT00002" |
| 19 | siti.a | Katalog: cari `PHP`, lalu Reservasi | Hanya buku yang tersedia 0 punya tombol Reservasi; reservasi tercatat status menunggu. Klik lagi: ditolak, reservasi aktif sudah ada |
| 20 | siti.a | Pinjaman Saya | Tiga tabel tampil, reservasi tadi ada, tidak ada data AGT00001 |
| 21 | siti.a | Buka `localhost/perpustakaan/buku.php` lewat alamat | Kembali ke Katalog |
| 22 | admin | Akun Pengguna: ubah `siti.a` jadi petugas; ubah peran `admin` sendiri | `siti.a` berubah (ID anggota tetap tampil); peran sendiri ditolak. Di jendela `siti.a`, muat ulang: pindah ke halaman petugas |

Untuk mencoba denda, buku harus dikembalikan setelah jatuh tempo. Cara cepatnya: di phpMyAdmin, buka tabel `peminjaman`, ubah `tanggal_jatuh_tempo` suatu peminjaman menjadi 3 hari lalu, kembalikan bukunya, lalu denda Rp3.000 muncul. Anggota itu tidak boleh meminjam sampai dendanya dibayar di halaman Pengembalian & Denda (jumlah bayar harus sama dengan nominal).

## 7. Kalau ada masalah

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman tidak bisa dibuka / "connection refused" | Apache belum jalan, lihat langkah 1 |
| 403 Forbidden | Izin langkah 2 belum ada, jalankan lagi baris `chmod +a` |
| 404 Not Found | Symlink `htdocs/perpustakaan` belum ada, lihat langkah 2 |
| Fatal error "Unknown database 'perpustakaan'" | Database belum diimpor, lihat langkah 3 |
| Fatal error "Connection refused" dari mysqli | MySQL belum jalan, lihat langkah 1 |
| Login selalu salah | Data akun berubah; impor ulang `database.sql` |
| Setelah sign up muncul Fatal error | Isian melebihi batas panjang DD (biasanya bukan dari browser); data tidak tersimpan, ulangi dengan isian lebih pendek |

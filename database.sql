-- Database Sistem Informasi Perpustakaan
-- Tabel dari Kamus Data bagian 5 (satu data store DFD = satu atau dua tabel).
-- Jalankan lewat phpMyAdmin: tab Import, pilih file ini.

DROP DATABASE IF EXISTS perpustakaan;
CREATE DATABASE perpustakaan;
USE perpustakaan;

-- D1 Data Anggota
CREATE TABLE anggota (
  id_anggota CHAR(8) NOT NULL,
  nama_anggota VARCHAR(100) NOT NULL,
  alamat VARCHAR(200) NOT NULL,
  no_telepon VARCHAR(15) NOT NULL,
  email VARCHAR(130) NULL,
  tanggal_daftar DATE NOT NULL,
  status_anggota ENUM('aktif','nonaktif') NOT NULL,
  denda_tertunggak INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id_anggota)
);

-- D2 Data Buku
CREATE TABLE buku (
  id_buku CHAR(7) NOT NULL,
  judul VARCHAR(200) NOT NULL,
  pengarang VARCHAR(100) NOT NULL,
  penerbit VARCHAR(100) NOT NULL,
  tahun_terbit YEAR NOT NULL,
  PRIMARY KEY (id_buku)
);

CREATE TABLE eksemplar (
  id_eksemplar CHAR(10) NOT NULL,
  id_buku CHAR(7) NOT NULL,
  status_eksemplar ENUM('tersedia','dipinjam') NOT NULL,
  PRIMARY KEY (id_eksemplar),
  FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
);

-- D3 Data Peminjaman
CREATE TABLE peminjaman (
  id_peminjaman CHAR(9) NOT NULL,
  id_anggota CHAR(8) NOT NULL,
  id_eksemplar CHAR(10) NOT NULL,
  tanggal_pinjam DATE NOT NULL,
  tanggal_jatuh_tempo DATE NOT NULL,
  tanggal_kembali DATE NULL,
  status_peminjaman ENUM('dipinjam','dikembalikan') NOT NULL,
  PRIMARY KEY (id_peminjaman),
  FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota),
  FOREIGN KEY (id_eksemplar) REFERENCES eksemplar(id_eksemplar)
);

-- D4 Data Denda
CREATE TABLE denda (
  id_denda CHAR(9) NOT NULL,
  id_peminjaman CHAR(9) NOT NULL UNIQUE,
  jumlah_hari_terlambat INT NOT NULL,
  nominal_denda INT NOT NULL,
  status_bayar ENUM('belum lunas','lunas') NOT NULL,
  tanggal_bayar DATE NULL,
  PRIMARY KEY (id_denda),
  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman)
);

-- D5 Data Reservasi
CREATE TABLE reservasi (
  id_reservasi CHAR(9) NOT NULL,
  id_anggota CHAR(8) NOT NULL,
  id_buku CHAR(7) NOT NULL,
  tanggal_reservasi DATE NOT NULL,
  status_reservasi ENUM('menunggu','siap diambil','batal') NOT NULL,
  PRIMARY KEY (id_reservasi),
  FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota),
  FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
);

-- D6 Data Akun Pengguna
-- tambalan celah 6: peran 'kepala' ditambah supaya Kepala Perpustakaan bisa login
CREATE TABLE akun_pengguna (
  id_akun CHAR(8) NOT NULL,
  username VARCHAR(30) NOT NULL UNIQUE,
  password VARCHAR(60) NOT NULL,
  peran ENUM('admin','petugas','kepala','anggota') NOT NULL,
  id_anggota CHAR(8) NULL UNIQUE,
  PRIMARY KEY (id_akun),
  FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota)
);

-- D7 Data Pengadaan
-- tambalan celah 4: status 'diusulkan' dan 'ditolak', tanggal_pesan kosong sampai dipesan
CREATE TABLE pengadaan (
  id_pengadaan CHAR(8) NOT NULL,
  tanggal_pesan DATE NULL,
  status_pengadaan ENUM('diusulkan','ditolak','dipesan','diterima') NOT NULL,
  no_faktur VARCHAR(30) NULL,
  tanggal_terima DATE NULL,
  PRIMARY KEY (id_pengadaan)
);

CREATE TABLE detail_pengadaan (
  id_pengadaan CHAR(8) NOT NULL,
  id_buku CHAR(7) NOT NULL,
  jumlah_pesan INT NOT NULL,
  jumlah_diterima INT NULL,
  PRIMARY KEY (id_pengadaan, id_buku),
  FOREIGN KEY (id_pengadaan) REFERENCES pengadaan(id_pengadaan),
  FOREIGN KEY (id_buku) REFERENCES buku(id_buku)
);

-- Data awal. Password: admin123, petugas123, kepala123
INSERT INTO akun_pengguna VALUES
('AKN00001', 'admin', '$2y$10$GuPhG.EN.aqvD4Kq4DJDbeKihADhSysX0c7ou0/3F59dh7EkRYUT6', 'admin', NULL),
('AKN00002', 'petugas', '$2y$10$tRTF3Wpral4sHtVtGRdwH.OG/Fffp3DxIFIgdMKh0pDeAgU8qlke2', 'petugas', NULL),
('AKN00003', 'kepala', '$2y$10$vC23KwOYKJCyENnNIXCf9uAlI51Hjz/GLQfp72jNxglU1rU5FPwyW', 'kepala', NULL);

INSERT INTO buku VALUES
('BK00001', 'Rekayasa Perangkat Lunak', 'Roger S. Pressman', 'Andi', 2012),
('BK00002', 'Basis Data', 'Fathansyah', 'Informatika', 2018),
('BK00003', 'Pemrograman Web dengan PHP', 'Budi Raharjo', 'Informatika', 2020);

INSERT INTO eksemplar VALUES
('BK00001-01', 'BK00001', 'tersedia'),
('BK00001-02', 'BK00001', 'tersedia'),
('BK00002-01', 'BK00002', 'tersedia'),
('BK00003-01', 'BK00003', 'tersedia');

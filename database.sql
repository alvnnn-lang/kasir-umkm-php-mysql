-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Jul 2025 pada 09.07
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_kasir_toko`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `tipe` enum('makanan','minuman_racik','minuman_kemasan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `tipe`) VALUES
(1, 'Makanan Utama', 'makanan'),
(2, 'makanan ringan', 'makanan'),
(3, 'Minuman Racikan', 'minuman_racik'),
(4, 'Minuman Kemasan', 'minuman_kemasan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `jenis_pembayaran` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id_pembayaran`, `jenis_pembayaran`) VALUES
(1, 'Tunai'),
(2, 'Non Tunai');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan_harian`
--

CREATE TABLE `penjualan_harian` (
  `id` int(11) NOT NULL,
  `produk_id` int(11) DEFAULT NULL,
  `jumlah_terjual` int(11) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `tanggal_penjualan` date NOT NULL,
  `waktu_penjualan` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_pembayaran` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penjualan_harian`
--

INSERT INTO `penjualan_harian` (`id`, `produk_id`, `jumlah_terjual`, `total_harga`, `tanggal_penjualan`, `waktu_penjualan`, `id_pembayaran`) VALUES
(1, 9, 2, 20000.00, '2025-06-16', '2025-06-16 12:01:14', NULL),
(3, 3, 5, 10000.00, '2025-06-16', '2025-06-16 12:21:47', NULL),
(4, 1, 2, 20000.00, '2025-06-16', '2025-06-16 12:22:24', NULL),
(5, 3, 4, 8000.00, '2025-06-16', '2025-06-16 12:54:01', NULL),
(6, 6, 1, 4000.00, '2025-06-16', '2025-06-16 12:59:26', NULL),
(8, 3, 1, 2000.00, '2025-06-16', '2025-06-16 13:32:49', NULL),
(9, 3, 4, 8000.00, '2025-06-17', '2025-06-17 05:56:54', NULL),
(16, 9, 1, 10000.00, '2025-06-17', '2025-06-17 12:00:52', 2),
(20, 9, 1, 10000.00, '2025-06-17', '2025-06-17 12:57:58', 1),
(23, 1, 5, 50000.00, '2025-06-17', '2025-06-17 14:22:32', 1),
(25, 4, 3, 15000.00, '2025-06-17', '2025-06-17 14:23:25', 1),
(26, 3, 1, 2000.00, '2025-06-17', '2025-06-17 14:44:46', 1),
(27, 6, 4, 16000.00, '2025-06-18', '2025-06-18 02:29:22', 1),
(28, 6, 3, 12000.00, '2025-06-18', '2025-06-18 02:30:05', 1),
(29, 17, 4, 8000.00, '2025-06-18', '2025-06-18 02:38:16', 1),
(30, 9, 2, 20000.00, '2025-06-18', '2025-06-18 02:45:43', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `kategori_id`, `nama`, `harga`, `stok`, `dibuat_pada`, `diperbarui_pada`) VALUES
(1, 1, 'Sate Ayam', 10000.00, 10, '2025-06-14 14:54:59', '2025-06-17 14:22:32'),
(3, 2, 'Kerupuk', 2000.00, 6, '2025-06-14 14:54:59', '2025-06-18 02:21:52'),
(4, 3, 'Kopi Hitam', 5000.00, 1, '2025-06-14 14:54:59', '2025-06-17 14:23:25'),
(5, 3, 'Teh Manis', 3000.00, 3, '2025-06-14 14:54:59', '2025-06-17 14:23:11'),
(6, 4, 'Teh Kotak', 4000.00, 7, '2025-06-14 14:54:59', '2025-06-18 02:30:05'),
(8, 1, 'Sate Pical', 10000.00, 10, '2025-06-16 11:59:02', '2025-06-16 11:59:02'),
(9, 1, 'Lontong Paku', 10000.00, 9, '2025-06-16 12:00:34', '2025-06-18 02:45:43'),
(12, 1, 'Nasi Ampera', 10000.00, 10, '2025-06-16 13:47:57', '2025-06-17 13:19:43'),
(17, 2, 'Donat', 2000.00, 20, '2025-06-17 12:56:57', '2025-06-18 02:40:49'),
(18, 1, 'Nasi Dadar', 8000.00, 20, '2025-06-17 13:17:52', '2025-06-17 13:17:52'),
(20, 2, 'gorengan', 1000.00, 20, '2025-06-18 02:44:05', '2025-06-18 02:44:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(5, 'adminkadai', '$2y$10$.1FS525gBOKlWjMru2vs3Ohs0R2GME6iV/6M6q2.kFwLXGBbQs.v2', '2025-06-15 05:07:29');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`);

--
-- Indeks untuk tabel `penjualan_harian`
--
ALTER TABLE `penjualan_harian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `id_pembayaran` (`id_pembayaran`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `penjualan_harian`
--
ALTER TABLE `penjualan_harian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `penjualan_harian`
--
ALTER TABLE `penjualan_harian`
  ADD CONSTRAINT `penjualan_harian_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `penjualan_harian_ibfk_2` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`);

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

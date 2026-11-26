-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 25, 2026 at 03:41 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dn_unit`
--

-- --------------------------------------------------------

--
-- Table structure for table `unit_usaha`
--

CREATE TABLE `unit_usaha` (
  `id_unit` int NOT NULL,
  `nama_unit` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','unit') NOT NULL DEFAULT 'unit',
  `saldo_awal` decimal(15,2) DEFAULT '0.00',
  `status` enum('aktif','non-aktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `unit_usaha`
--

INSERT INTO `unit_usaha` (`id_unit`, `nama_unit`, `username`, `password`, `role`, `saldo_awal`, `status`, `created_at`) VALUES
(1, 'Unit Air Minum DN Water', 'dn_water_admin', '$2y$10$mKEeIFIiC9MYcEnAreia4ulYBXC7Q37M1c6M3AXNrcmeUc9rX/eae', 'unit', 0.00, 'aktif', '2026-04-15 09:44:42'),
(2, 'Administrator Pusat', 'admin_pusat', '$2y$10$QQvvbjyohYyOgve5HiRfFuvPqYyhCw1AmWvnJANExlcNzuELwP7KK', 'admin', 0.00, 'aktif', '2026-04-15 10:13:31'),
(3, 'Kopontren Putra Darussalam', 'kopontren_putra', '$2y$10$bmz/qXfF/PyOxRIbLOUjT.hoYDEsIS0rRz1GvToMOO3u563gQNVj.', 'unit', 0.00, 'aktif', '2026-04-22 08:31:00'),
(4, 'Kopontren Putri Darussalam', 'kopontren_putri', '$2y$10$8RUMsEWT6JZltaT2M71E6u4/q/x5Kdi9g9c0c6hu0UAzyi/m2PNii', 'unit', 0.00, 'aktif', '2026-04-22 08:31:00'),
(5, 'DN Cafetaria', 'dn_cafetaria', '$2y$10$SI/XSOJi66sCbE6UpAraa.aJj5o7lUimARn9HOz/Z.dJjxApV.kxi', 'unit', 0.00, 'aktif', '2026-04-22 08:31:00'),
(6, 'DN Mart', 'dn_mart', '$2y$10$y9jhsjU8Rq4YGPiGdfahT.3nXhOaYdqDWuPz/KGXnFoDN7J.IdgT6', 'unit', 0.00, 'aktif', '2026-04-22 08:31:00'),
(7, 'DN Kiftir', 'dn_kiftir', '$2y$10$cTsx5aubqmjpJEcA1DDDx.MJI.fJ.LmRO.b9p1hLsgGCX1HSe4Oz6', 'unit', 0.00, 'aktif', '2026-04-22 08:31:01'),
(8, 'DN Bakery', 'dn_bakery', '$2y$10$guzj/jLAD6YD0jjPsp6RR.usV/YflxVsdqVIDydYP4Mqp7TWiDvz.', 'unit', 0.00, 'aktif', '2026-04-25 14:40:56'),
(9, 'DN Transport', 'dn_transport', '$2y$10$.oKyyf0UX/H/kro7dpw5R.XiTnTPegKO8PgipMAOBshFTeI8ZrQcW', 'unit', 0.00, 'aktif', '2026-04-25 14:40:56'),
(10, 'DN Catering', 'dn_catering', '$2y$10$GQbhcAbJidvGt6a6gvxRnuN/CFCSt45Nn9IMsgEle12dWiixXU.Gm', 'unit', 0.00, 'aktif', '2026-04-25 14:40:56'),
(11, 'DN Cafe Santri', 'dn_cafesantri', '$2y$10$emjO68hQDUFExPwXVuRHnu10i19J1OCWCg3vuJ63lClbg6eBJEM4K', 'unit', 0.00, 'aktif', '2026-04-25 14:40:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `unit_usaha`
--
ALTER TABLE `unit_usaha`
  ADD PRIMARY KEY (`id_unit`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `unit_usaha`
--
ALTER TABLE `unit_usaha`
  MODIFY `id_unit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

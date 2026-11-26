-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: dn_unit
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `id_unit` int(11) NOT NULL,
  `jenis_transaksi` enum('pemasukan','pengeluaran') NOT NULL,
  `nominal` int(11) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `tanggal_transaksi` date NOT NULL,
  PRIMARY KEY (`id_transaksi`),
  KEY `id_unit` (`id_unit`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_unit`) REFERENCES `unit_usaha` (`id_unit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_usaha`
--

DROP TABLE IF EXISTS `unit_usaha`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unit_usaha` (
  `id_unit` int(11) NOT NULL AUTO_INCREMENT,
  `nama_unit` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','unit') NOT NULL DEFAULT 'unit',
  `saldo_awal` decimal(15,2) DEFAULT 0.00,
  `status` enum('aktif','non-aktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_unit`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_usaha`
--

LOCK TABLES `unit_usaha` WRITE;
/*!40000 ALTER TABLE `unit_usaha` DISABLE KEYS */;
INSERT INTO `unit_usaha` VALUES (1,'Unit Air Minum DN Water','dn_water_admin','$2y$10$mKEeIFIiC9MYcEnAreia4ulYBXC7Q37M1c6M3AXNrcmeUc9rX/eae','unit',0.00,'aktif','2026-04-15 09:44:42'),(2,'Administrator Pusat','admin_pusat','$2y$10$QQvvbjyohYyOgve5HiRfFuvPqYyhCw1AmWvnJANExlcNzuELwP7KK','admin',0.00,'aktif','2026-04-15 10:13:31'),(3,'Kopontren Putra Darussalam','kopontren_putra','$2y$10$bmz/qXfF/PyOxRIbLOUjT.hoYDEsIS0rRz1GvToMOO3u563gQNVj.','unit',0.00,'aktif','2026-04-22 08:31:00'),(4,'Kopontren Putri Darussalam','kopontren_putri','$2y$10$8RUMsEWT6JZltaT2M71E6u4/q/x5Kdi9g9c0c6hu0UAzyi/m2PNii','unit',0.00,'aktif','2026-04-22 08:31:00'),(5,'DN Cafetaria','dn_cafetaria','$2y$10$SI/XSOJi66sCbE6UpAraa.aJj5o7lUimARn9HOz/Z.dJjxApV.kxi','unit',0.00,'aktif','2026-04-22 08:31:00'),(6,'DN Mart','dn_mart','$2y$10$y9jhsjU8Rq4YGPiGdfahT.3nXhOaYdqDWuPz/KGXnFoDN7J.IdgT6','unit',0.00,'aktif','2026-04-22 08:31:00'),(7,'DN Kiftir','dn_kiftir','$2y$10$cTsx5aubqmjpJEcA1DDDx.MJI.fJ.LmRO.b9p1hLsgGCX1HSe4Oz6','unit',0.00,'aktif','2026-04-22 08:31:01'),(8,'DN Bakery','dn_bakery','$2y$10$guzj/jLAD6YD0jjPsp6RR.usV/YflxVsdqVIDydYP4Mqp7TWiDvz.','unit',0.00,'aktif','2026-04-25 14:40:56'),(9,'DN Transport','dn_transport','$2y$10$.oKyyf0UX/H/kro7dpw5R.XiTnTPegKO8PgipMAOBshFTeI8ZrQcW','unit',0.00,'aktif','2026-04-25 14:40:56'),(10,'DN Catering','dn_catering','$2y$10$GQbhcAbJidvGt6a6gvxRnuN/CFCSt45Nn9IMsgEle12dWiixXU.Gm','unit',0.00,'aktif','2026-04-25 14:40:56'),(11,'DN Cafe Santri','dn_cafesantri','$2y$10$emjO68hQDUFExPwXVuRHnu10i19J1OCWCg3vuJ63lClbg6eBJEM4K','unit',0.00,'aktif','2026-04-25 14:40:56');
/*!40000 ALTER TABLE `unit_usaha` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-23 14:25:36

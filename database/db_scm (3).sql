-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 03, 2025 at 03:45 AM
-- Server version: 8.0.30
-- PHP Version: 7.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_scm`
--

-- --------------------------------------------------------

--
-- Table structure for table `alamat_pengiriman`
--

CREATE TABLE `alamat_pengiriman` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `label` varchar(50) COLLATE utf8mb3_swedish_ci NOT NULL,
  `nama_penerima` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `no_telepon` varchar(15) COLLATE utf8mb3_swedish_ci NOT NULL,
  `province_id` int NOT NULL,
  `city_id` int NOT NULL,
  `alamat_lengkap` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `kode_pos` varchar(10) COLLATE utf8mb3_swedish_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `alamat_pengiriman`
--

INSERT INTO `alamat_pengiriman` (`id`, `user_id`, `label`, `nama_penerima`, `no_telepon`, `province_id`, `city_id`, `alamat_lengkap`, `kode_pos`, `is_default`, `created_at`) VALUES
(1, 5, 'Rumah', 'Customer Test', '081234567894', 11, 1, 'Jl. Teuku Umar No. 123, Banda Aceh', '23111', 1, '2025-07-02 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `batch_produksi`
--

CREATE TABLE `batch_produksi` (
  `id` int NOT NULL,
  `kode_batch` varchar(20) COLLATE utf8mb3_swedish_ci NOT NULL,
  `petani_id` int NOT NULL,
  `jenis_kopi` enum('arabika','robusta') COLLATE utf8mb3_swedish_ci NOT NULL,
  `jumlah_kg` decimal(10,2) NOT NULL,
  `tanggal_panen` date NOT NULL,
  `harga_per_kg` decimal(10,2) NOT NULL,
  `status` enum('panen','proses','selesai','terjual') COLLATE utf8mb3_swedish_ci DEFAULT 'panen',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `batch_produksi`
--

INSERT INTO `batch_produksi` (`id`, `kode_batch`, `petani_id`, `jenis_kopi`, `jumlah_kg`, `tanggal_panen`, `harga_per_kg`, `status`, `created_at`) VALUES
(1, 'BP20241205001', 1, 'arabika', '500.00', '2024-12-01', '25000.00', 'selesai', '2025-07-02 08:10:40'),
(2, 'BP20241205002', 2, 'arabika', '300.00', '2024-12-02', '27000.00', 'selesai', '2025-07-02 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `id` int NOT NULL,
  `produk_id` int NOT NULL,
  `pembeli_id` int NOT NULL,
  `penjual_id` int NOT NULL,
  `pesan` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `pengirim` enum('pembeli','penjual') COLLATE utf8mb3_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `nama_produk` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `informasi`
--

CREATE TABLE `informasi` (
  `id` int NOT NULL,
  `judul` varchar(200) COLLATE utf8mb3_swedish_ci NOT NULL,
  `konten` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `gambar` varchar(255) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `informasi`
--

INSERT INTO `informasi` (`id`, `judul`, `konten`, `gambar`, `created_by`, `created_at`) VALUES
(1, 'Tips Budidaya Kopi Arabika', 'Kopi arabika tumbuh optimal di ketinggian 1000-2000 mdpl di daerah Bener Meriah...', NULL, 1, '2025-07-02 08:10:40'),
(2, 'Cara Menggunakan Raja Ongkir', 'Panduan checkout dengan kalkulasi ongkir otomatis menggunakan JNE, POS, TIKI...', NULL, 1, '2025-07-02 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id` int NOT NULL,
  `nama_kategori` varchar(50) COLLATE utf8mb3_swedish_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb3_swedish_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`id`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Green Bean', 'Biji kopi mentah yang belum disangrai'),
(2, 'Bubuk Kopi', 'Kopi yang sudah disangrai dan digiling');

-- --------------------------------------------------------

--
-- Table structure for table `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kota`
--

CREATE TABLE `kota` (
  `city_id` int NOT NULL,
  `province_id` int NOT NULL,
  `type` varchar(20) COLLATE utf8mb3_swedish_ci NOT NULL,
  `city_name` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `postal_code` varchar(10) COLLATE utf8mb3_swedish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `kota`
--

INSERT INTO `kota` (`city_id`, `province_id`, `type`, `city_name`, `postal_code`) VALUES
(1, 11, 'Kabupaten', 'Aceh Barat', '23681'),
(2, 21, 'Kabupaten', 'Aceh Barat Daya', '23764'),
(3, 21, 'Kabupaten', 'Aceh Besar', '23951'),
(4, 21, 'Kabupaten', 'Aceh Jaya', '23654'),
(5, 21, 'Kabupaten', 'Aceh Selatan', '23719'),
(6, 21, 'Kabupaten', 'Aceh Singkil', '24785'),
(7, 21, 'Kabupaten', 'Aceh Tamiang', '24476'),
(8, 21, 'Kabupaten', 'Aceh Tengah', '24511'),
(9, 21, 'Kabupaten', 'Aceh Tenggara', '24611'),
(10, 21, 'Kabupaten', 'Aceh Timur', '24454'),
(11, 21, 'Kabupaten', 'Aceh Utara', '24382'),
(12, 32, 'Kabupaten', 'Agam', '26411'),
(13, 23, 'Kabupaten', 'Alor', '85811'),
(14, 19, 'Kota', 'Ambon', '97222'),
(15, 11, 'Kabupaten', 'Asahan', '21214'),
(16, 11, 'Kabupaten', 'Asmat', '99777'),
(17, 1, 'Kabupaten', 'Badung', '80351'),
(18, 13, 'Kabupaten', 'Balangan', '71611'),
(19, 15, 'Kota', 'Balikpapan', '76111'),
(20, 21, 'Kota', 'Banda Aceh', '23238'),
(21, 18, 'Kota', 'Bandar Lampung', '35139'),
(22, 9, 'Kabupaten', 'Bandung', '40311'),
(23, 9, 'Kota', 'Bandung', '40111'),
(24, 9, 'Kabupaten', 'Bandung Barat', '40721'),
(25, 29, 'Kabupaten', 'Banggai', '94711'),
(26, 29, 'Kabupaten', 'Banggai Kepulauan', '94881'),
(27, 2, 'Kabupaten', 'Bangka', '33212'),
(28, 2, 'Kabupaten', 'Bangka Barat', '33315'),
(29, 2, 'Kabupaten', 'Bangka Selatan', '33719'),
(30, 2, 'Kabupaten', 'Bangka Tengah', '33613'),
(31, 11, 'Kabupaten', 'Bangkalan', '69118'),
(32, 1, 'Kabupaten', 'Bangli', '80619'),
(33, 13, 'Kabupaten', 'Banjar', '70619'),
(34, 9, 'Kota', 'Banjar', '46311'),
(35, 13, 'Kota', 'Banjarbaru', '70712'),
(36, 13, 'Kota', 'Banjarmasin', '70117'),
(37, 10, 'Kabupaten', 'Banjarnegara', '53419'),
(38, 28, 'Kabupaten', 'Bantaeng', '92411'),
(39, 5, 'Kabupaten', 'Bantul', '55715'),
(40, 33, 'Kabupaten', 'Banyuasin', '30911'),
(41, 10, 'Kabupaten', 'Banyumas', '53114'),
(42, 11, 'Kabupaten', 'Banyuwangi', '68416'),
(43, 13, 'Kabupaten', 'Barito Kuala', '70511'),
(44, 14, 'Kabupaten', 'Barito Selatan', '73711'),
(45, 14, 'Kabupaten', 'Barito Timur', '73671'),
(46, 14, 'Kabupaten', 'Barito Utara', '73881'),
(47, 28, 'Kabupaten', 'Barru', '90719'),
(48, 17, 'Kota', 'Batam', '29413'),
(49, 10, 'Kabupaten', 'Batang', '51211'),
(50, 8, 'Kabupaten', 'Batang Hari', '36613'),
(51, 11, 'Kota', 'Batu', '65311'),
(52, 34, 'Kabupaten', 'Batu Bara', '21655'),
(53, 30, 'Kota', 'Bau-Bau', '93719'),
(54, 9, 'Kabupaten', 'Bekasi', '17837'),
(55, 9, 'Kota', 'Bekasi', '17121'),
(56, 2, 'Kabupaten', 'Belitung', '33419'),
(57, 2, 'Kabupaten', 'Belitung Timur', '33519'),
(58, 23, 'Kabupaten', 'Belu', '85711'),
(59, 21, 'Kabupaten', 'Bener Meriah', '24581'),
(60, 26, 'Kabupaten', 'Bengkalis', '28719'),
(61, 12, 'Kabupaten', 'Bengkayang', '79213'),
(62, 4, 'Kota', 'Bengkulu', '38229'),
(63, 4, 'Kabupaten', 'Bengkulu Selatan', '38519'),
(64, 4, 'Kabupaten', 'Bengkulu Tengah', '38319'),
(65, 4, 'Kabupaten', 'Bengkulu Utara', '38619'),
(66, 15, 'Kabupaten', 'Berau', '77311'),
(67, 24, 'Kabupaten', 'Biak Numfor', '98119'),
(68, 22, 'Kabupaten', 'Bima', '84171'),
(69, 22, 'Kota', 'Bima', '84139'),
(70, 34, 'Kota', 'Binjai', '20712'),
(71, 17, 'Kabupaten', 'Bintan', '29135'),
(72, 21, 'Kabupaten', 'Bireuen', '24219'),
(73, 31, 'Kota', 'Bitung', '95512'),
(74, 11, 'Kabupaten', 'Blitar', '66171'),
(75, 11, 'Kota', 'Blitar', '66124'),
(76, 10, 'Kabupaten', 'Blora', '58219'),
(77, 7, 'Kabupaten', 'Boalemo', '96319'),
(78, 9, 'Kabupaten', 'Bogor', '16911'),
(79, 9, 'Kota', 'Bogor', '16119'),
(80, 11, 'Kabupaten', 'Bojonegoro', '62119'),
(81, 31, 'Kabupaten', 'Bolaang Mongondow (Bolmong)', '95755'),
(82, 31, 'Kabupaten', 'Bolaang Mongondow Selatan', '95774'),
(83, 31, 'Kabupaten', 'Bolaang Mongondow Timur', '95783'),
(84, 31, 'Kabupaten', 'Bolaang Mongondow Utara', '95765'),
(85, 30, 'Kabupaten', 'Bombana', '93771'),
(86, 11, 'Kabupaten', 'Bondowoso', '68219'),
(87, 28, 'Kabupaten', 'Bone', '92713'),
(88, 7, 'Kabupaten', 'Bone Bolango', '96511'),
(89, 15, 'Kota', 'Bontang', '75313'),
(90, 24, 'Kabupaten', 'Boven Digoel', '99662'),
(91, 10, 'Kabupaten', 'Boyolali', '57312'),
(92, 10, 'Kabupaten', 'Brebes', '52212'),
(93, 32, 'Kota', 'Bukittinggi', '26115'),
(94, 1, 'Kabupaten', 'Buleleng', '81111'),
(95, 28, 'Kabupaten', 'Bulukumba', '92511'),
(96, 16, 'Kabupaten', 'Bulungan (Bulongan)', '77211'),
(97, 8, 'Kabupaten', 'Bungo', '37216'),
(98, 29, 'Kabupaten', 'Buol', '94564'),
(99, 19, 'Kabupaten', 'Buru', '97371'),
(100, 19, 'Kabupaten', 'Buru Selatan', '97351'),
(101, 30, 'Kabupaten', 'Buton', '93754'),
(102, 30, 'Kabupaten', 'Buton Utara', '93745'),
(103, 9, 'Kabupaten', 'Ciamis', '46211'),
(104, 9, 'Kabupaten', 'Cianjur', '43217'),
(105, 10, 'Kabupaten', 'Cilacap', '53211'),
(106, 3, 'Kota', 'Cilegon', '42417'),
(107, 9, 'Kota', 'Cimahi', '40512'),
(108, 9, 'Kabupaten', 'Cirebon', '45611'),
(109, 9, 'Kota', 'Cirebon', '45116'),
(110, 34, 'Kabupaten', 'Dairi', '22211'),
(111, 24, 'Kabupaten', 'Deiyai (Deliyai)', '98784'),
(112, 34, 'Kabupaten', 'Deli Serdang', '20511'),
(113, 10, 'Kabupaten', 'Demak', '59519'),
(114, 1, 'Kota', 'Denpasar', '80227'),
(115, 9, 'Kota', 'Depok', '16416'),
(116, 32, 'Kabupaten', 'Dharmasraya', '27612'),
(117, 24, 'Kabupaten', 'Dogiyai', '98866'),
(118, 22, 'Kabupaten', 'Dompu', '84217'),
(119, 29, 'Kabupaten', 'Donggala', '94341'),
(120, 26, 'Kota', 'Dumai', '28811'),
(121, 33, 'Kabupaten', 'Empat Lawang', '31811'),
(122, 23, 'Kabupaten', 'Ende', '86351'),
(123, 28, 'Kabupaten', 'Enrekang', '91719'),
(124, 25, 'Kabupaten', 'Fakfak', '98651'),
(125, 23, 'Kabupaten', 'Flores Timur', '86213'),
(126, 9, 'Kabupaten', 'Garut', '44126'),
(127, 21, 'Kabupaten', 'Gayo Lues', '24653'),
(128, 1, 'Kabupaten', 'Gianyar', '80519'),
(129, 7, 'Kabupaten', 'Gorontalo', '96218'),
(130, 7, 'Kota', 'Gorontalo', '96115'),
(131, 7, 'Kabupaten', 'Gorontalo Utara', '96611'),
(132, 28, 'Kabupaten', 'Gowa', '92111'),
(133, 11, 'Kabupaten', 'Gresik', '61115'),
(134, 10, 'Kabupaten', 'Grobogan', '58111'),
(135, 5, 'Kabupaten', 'Gunung Kidul', '55812'),
(136, 14, 'Kabupaten', 'Gunung Mas', '74511'),
(137, 34, 'Kota', 'Gunungsitoli', '22813'),
(138, 20, 'Kabupaten', 'Halmahera Barat', '97757'),
(139, 20, 'Kabupaten', 'Halmahera Selatan', '97911'),
(140, 20, 'Kabupaten', 'Halmahera Tengah', '97853'),
(141, 20, 'Kabupaten', 'Halmahera Timur', '97862'),
(142, 20, 'Kabupaten', 'Halmahera Utara', '97762'),
(143, 13, 'Kabupaten', 'Hulu Sungai Selatan', '71212'),
(144, 13, 'Kabupaten', 'Hulu Sungai Tengah', '71313'),
(145, 13, 'Kabupaten', 'Hulu Sungai Utara', '71419'),
(146, 34, 'Kabupaten', 'Humbang Hasundutan', '22457'),
(147, 26, 'Kabupaten', 'Indragiri Hilir', '29212'),
(148, 26, 'Kabupaten', 'Indragiri Hulu', '29319'),
(149, 9, 'Kabupaten', 'Indramayu', '45214'),
(150, 24, 'Kabupaten', 'Intan Jaya', '98771'),
(151, 6, 'Kota', 'Jakarta Barat', '11220'),
(152, 6, 'Kota', 'Jakarta Pusat', '10540'),
(153, 6, 'Kota', 'Jakarta Selatan', '12230'),
(154, 6, 'Kota', 'Jakarta Timur', '13330'),
(155, 6, 'Kota', 'Jakarta Utara', '14140'),
(156, 8, 'Kota', 'Jambi', '36111'),
(157, 24, 'Kabupaten', 'Jayapura', '99352'),
(158, 24, 'Kota', 'Jayapura', '99114'),
(159, 24, 'Kabupaten', 'Jayawijaya', '99511'),
(160, 11, 'Kabupaten', 'Jember', '68113'),
(161, 1, 'Kabupaten', 'Jembrana', '82251'),
(162, 28, 'Kabupaten', 'Jeneponto', '92319'),
(163, 10, 'Kabupaten', 'Jepara', '59419'),
(164, 11, 'Kabupaten', 'Jombang', '61415'),
(165, 25, 'Kabupaten', 'Kaimana', '98671'),
(166, 26, 'Kabupaten', 'Kampar', '28411'),
(167, 14, 'Kabupaten', 'Kapuas', '73583'),
(168, 12, 'Kabupaten', 'Kapuas Hulu', '78719'),
(169, 10, 'Kabupaten', 'Karanganyar', '57718'),
(170, 1, 'Kabupaten', 'Karangasem', '80819'),
(171, 9, 'Kabupaten', 'Karawang', '41311'),
(172, 17, 'Kabupaten', 'Karimun', '29611'),
(173, 34, 'Kabupaten', 'Karo', '22119'),
(174, 14, 'Kabupaten', 'Katingan', '74411'),
(175, 4, 'Kabupaten', 'Kaur', '38911'),
(176, 12, 'Kabupaten', 'Kayong Utara', '78852'),
(177, 10, 'Kabupaten', 'Kebumen', '54319'),
(178, 11, 'Kabupaten', 'Kediri', '64184'),
(179, 11, 'Kota', 'Kediri', '64125'),
(180, 24, 'Kabupaten', 'Keerom', '99461'),
(181, 10, 'Kabupaten', 'Kendal', '51314'),
(182, 30, 'Kota', 'Kendari', '93126'),
(183, 4, 'Kabupaten', 'Kepahiang', '39319'),
(184, 17, 'Kabupaten', 'Kepulauan Anambas', '29991'),
(185, 19, 'Kabupaten', 'Kepulauan Aru', '97681'),
(186, 32, 'Kabupaten', 'Kepulauan Mentawai', '25771'),
(187, 26, 'Kabupaten', 'Kepulauan Meranti', '28791'),
(188, 31, 'Kabupaten', 'Kepulauan Sangihe', '95819'),
(189, 6, 'Kabupaten', 'Kepulauan Seribu', '14550'),
(190, 31, 'Kabupaten', 'Kepulauan Siau Tagulandang Biaro (Sitaro)', '95862'),
(191, 20, 'Kabupaten', 'Kepulauan Sula', '97995'),
(192, 31, 'Kabupaten', 'Kepulauan Talaud', '95885'),
(193, 24, 'Kabupaten', 'Kepulauan Yapen (Yapen Waropen)', '98211'),
(194, 8, 'Kabupaten', 'Kerinci', '37167'),
(195, 12, 'Kabupaten', 'Ketapang', '78874'),
(196, 10, 'Kabupaten', 'Klaten', '57411'),
(197, 1, 'Kabupaten', 'Klungkung', '80719'),
(198, 30, 'Kabupaten', 'Kolaka', '93511'),
(199, 30, 'Kabupaten', 'Kolaka Utara', '93911'),
(200, 30, 'Kabupaten', 'Konawe', '93411'),
(201, 30, 'Kabupaten', 'Konawe Selatan', '93811'),
(202, 30, 'Kabupaten', 'Konawe Utara', '93311'),
(203, 13, 'Kabupaten', 'Kotabaru', '72119'),
(204, 31, 'Kota', 'Kotamobagu', '95711'),
(205, 14, 'Kabupaten', 'Kotawaringin Barat', '74119'),
(206, 14, 'Kabupaten', 'Kotawaringin Timur', '74364'),
(207, 26, 'Kabupaten', 'Kuantan Singingi', '29519'),
(208, 12, 'Kabupaten', 'Kubu Raya', '78311'),
(209, 10, 'Kabupaten', 'Kudus', '59311'),
(210, 5, 'Kabupaten', 'Kulon Progo', '55611'),
(211, 9, 'Kabupaten', 'Kuningan', '45511'),
(212, 23, 'Kabupaten', 'Kupang', '85362'),
(213, 23, 'Kota', 'Kupang', '85119'),
(214, 15, 'Kabupaten', 'Kutai Barat', '75711'),
(215, 15, 'Kabupaten', 'Kutai Kartanegara', '75511'),
(216, 15, 'Kabupaten', 'Kutai Timur', '75611'),
(217, 34, 'Kabupaten', 'Labuhan Batu', '21412'),
(218, 34, 'Kabupaten', 'Labuhan Batu Selatan', '21511'),
(219, 34, 'Kabupaten', 'Labuhan Batu Utara', '21711'),
(220, 33, 'Kabupaten', 'Lahat', '31419'),
(221, 14, 'Kabupaten', 'Lamandau', '74611'),
(222, 11, 'Kabupaten', 'Lamongan', '64125'),
(223, 18, 'Kabupaten', 'Lampung Barat', '34814'),
(224, 18, 'Kabupaten', 'Lampung Selatan', '35511'),
(225, 18, 'Kabupaten', 'Lampung Tengah', '34212'),
(226, 18, 'Kabupaten', 'Lampung Timur', '34319'),
(227, 18, 'Kabupaten', 'Lampung Utara', '34516'),
(228, 12, 'Kabupaten', 'Landak', '78319'),
(229, 34, 'Kabupaten', 'Langkat', '20811'),
(230, 21, 'Kota', 'Langsa', '24412'),
(231, 24, 'Kabupaten', 'Lanny Jaya', '99531'),
(232, 3, 'Kabupaten', 'Lebak', '42319'),
(233, 4, 'Kabupaten', 'Lebong', '39264'),
(234, 23, 'Kabupaten', 'Lembata', '86611'),
(235, 21, 'Kota', 'Lhokseumawe', '24352'),
(236, 32, 'Kabupaten', 'Lima Puluh Koto/Kota', '26671'),
(237, 17, 'Kabupaten', 'Lingga', '29811'),
(238, 22, 'Kabupaten', 'Lombok Barat', '83311'),
(239, 22, 'Kabupaten', 'Lombok Tengah', '83511'),
(240, 22, 'Kabupaten', 'Lombok Timur', '83612'),
(241, 22, 'Kabupaten', 'Lombok Utara', '83711'),
(242, 33, 'Kota', 'Lubuk Linggau', '31614'),
(243, 11, 'Kabupaten', 'Lumajang', '67319'),
(244, 28, 'Kabupaten', 'Luwu', '91994'),
(245, 28, 'Kabupaten', 'Luwu Timur', '92981'),
(246, 28, 'Kabupaten', 'Luwu Utara', '92911'),
(247, 11, 'Kabupaten', 'Madiun', '63153'),
(248, 11, 'Kota', 'Madiun', '63122'),
(249, 10, 'Kabupaten', 'Magelang', '56519'),
(250, 10, 'Kota', 'Magelang', '56133'),
(251, 11, 'Kabupaten', 'Magetan', '63314'),
(252, 9, 'Kabupaten', 'Majalengka', '45412'),
(253, 27, 'Kabupaten', 'Majene', '91411'),
(254, 28, 'Kota', 'Makassar', '90111'),
(255, 11, 'Kabupaten', 'Malang', '65163'),
(256, 11, 'Kota', 'Malang', '65112'),
(257, 16, 'Kabupaten', 'Malinau', '77511'),
(258, 19, 'Kabupaten', 'Maluku Barat Daya', '97451'),
(259, 19, 'Kabupaten', 'Maluku Tengah', '97513'),
(260, 19, 'Kabupaten', 'Maluku Tenggara', '97651'),
(261, 19, 'Kabupaten', 'Maluku Tenggara Barat', '97465'),
(262, 27, 'Kabupaten', 'Mamasa', '91362'),
(263, 24, 'Kabupaten', 'Mamberamo Raya', '99381'),
(264, 24, 'Kabupaten', 'Mamberamo Tengah', '99553'),
(265, 27, 'Kabupaten', 'Mamuju', '91519'),
(266, 27, 'Kabupaten', 'Mamuju Utara', '91571'),
(267, 31, 'Kota', 'Manado', '95247'),
(268, 34, 'Kabupaten', 'Mandailing Natal', '22916'),
(269, 23, 'Kabupaten', 'Manggarai', '86551'),
(270, 23, 'Kabupaten', 'Manggarai Barat', '86711'),
(271, 23, 'Kabupaten', 'Manggarai Timur', '86811'),
(272, 25, 'Kabupaten', 'Manokwari', '98311'),
(273, 25, 'Kabupaten', 'Manokwari Selatan', '98355'),
(274, 24, 'Kabupaten', 'Mappi', '99853'),
(275, 28, 'Kabupaten', 'Maros', '90511'),
(276, 22, 'Kota', 'Mataram', '83131'),
(277, 25, 'Kabupaten', 'Maybrat', '98051'),
(278, 34, 'Kota', 'Medan', '20228'),
(279, 12, 'Kabupaten', 'Melawi', '78619'),
(280, 8, 'Kabupaten', 'Merangin', '37319'),
(281, 24, 'Kabupaten', 'Merauke', '99613'),
(282, 18, 'Kabupaten', 'Mesuji', '34911'),
(283, 18, 'Kota', 'Metro', '34111'),
(284, 24, 'Kabupaten', 'Mimika', '99962'),
(285, 31, 'Kabupaten', 'Minahasa', '95614'),
(286, 31, 'Kabupaten', 'Minahasa Selatan', '95914'),
(287, 31, 'Kabupaten', 'Minahasa Tenggara', '95995'),
(288, 31, 'Kabupaten', 'Minahasa Utara', '95316'),
(289, 11, 'Kabupaten', 'Mojokerto', '61382'),
(290, 11, 'Kota', 'Mojokerto', '61316'),
(291, 29, 'Kabupaten', 'Morowali', '94911'),
(292, 33, 'Kabupaten', 'Muara Enim', '31315'),
(293, 8, 'Kabupaten', 'Muaro Jambi', '36311'),
(294, 4, 'Kabupaten', 'Muko Muko', '38715'),
(295, 30, 'Kabupaten', 'Muna', '93611'),
(296, 14, 'Kabupaten', 'Murung Raya', '73911'),
(297, 33, 'Kabupaten', 'Musi Banyuasin', '30719'),
(298, 33, 'Kabupaten', 'Musi Rawas', '31661'),
(299, 24, 'Kabupaten', 'Nabire', '98816'),
(300, 21, 'Kabupaten', 'Nagan Raya', '23674'),
(301, 23, 'Kabupaten', 'Nagekeo', '86911'),
(302, 17, 'Kabupaten', 'Natuna', '29711'),
(303, 24, 'Kabupaten', 'Nduga', '99541'),
(304, 23, 'Kabupaten', 'Ngada', '86413'),
(305, 11, 'Kabupaten', 'Nganjuk', '64414'),
(306, 11, 'Kabupaten', 'Ngawi', '63219'),
(307, 34, 'Kabupaten', 'Nias', '22876'),
(308, 34, 'Kabupaten', 'Nias Barat', '22895'),
(309, 34, 'Kabupaten', 'Nias Selatan', '22865'),
(310, 34, 'Kabupaten', 'Nias Utara', '22856'),
(311, 16, 'Kabupaten', 'Nunukan', '77421'),
(312, 33, 'Kabupaten', 'Ogan Ilir', '30811'),
(313, 33, 'Kabupaten', 'Ogan Komering Ilir', '30618'),
(314, 33, 'Kabupaten', 'Ogan Komering Ulu', '32112'),
(315, 33, 'Kabupaten', 'Ogan Komering Ulu Selatan', '32211'),
(316, 33, 'Kabupaten', 'Ogan Komering Ulu Timur', '32312'),
(317, 11, 'Kabupaten', 'Pacitan', '63512'),
(318, 32, 'Kota', 'Padang', '25112'),
(319, 34, 'Kabupaten', 'Padang Lawas', '22763'),
(320, 34, 'Kabupaten', 'Padang Lawas Utara', '22753'),
(321, 32, 'Kota', 'Padang Panjang', '27122'),
(322, 32, 'Kabupaten', 'Padang Pariaman', '25583'),
(323, 34, 'Kota', 'Padang Sidempuan', '22727'),
(324, 33, 'Kota', 'Pagar Alam', '31512'),
(325, 34, 'Kabupaten', 'Pakpak Bharat', '22272'),
(326, 14, 'Kota', 'Palangka Raya', '73112'),
(327, 33, 'Kota', 'Palembang', '30111'),
(328, 28, 'Kota', 'Palopo', '91911'),
(329, 29, 'Kota', 'Palu', '94111'),
(330, 11, 'Kabupaten', 'Pamekasan', '69319'),
(331, 3, 'Kabupaten', 'Pandeglang', '42212'),
(332, 9, 'Kabupaten', 'Pangandaran', '46511'),
(333, 28, 'Kabupaten', 'Pangkajene Kepulauan', '90611'),
(334, 2, 'Kota', 'Pangkal Pinang', '33115'),
(335, 24, 'Kabupaten', 'Paniai', '98765'),
(336, 28, 'Kota', 'Parepare', '91123'),
(337, 32, 'Kota', 'Pariaman', '25511'),
(338, 29, 'Kabupaten', 'Parigi Moutong', '94411'),
(339, 32, 'Kabupaten', 'Pasaman', '26318'),
(340, 32, 'Kabupaten', 'Pasaman Barat', '26511'),
(341, 15, 'Kabupaten', 'Paser', '76211'),
(342, 11, 'Kabupaten', 'Pasuruan', '67153'),
(343, 11, 'Kota', 'Pasuruan', '67118'),
(344, 10, 'Kabupaten', 'Pati', '59114'),
(345, 32, 'Kota', 'Payakumbuh', '26213'),
(346, 25, 'Kabupaten', 'Pegunungan Arfak', '98354'),
(347, 24, 'Kabupaten', 'Pegunungan Bintang', '99573'),
(348, 10, 'Kabupaten', 'Pekalongan', '51161'),
(349, 10, 'Kota', 'Pekalongan', '51122'),
(350, 26, 'Kota', 'Pekanbaru', '28112'),
(351, 26, 'Kabupaten', 'Pelalawan', '28311'),
(352, 10, 'Kabupaten', 'Pemalang', '52319'),
(353, 34, 'Kota', 'Pematang Siantar', '21126'),
(354, 15, 'Kabupaten', 'Penajam Paser Utara', '76311'),
(355, 18, 'Kabupaten', 'Pesawaran', '35312'),
(356, 18, 'Kabupaten', 'Pesisir Barat', '35974'),
(357, 32, 'Kabupaten', 'Pesisir Selatan', '25611'),
(358, 21, 'Kabupaten', 'Pidie', '24116'),
(359, 21, 'Kabupaten', 'Pidie Jaya', '24186'),
(360, 28, 'Kabupaten', 'Pinrang', '91251'),
(361, 7, 'Kabupaten', 'Pohuwato', '96419'),
(362, 27, 'Kabupaten', 'Polewali Mandar', '91311'),
(363, 11, 'Kabupaten', 'Ponorogo', '63411'),
(364, 12, 'Kabupaten', 'Pontianak', '78971'),
(365, 12, 'Kota', 'Pontianak', '78112'),
(366, 29, 'Kabupaten', 'Poso', '94615'),
(367, 33, 'Kota', 'Prabumulih', '31121'),
(368, 18, 'Kabupaten', 'Pringsewu', '35719'),
(369, 11, 'Kabupaten', 'Probolinggo', '67282'),
(370, 11, 'Kota', 'Probolinggo', '67215'),
(371, 14, 'Kabupaten', 'Pulang Pisau', '74811'),
(372, 20, 'Kabupaten', 'Pulau Morotai', '97771'),
(373, 24, 'Kabupaten', 'Puncak', '98981'),
(374, 24, 'Kabupaten', 'Puncak Jaya', '98979'),
(375, 10, 'Kabupaten', 'Purbalingga', '53312'),
(376, 9, 'Kabupaten', 'Purwakarta', '41119'),
(377, 10, 'Kabupaten', 'Purworejo', '54111'),
(378, 25, 'Kabupaten', 'Raja Ampat', '98489'),
(379, 4, 'Kabupaten', 'Rejang Lebong', '39112'),
(380, 10, 'Kabupaten', 'Rembang', '59219'),
(381, 26, 'Kabupaten', 'Rokan Hilir', '28992'),
(382, 26, 'Kabupaten', 'Rokan Hulu', '28511'),
(383, 23, 'Kabupaten', 'Rote Ndao', '85982'),
(384, 21, 'Kota', 'Sabang', '23512'),
(385, 23, 'Kabupaten', 'Sabu Raijua', '85391'),
(386, 10, 'Kota', 'Salatiga', '50711'),
(387, 15, 'Kota', 'Samarinda', '75133'),
(388, 12, 'Kabupaten', 'Sambas', '79453'),
(389, 34, 'Kabupaten', 'Samosir', '22392'),
(390, 11, 'Kabupaten', 'Sampang', '69219'),
(391, 12, 'Kabupaten', 'Sanggau', '78557'),
(392, 24, 'Kabupaten', 'Sarmi', '99373'),
(393, 8, 'Kabupaten', 'Sarolangun', '37419'),
(394, 32, 'Kota', 'Sawah Lunto', '27416'),
(395, 12, 'Kabupaten', 'Sekadau', '79583'),
(396, 28, 'Kabupaten', 'Selayar (Kepulauan Selayar)', '92812'),
(397, 4, 'Kabupaten', 'Seluma', '38811'),
(398, 10, 'Kabupaten', 'Semarang', '50511'),
(399, 10, 'Kota', 'Semarang', '50135'),
(400, 19, 'Kabupaten', 'Seram Bagian Barat', '97561'),
(401, 19, 'Kabupaten', 'Seram Bagian Timur', '97581'),
(402, 3, 'Kabupaten', 'Serang', '42182'),
(403, 3, 'Kota', 'Serang', '42111'),
(404, 34, 'Kabupaten', 'Serdang Bedagai', '20915'),
(405, 14, 'Kabupaten', 'Seruyan', '74211'),
(406, 26, 'Kabupaten', 'Siak', '28623'),
(407, 34, 'Kota', 'Sibolga', '22522'),
(408, 28, 'Kabupaten', 'Sidenreng Rappang/Rapang', '91613'),
(409, 11, 'Kabupaten', 'Sidoarjo', '61219'),
(410, 29, 'Kabupaten', 'Sigi', '94364'),
(411, 32, 'Kabupaten', 'Sijunjung (Sawah Lunto Sijunjung)', '27511'),
(412, 23, 'Kabupaten', 'Sikka', '86121'),
(413, 34, 'Kabupaten', 'Simalungun', '21162'),
(414, 21, 'Kabupaten', 'Simeulue', '23891'),
(415, 12, 'Kota', 'Singkawang', '79117'),
(416, 28, 'Kabupaten', 'Sinjai', '92615'),
(417, 12, 'Kabupaten', 'Sintang', '78619'),
(418, 11, 'Kabupaten', 'Situbondo', '68316'),
(419, 5, 'Kabupaten', 'Sleman', '55513'),
(420, 32, 'Kabupaten', 'Solok', '27365'),
(421, 32, 'Kota', 'Solok', '27315'),
(422, 32, 'Kabupaten', 'Solok Selatan', '27779'),
(423, 28, 'Kabupaten', 'Soppeng', '90812'),
(424, 25, 'Kabupaten', 'Sorong', '98431'),
(425, 25, 'Kota', 'Sorong', '98411'),
(426, 25, 'Kabupaten', 'Sorong Selatan', '98454'),
(427, 10, 'Kabupaten', 'Sragen', '57211'),
(428, 9, 'Kabupaten', 'Subang', '41215'),
(429, 21, 'Kota', 'Subulussalam', '24882'),
(430, 9, 'Kabupaten', 'Sukabumi', '43311'),
(431, 9, 'Kota', 'Sukabumi', '43114'),
(432, 14, 'Kabupaten', 'Sukamara', '74712'),
(433, 10, 'Kabupaten', 'Sukoharjo', '57514'),
(434, 23, 'Kabupaten', 'Sumba Barat', '87219'),
(435, 23, 'Kabupaten', 'Sumba Barat Daya', '87453'),
(436, 23, 'Kabupaten', 'Sumba Tengah', '87358'),
(437, 23, 'Kabupaten', 'Sumba Timur', '87112'),
(438, 22, 'Kabupaten', 'Sumbawa', '84315'),
(439, 22, 'Kabupaten', 'Sumbawa Barat', '84419'),
(440, 9, 'Kabupaten', 'Sumedang', '45326'),
(441, 11, 'Kabupaten', 'Sumenep', '69413'),
(442, 8, 'Kota', 'Sungaipenuh', '37113'),
(443, 24, 'Kabupaten', 'Supiori', '98164'),
(444, 11, 'Kota', 'Surabaya', '60119'),
(445, 10, 'Kota', 'Surakarta (Solo)', '57113'),
(446, 13, 'Kabupaten', 'Tabalong', '71513'),
(447, 1, 'Kabupaten', 'Tabanan', '82119'),
(448, 28, 'Kabupaten', 'Takalar', '92212'),
(449, 25, 'Kabupaten', 'Tambrauw', '98475'),
(450, 16, 'Kabupaten', 'Tana Tidung', '77611'),
(451, 28, 'Kabupaten', 'Tana Toraja', '91819'),
(452, 13, 'Kabupaten', 'Tanah Bumbu', '72211'),
(453, 32, 'Kabupaten', 'Tanah Datar', '27211'),
(454, 13, 'Kabupaten', 'Tanah Laut', '70811'),
(455, 3, 'Kabupaten', 'Tangerang', '15914'),
(456, 3, 'Kota', 'Tangerang', '15111'),
(457, 3, 'Kota', 'Tangerang Selatan', '15435'),
(458, 18, 'Kabupaten', 'Tanggamus', '35619'),
(459, 34, 'Kota', 'Tanjung Balai', '21321'),
(460, 8, 'Kabupaten', 'Tanjung Jabung Barat', '36513'),
(461, 8, 'Kabupaten', 'Tanjung Jabung Timur', '36719'),
(462, 17, 'Kota', 'Tanjung Pinang', '29111'),
(463, 34, 'Kabupaten', 'Tapanuli Selatan', '22742'),
(464, 34, 'Kabupaten', 'Tapanuli Tengah', '22611'),
(465, 34, 'Kabupaten', 'Tapanuli Utara', '22414'),
(466, 13, 'Kabupaten', 'Tapin', '71119'),
(467, 16, 'Kota', 'Tarakan', '77114'),
(468, 9, 'Kabupaten', 'Tasikmalaya', '46411'),
(469, 9, 'Kota', 'Tasikmalaya', '46116'),
(470, 34, 'Kota', 'Tebing Tinggi', '20632'),
(471, 8, 'Kabupaten', 'Tebo', '37519'),
(472, 10, 'Kabupaten', 'Tegal', '52419'),
(473, 10, 'Kota', 'Tegal', '52114'),
(474, 25, 'Kabupaten', 'Teluk Bintuni', '98551'),
(475, 25, 'Kabupaten', 'Teluk Wondama', '98591'),
(476, 10, 'Kabupaten', 'Temanggung', '56212'),
(477, 20, 'Kota', 'Ternate', '97714'),
(478, 20, 'Kota', 'Tidore Kepulauan', '97815'),
(479, 23, 'Kabupaten', 'Timor Tengah Selatan', '85562'),
(480, 23, 'Kabupaten', 'Timor Tengah Utara', '85612'),
(481, 34, 'Kabupaten', 'Toba Samosir', '22316'),
(482, 29, 'Kabupaten', 'Tojo Una-Una', '94683'),
(483, 29, 'Kabupaten', 'Toli-Toli', '94542'),
(484, 24, 'Kabupaten', 'Tolikara', '99411'),
(485, 31, 'Kota', 'Tomohon', '95416'),
(486, 28, 'Kabupaten', 'Toraja Utara', '91831'),
(487, 11, 'Kabupaten', 'Trenggalek', '66312'),
(488, 19, 'Kota', 'Tual', '97612'),
(489, 11, 'Kabupaten', 'Tuban', '62319'),
(490, 18, 'Kabupaten', 'Tulang Bawang', '34613'),
(491, 18, 'Kabupaten', 'Tulang Bawang Barat', '34419'),
(492, 11, 'Kabupaten', 'Tulungagung', '66212'),
(493, 28, 'Kabupaten', 'Wajo', '90911'),
(494, 30, 'Kabupaten', 'Wakatobi', '93791'),
(495, 24, 'Kabupaten', 'Waropen', '98269'),
(496, 18, 'Kabupaten', 'Way Kanan', '34711'),
(497, 10, 'Kabupaten', 'Wonogiri', '57619'),
(498, 10, 'Kabupaten', 'Wonosobo', '56311'),
(499, 24, 'Kabupaten', 'Yahukimo', '99041'),
(500, 24, 'Kabupaten', 'Yalimo', '99481'),
(501, 5, 'Kota', 'Yogyakarta', '55111');

-- --------------------------------------------------------

--
-- Table structure for table `kurir`
--

CREATE TABLE `kurir` (
  `id` int NOT NULL,
  `kode` varchar(10) COLLATE utf8mb3_swedish_ci NOT NULL,
  `nama` varchar(50) COLLATE utf8mb3_swedish_ci NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb3_swedish_ci DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `kurir`
--

INSERT INTO `kurir` (`id`, `kode`, `nama`, `status`) VALUES
(1, 'jne', 'JNE', 'aktif'),
(2, 'pos', 'POS', 'aktif'),
(3, 'tiki', 'TIKI', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `ongkir_cache`
--

CREATE TABLE `ongkir_cache` (
  `id` int NOT NULL,
  `origin_city_id` int NOT NULL,
  `destination_city_id` int NOT NULL,
  `weight` int NOT NULL,
  `courier` varchar(10) COLLATE utf8mb3_swedish_ci NOT NULL,
  `service` varchar(50) COLLATE utf8mb3_swedish_ci NOT NULL,
  `cost` int NOT NULL,
  `etd` varchar(20) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` timestamp NULL DEFAULT ((now() + interval 24 hour))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int NOT NULL,
  `kode_pesanan` varchar(20) COLLATE utf8mb3_swedish_ci NOT NULL,
  `user_id` int NOT NULL,
  `alamat_pengiriman_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `ongkir` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('cod','transfer') COLLATE utf8mb3_swedish_ci NOT NULL,
  `kurir_kode` varchar(10) COLLATE utf8mb3_swedish_ci NOT NULL,
  `kurir_service` varchar(50) COLLATE utf8mb3_swedish_ci NOT NULL,
  `estimasi_sampai` varchar(20) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `berat_total` decimal(8,2) NOT NULL,
  `bukti_pembayaran` varchar(255) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb3_swedish_ci,
  `status_pesanan` enum('pending','confirmed','processed','shipped','delivered','cancelled') COLLATE utf8mb3_swedish_ci DEFAULT 'pending',
  `resi_pengiriman` varchar(50) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Triggers `pesanan`
--
DELIMITER $$
CREATE TRIGGER `generate_kode_pesanan` BEFORE INSERT ON `pesanan` FOR EACH ROW BEGIN
    DECLARE next_id INT;
    SELECT AUTO_INCREMENT INTO next_id 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pesanan';
    
    SET NEW.kode_pesanan = CONCAT('CF', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(next_id, 4, '0'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `petani`
--

CREATE TABLE `petani` (
  `id` int NOT NULL,
  `nama_petani` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `no_telepon` varchar(15) COLLATE utf8mb3_swedish_ci NOT NULL,
  `alamat_kebun` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `luas_lahan` decimal(8,2) NOT NULL,
  `jenis_kopi` enum('arabika','robusta') COLLATE utf8mb3_swedish_ci NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `petani`
--

INSERT INTO `petani` (`id`, `nama_petani`, `no_telepon`, `alamat_kebun`, `luas_lahan`, `jenis_kopi`, `created_by`, `created_at`) VALUES
(1, 'Pak Mahmud', '081234567801', 'Desa Reje Baru, Wih Pesam', '2.50', 'arabika', 2, '2025-07-02 08:10:40'),
(2, 'Pak Usman', '081234567802', 'Desa Kute Panang, Wih Pesam', '3.00', 'arabika', 2, '2025-07-02 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int NOT NULL,
  `nama_produk` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `kategori_id` int NOT NULL,
  `penjual_id` int NOT NULL,
  `foto` varchar(255) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `berat` decimal(8,2) NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb3_swedish_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nama_produk`, `deskripsi`, `harga`, `stok`, `kategori_id`, `penjual_id`, `foto`, `berat`, `status`, `created_at`) VALUES
(1, 'Green Bean Arabika Gayo 1kg', 'Biji kopi arabika dari Wih Pesam, grade premium', '280000.00', 50, 1, 2, NULL, '1000.00', 'aktif', '2025-07-02 08:10:40'),
(2, 'Kopi Gayo Medium Roast 250g', 'Kopi bubuk arabika roasting medium, cocok untuk espresso', '85000.00', 100, 2, 3, NULL, '250.00', 'aktif', '2025-07-02 08:10:40'),
(3, 'Kopi Gayo Premium 100g', 'Kemasan retail kopi gayo premium', '45000.00', 200, 2, 4, NULL, '100.00', 'aktif', '2025-07-02 08:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `provinsi`
--

CREATE TABLE `provinsi` (
  `province_id` int NOT NULL,
  `province` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `provinsi`
--

INSERT INTO `provinsi` (`province_id`, `province`) VALUES
(1, 'Bali'),
(2, 'Bangka Belitung'),
(3, 'Banten'),
(4, 'Bengkulu'),
(5, 'DI Yogyakarta'),
(6, 'DKI Jakarta'),
(7, 'Gorontalo'),
(8, 'Jambi'),
(9, 'Jawa Barat'),
(10, 'Jawa Tengah'),
(11, 'Jawa Timur'),
(12, 'Kalimantan Barat'),
(13, 'Kalimantan Selatan'),
(14, 'Kalimantan Tengah'),
(15, 'Kalimantan Timur'),
(16, 'Kalimantan Utara'),
(17, 'Kepulauan Riau'),
(18, 'Lampung'),
(19, 'Maluku'),
(20, 'Maluku Utara'),
(21, 'Nanggroe Aceh Darussalam (NAD)'),
(22, 'Nusa Tenggara Barat (NTB)'),
(23, 'Nusa Tenggara Timur (NTT)'),
(24, 'Papua'),
(25, 'Papua Barat'),
(26, 'Riau'),
(27, 'Sulawesi Barat'),
(28, 'Sulawesi Selatan'),
(29, 'Sulawesi Tengah'),
(30, 'Sulawesi Tenggara'),
(31, 'Sulawesi Utara'),
(32, 'Sumatera Barat'),
(33, 'Sumatera Selatan'),
(34, 'Sumatera Utara');

-- --------------------------------------------------------

--
-- Table structure for table `retur`
--

CREATE TABLE `retur` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `user_id` int NOT NULL,
  `alasan` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `foto_bukti` varchar(255) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `status_retur` enum('pending','approved','rejected','completed') COLLATE utf8mb3_swedish_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_gudang`
--

CREATE TABLE `stok_gudang` (
  `id` int NOT NULL,
  `produk_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `jumlah_stok` decimal(10,2) NOT NULL,
  `lokasi_gudang` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `id` int NOT NULL,
  `pesanan_id` int NOT NULL,
  `produk_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `komentar` text COLLATE utf8mb3_swedish_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb3_swedish_ci NOT NULL,
  `no_telepon` varchar(15) COLLATE utf8mb3_swedish_ci NOT NULL,
  `alamat` text COLLATE utf8mb3_swedish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_swedish_ci NOT NULL,
  `role` enum('admin','pembeli','pengepul','roasting','penjual') COLLATE utf8mb3_swedish_ci NOT NULL,
  `nama_toko` varchar(100) COLLATE utf8mb3_swedish_ci DEFAULT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb3_swedish_ci DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `no_telepon`, `alamat`, `password`, `role`, `nama_toko`, `status`, `created_at`) VALUES
(1, 'Admin Cofflow', 'admin@cofflow.com', '081234567890', 'Bener Meriah, Aceh', 'admin123', 'admin', NULL, 'aktif', '2025-07-02 08:10:40'),
(2, 'Ahmad Pengepul', 'ahmad@cofflow.com', '081234567891', 'Wih Pesam, Bener Meriah', 'password123', 'pengepul', 'CV Gayo Collector', 'aktif', '2025-07-02 08:10:40'),
(3, 'Sari Roasting', 'sari@cofflow.com', '081234567892', 'Kebayakan, Bener Meriah', 'password123', 'roasting', 'Gayo Roastery', 'aktif', '2025-07-02 08:10:40'),
(4, 'Budi Penjual', 'budi@cofflow.com', '081234567893', 'Silih Nara, Bener Meriah', 'password123', 'penjual', 'Toko Kopi Gayo', 'aktif', '2025-07-02 08:10:40'),
(5, 'Customer Test', 'customer@cofflow.com', '081234567894', 'Banda Aceh', 'password123', 'pembeli', NULL, 'aktif', '2025-07-02 08:10:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alamat_pengiriman`
--
ALTER TABLE `alamat_pengiriman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `idx_alamat_user` (`user_id`);

--
-- Indexes for table `batch_produksi`
--
ALTER TABLE `batch_produksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_batch` (`kode_batch`),
  ADD KEY `petani_id` (`petani_id`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `pembeli_id` (`pembeli_id`),
  ADD KEY `penjual_id` (`penjual_id`);

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `informasi`
--
ALTER TABLE `informasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_produk` (`user_id`,`produk_id`),
  ADD KEY `produk_id` (`produk_id`);

--
-- Indexes for table `kota`
--
ALTER TABLE `kota`
  ADD PRIMARY KEY (`city_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `kurir`
--
ALTER TABLE `kurir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `ongkir_cache`
--
ALTER TABLE `ongkir_cache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ongkir` (`origin_city_id`,`destination_city_id`,`weight`,`courier`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `alamat_pengiriman_id` (`alamat_pengiriman_id`),
  ADD KEY `kurir_kode` (`kurir_kode`),
  ADD KEY `idx_pesanan_user` (`user_id`),
  ADD KEY `idx_pesanan_status` (`status_pesanan`);

--
-- Indexes for table `petani`
--
ALTER TABLE `petani`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produk_kategori` (`kategori_id`),
  ADD KEY `idx_produk_penjual` (`penjual_id`);

--
-- Indexes for table `provinsi`
--
ALTER TABLE `provinsi`
  ADD PRIMARY KEY (`province_id`);

--
-- Indexes for table `retur`
--
ALTER TABLE `retur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `produk_id` (`produk_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `no_telepon` (`no_telepon`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alamat_pengiriman`
--
ALTER TABLE `alamat_pengiriman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `batch_produksi`
--
ALTER TABLE `batch_produksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `informasi`
--
ALTER TABLE `informasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kurir`
--
ALTER TABLE `kurir`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ongkir_cache`
--
ALTER TABLE `ongkir_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petani`
--
ALTER TABLE `petani`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `retur`
--
ALTER TABLE `retur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alamat_pengiriman`
--
ALTER TABLE `alamat_pengiriman`
  ADD CONSTRAINT `alamat_pengiriman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alamat_pengiriman_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinsi` (`province_id`),
  ADD CONSTRAINT `alamat_pengiriman_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `kota` (`city_id`);

--
-- Constraints for table `batch_produksi`
--
ALTER TABLE `batch_produksi`
  ADD CONSTRAINT `batch_produksi_ibfk_1` FOREIGN KEY (`petani_id`) REFERENCES `petani` (`id`);

--
-- Constraints for table `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`pembeli_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `chat_ibfk_3` FOREIGN KEY (`penjual_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`);

--
-- Constraints for table `informasi`
--
ALTER TABLE `informasi`
  ADD CONSTRAINT `informasi_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kota`
--
ALTER TABLE `kota`
  ADD CONSTRAINT `kota_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinsi` (`province_id`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`alamat_pengiriman_id`) REFERENCES `alamat_pengiriman` (`id`),
  ADD CONSTRAINT `pesanan_ibfk_3` FOREIGN KEY (`kurir_kode`) REFERENCES `kurir` (`kode`);

--
-- Constraints for table `petani`
--
ALTER TABLE `petani`
  ADD CONSTRAINT `petani_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_produk` (`id`),
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`penjual_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `retur`
--
ALTER TABLE `retur`
  ADD CONSTRAINT `retur_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`),
  ADD CONSTRAINT `retur_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD CONSTRAINT `stok_gudang_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `stok_gudang_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `batch_produksi` (`id`);

--
-- Constraints for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `ulasan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`),
  ADD CONSTRAINT `ulasan_ibfk_2` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`),
  ADD CONSTRAINT `ulasan_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

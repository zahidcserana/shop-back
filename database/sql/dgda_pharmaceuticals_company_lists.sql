-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2019 at 08:17 AM
-- Server version: 10.1.40-MariaDB
-- PHP Version: 7.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dgdasp`
--

-- --------------------------------------------------------

--
-- Table structure for table `dgda_pharmaceuticals_company_lists`
--

CREATE TABLE `dgda_pharmaceuticals_company_lists` (
  `id` int(11) NOT NULL,
  `manufacturer_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `dgda_pharmaceuticals_company_lists`
--

INSERT INTO `dgda_pharmaceuticals_company_lists` (`id`, `manufacturer_name`) VALUES
(1, 'ACI HealthCare Limited'),
(2, 'Acmunio International Ltd'),
(3, 'Active Fine Chemicals Ltd'),
(4, 'Ad-din Pharmaceuticals Ltd'),
(5, 'Advanced Chemical Industries Limited'),
(6, 'Advent Pharma Ltd'),
(7, 'Al-Madina Pharmaceuticals Ltd'),
(8, 'Albion Laboratories Ltd'),
(9, 'Alco Pharma Limited'),
(10, 'Alkad Laboratories'),
(11, 'Allied Pharmaceuticals Ltd'),
(12, 'Ambee Pharmaceuticals Ltd'),
(13, 'Amico Laboratories Ltd'),
(14, 'Amulet Pharmaceuticals Ltd'),
(15, 'APC Pharma Limited'),
(16, 'Apex Pharma Ltd'),
(17, 'Apollo Pharmaceutical Laboratories Ltd'),
(18, 'Aristopharma Limited'),
(19, 'Asiatic Laboratories Ltd'),
(20, 'Astra Biopharmaceuticals Ltd'),
(21, 'Aztec Pharmaceuticals Ltd'),
(22, 'Bangladesh Antibiotic Industries Limited'),
(23, 'Beacon Pharmaceuticals Ltd'),
(24, 'Belsen Pharmaceuticals Ltd'),
(25, 'Bengal Drugs & Chemical Works Pharm. Ltd'),
(26, 'Bengal Remedies Ltd'),
(27, 'Benham Pharmaceuticals Ltd'),
(28, 'Beximco Pharmaceuticals Ltd'),
(29, 'Biogen Pharmaceuticals Ltd'),
(30, 'Biopharma Ltd'),
(31, 'Bios Pharmaceuticals Ltd'),
(32, 'Bridge Pharmaceuticals Ltd'),
(33, 'Bristol Pharma Ltd'),
(34, 'Centeon Pharma Limited'),
(35, 'Central Pharmaceutical Ltd'),
(36, 'Chemist Laboratories Ltd'),
(37, 'Cipla Ltd'),
(38, 'Concord Pharmaceuticals Ltd'),
(39, 'Cosmic Pharma Ltd'),
(40, 'Cosmo Pharma Laboratories Ltd'),
(41, 'Decent Pharma Laboratories Ltd'),
(42, 'Delta Chemicals Ltd'),
(43, 'Delta Pharma Limited'),
(44, 'Desh Pharmaceuticals Ltd'),
(45, 'Doctor Tims Pharmaceuticals Ltd'),
(46, 'Doctors Chemicals Works Ltd'),
(47, 'Drug International Ltd'),
(48, 'EDCL'),
(49, 'Edruc Ltd'),
(50, 'EMCS Pharma Limited'),
(51, 'Eon Pharmaceuticals Ltd'),
(52, 'Eskayef Pharmaceuticals Ltd'),
(53, 'Ethical Drug Ltd'),
(54, 'Euro Pharma Ltd'),
(55, 'Everest Pharmaceuticals Ltd'),
(56, 'FnF Pharmaceuticals Ltd'),
(57, 'G. A. Company Ltd'),
(58, 'General Pharmaceuticals Ltd'),
(59, 'Gentry Pharmaceuticals Ltd'),
(60, 'Genvio Pharma Ltd'),
(61, 'Get Well Limited'),
(62, 'Global Capsules Ltd'),
(63, 'Global Heavy Chemicals Ltd'),
(64, 'Globe Pharmaceuticals Ltd'),
(65, 'Globex Pharmaceuticals Ltd'),
(66, 'Gonoshasthaya Antibiotic Ltd'),
(67, 'Gonoshasthaya Basic Chemical Ltd'),
(68, 'Gonoshasthaya Pharmaceuticals Ltd'),
(69, 'Goodman Pharmaceuticals Ltd'),
(70, 'Greenland Pharmaceuticals Ltd'),
(71, 'Guardian Healthcare Ltd'),
(72, 'Hallmark Pharmaceuticals Ltd'),
(73, 'Healthcare Pharmaceuticals Ltd'),
(74, 'Hope Pharmaceuticals Ltd'),
(75, 'Hudson Pharmaceuticals Ltd'),
(76, 'Ibn Sina Pharmaceutical Ind. Ltd'),
(77, 'Incepta Chemicals Ltd'),
(78, 'Incepta Pharmaceuticals Ltd'),
(79, 'Incepta Vaccine Limited'),
(80, 'Indo-Bangla Pharmaceuticals Ltd'),
(81, 'Institute of Public Health'),
(82, 'Islam Oxygen (Pvt) Ltd'),
(83, 'Jalalabad Pharmaceuticals Ltd'),
(84, 'Jayson Pharmaceuticals Ltd'),
(85, 'JMI Industrial Gas Ltd'),
(86, 'JMI Syringes & Medical Devices Ltd'),
(87, 'Julphar Bangladesh Ltd'),
(88, 'Kemiko Pharmaceuticals Ltd'),
(89, 'Kumudini Pharma Ltd'),
(90, 'Labaid Pharmaceuticals Ltd'),
(91, 'Leon Pharmaceuticals Ltd'),
(92, 'Libra Pharmaceuticls Ltd'),
(93, 'Linde Bangladesh Limited'),
(94, 'Maks Drugs Ltd'),
(95, 'Marker Pharmaceuticals Ltd'),
(96, 'Marksman Pharmaceutical Ltd'),
(97, 'Medicon Pharmaceuticals Ltd'),
(98, 'Medimet Pharmaceuticals Ltd'),
(99, 'MedRx Life Science Ltd'),
(100, 'Millat Pharmaceuticals Ltd'),
(101, 'Modern Pharmaceuticals Ltd'),
(102, 'Momotaz Pharmaceuticals Ltd'),
(103, 'Monicopharma Limited'),
(104, 'Monomedi Bangladesh Ltd'),
(105, 'MSF Pharmaceuticals Ltd'),
(106, 'MST Pharma and Healthcare Ltd'),
(107, 'Mundipharma (Bangladesh) Pvt. Ltd'),
(108, 'Naafco Pharma Ltd'),
(109, 'National Laboratories Ltd'),
(110, 'Navana Pharmaceuticals Ltd'),
(111, 'Newtec Pharmaceuticals Ltd'),
(112, 'Nip Chemicals And Pharmaceuticals Ltd'),
(113, 'Nipa Pharmaceuticals Ltd'),
(114, 'NIPRO JMI Company Ltd'),
(115, 'NIPRO JMI Pharma Limited'),
(116, 'Novartis (Bangladesh) Ltd'),
(117, 'Novelta Bestway Pharmaceuticals Ltd'),
(118, 'Novo Healthcare and Pharma Ltd'),
(119, 'Novus Pharmaceuticals Ltd'),
(120, 'Nuvista Pharma Ltd'),
(121, 'One Pharma Ltd'),
(122, 'Opso Saline Ltd'),
(123, 'Opsonin Bulk Drugs Ltd'),
(124, 'Opsonin Pharma Limited'),
(125, 'Orbit Pharmaceuticals Ltd'),
(126, 'Organic Health Care'),
(127, 'Orion Infusion Ltd'),
(128, 'Orion Pharma Ltd'),
(129, 'Oyster Pharmaceuticals Ltd'),
(130, 'Pacific Pharmaceuticals Ltd'),
(131, 'Peoples Pharma Ltd'),
(132, 'Pharmacil Ltd'),
(133, 'Pharmadesh Laboratories Ltd'),
(134, 'Pharmasia Ltd'),
(135, 'Pharmatek Chemicals Ltd'),
(136, 'Pharmik Laboratories Ltd'),
(137, 'Phoenix Chemicals Laboratory (BD) Ltd'),
(138, 'Popular Pharmaceuticals Ltd'),
(139, 'Premier Pharmaceuticals'),
(140, 'Prime Pharmaceuticals Ltd'),
(141, 'Quality Pharmaceuticals (Pvt) Ltd'),
(142, 'Radiant Pharmaceuticals Ltd'),
(143, 'Rahman Chemicals Ltd'),
(144, 'Rampart-Power Bangladesh Ltd'),
(145, 'Rangs Pharmaceuticals Ltd'),
(146, 'Reckitt Benckiser Bangladesh Ltd'),
(147, 'Reliance Pharmaceuticals Ltd'),
(148, 'Reman Drug Laboratories Ltd'),
(149, 'Remo Chemical Ltd'),
(150, 'Renata Limited'),
(151, 'Renata Oncology Limited'),
(152, 'Rephco Pharmaceuticals Ltd'),
(153, 'RN Pharmaceuticals'),
(154, 'S. N. Pharmaceuticals Ltd'),
(155, 'Salton Pharmaceuticals Ltd'),
(156, 'Sanofi Bangladesh Ltd'),
(157, 'Save Pharmaceutical'),
(158, 'Seba Laboratories Ltd'),
(159, 'Seema Pharmaceuticals Ltd'),
(160, 'Sharif Pharmaceuticals Ltd'),
(161, 'Silco Pharmaceuticlas Ltd'),
(162, 'Silva Pharmaceuticals Ltd'),
(163, 'SMC Enterprise Limited'),
(164, 'Sodical Chemical Ltd'),
(165, 'Somatec Pharmaceuticals Ltd'),
(166, 'Spectra Oxygen Limited'),
(167, 'Square Cephalosporins Ltd'),
(168, 'Square Formulations Ltd'),
(169, 'Square Pharmaceuticals Ltd'),
(170, 'Standard Laboratories Ltd'),
(171, 'Sun Pharmaceutical (Bangladesh) Ltd'),
(172, 'Sunman-Birdem Pharma Ltd'),
(173, 'Super Power Pharmaceuticals Ltd'),
(174, 'Supreme Pharmaceuticals Ltd'),
(175, 'Team Pharmaceuticals Ltd'),
(176, 'Techno Drugs Ltd'),
(177, 'The ACME Laboratories Ltd'),
(178, 'The White Horse Pharmaceuticals Ltd'),
(179, 'Unimed Unihealth Manufacturers Ltd'),
(180, 'Union Pharmaceuticals Ltd'),
(181, 'Unique Pharmaceutical Ltd'),
(182, 'United Chemicals & Pharmaceuticals Ltd'),
(183, 'Veritas Pharmaceuticals Ltd'),
(184, 'Virgo Pharmaceuticals Ltd'),
(185, 'World Chemical Industry Ltd'),
(186, 'Zenith Pharmaceuticals Ltd'),
(187, 'Ziska Pharmaceuticals Ltd');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dgda_pharmaceuticals_company_lists`
--
ALTER TABLE `dgda_pharmaceuticals_company_lists`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dgda_pharmaceuticals_company_lists`
--
ALTER TABLE `dgda_pharmaceuticals_company_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

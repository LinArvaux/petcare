-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2026 at 07:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `petcare_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `target_id` int(11) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `can_undo` tinyint(1) DEFAULT 0,
  `undo_meta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `details`, `created_at`, `target_id`, `target_type`, `can_undo`, `undo_meta`) VALUES
(1, 1, 'Corrected AI Result', 'Flagged image #14 and reassigned to Healthy/cat.', '2026-09-13 18:20:31', NULL, NULL, 0, NULL),
(2, 1, 'Corrected AI Result', 'Flagged image #19 and assigned original image to Healthy/cat.', '2026-09-13 19:03:46', NULL, NULL, 0, NULL),
(3, 1, 'Corrected AI Result', 'Flagged image #20 and assigned original image to Healthy/cat.', '2026-09-13 19:04:39', NULL, NULL, 0, NULL),
(4, 1, 'Uploaded Image', 'Uploaded image to staging for Healthy/cat.', '2026-09-13 19:20:30', NULL, NULL, 0, NULL),
(5, 1, 'Uploaded Image', 'Uploaded image to staging for Diseased/cat/FLEA_ALLERGY.', '2026-09-13 19:20:48', NULL, NULL, 0, NULL),
(6, 1, 'Rejected Image', 'Rejected image intended for Diseased/cat/FLEA_ALLERGY.', '2026-09-13 19:21:02', 11, 'dataset_images', 0, NULL),
(7, 1, 'Approved Image', 'Approved image into Healthy/cat.', '2026-09-13 19:21:07', 10, 'dataset_images', 0, NULL),
(8, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-13 22:17:21', NULL, NULL, 0, NULL),
(9, 1, 'Corrected AI Result', 'Flagged image #23 and assigned to Healthy/cat.', '2026-09-13 22:42:32', 23, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/cat/0f210d552b92c7204442138de5151b55_jpg.rf.c5ea130e7e7b87d5567ba7c3a2eea4bb.jpg'),
(10, 1, 'Corrected AI Result', 'Flagged image #37 and assigned to Healthy/dog.', '2026-09-13 22:43:13', 37, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/e2dec27e-735e-42e0-a3bf-33bfbe924e84.jpg'),
(11, 1, 'Corrected AI Result', 'Flagged image #46 and assigned to Healthy/dog.', '2026-09-13 22:43:29', 46, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/805751534_2344101779669255_4353199906505687313_n.jpg'),
(12, 1, 'Corrected AI Result', 'Flagged image #45 and assigned to Healthy/dog.', '2026-09-13 22:43:37', 45, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/b2474236-ec42-4f93-8fdf-5c1cbd0d3763.jpg'),
(13, 1, 'Corrected AI Result', 'Flagged image #43 and assigned to Healthy/dog.', '2026-09-13 22:43:45', 43, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/b29399ad-1267-4487-8fdb-b74c993eede5.jpg'),
(14, 1, 'Corrected AI Result', 'Flagged image #42 and assigned to Healthy/dog.', '2026-09-13 22:44:01', 42, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/a78aab48-edf7-4601-afa8-51bda90dedd2.jpg'),
(15, 1, 'Corrected AI Result', 'Flagged image #40 and assigned to Healthy/dog.', '2026-09-13 22:44:12', 40, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/21e7c151-431a-4138-8b44-02ecf0c7cc97.jpg'),
(16, 1, 'Corrected AI Result', 'Flagged image #41 and assigned to Healthy/dog.', '2026-09-13 22:44:19', 41, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/134f7901-2615-496b-9208-6d9b0a04b95a.jpg'),
(17, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-14 17:19:24', NULL, NULL, 0, NULL),
(18, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-14 18:31:11', NULL, NULL, 0, NULL),
(19, 1, 'Corrected AI Result', 'Flagged image #47 and assigned to Healthy/dog.', '2026-09-14 20:44:37', 47, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/805751534_2344101779669255_4353199906505687313_n.jpg'),
(20, 1, 'Corrected AI Result', 'Flagged image #38 and assigned to Healthy/dog.', '2026-09-14 20:45:00', 38, 'image_classifications', 1, 'C:\\xampp\\htdocs\\pet_system_real\\admin/../dataset/Healthy/dog/bf31cbd3-5fd3-4cf1-872a-3008c246bd5b.jpg'),
(21, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-14 22:35:35', NULL, NULL, 0, NULL),
(22, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-15 12:25:19', NULL, NULL, 0, NULL),
(23, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-15 13:30:55', NULL, NULL, 0, NULL),
(24, 1, 'Started AI Server', 'Launched Python Flask backend.', '2026-09-17 13:00:42', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','vet_reviewer') NOT NULL DEFAULT 'vet_reviewer',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password_hash`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'Dr. Maria Santos', 'admin@petcare.local', '$2b$10$1SOwbGPfE0eot5Mt2yE7juLtph2xz402rq6203TpyG36FF.xoKFcK', 'admin', 1, '2026-09-17 12:53:44', '2026-09-12 10:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `conditions`
--

CREATE TABLE `conditions` (
  `id` int(11) NOT NULL,
  `condition_name` varchar(150) NOT NULL,
  `species` enum('dog','cat','both','other') NOT NULL DEFAULT 'both',
  `summary` text NOT NULL,
  `symptoms` text NOT NULL,
  `recommended_action` text NOT NULL,
  `severity` enum('low','medium','high','emergency') NOT NULL DEFAULT 'medium',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `source_staging_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conditions`
--

INSERT INTO `conditions` (`id`, `condition_name`, `species`, `summary`, `symptoms`, `recommended_action`, `severity`, `is_published`, `source_staging_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(6, 'Cancer', 'cat', 'A class of diseases in which cells grow uncontrollably, invade surrounding tissue, and may spread to other areas of the body.', 'lumps, swelling, persistent sores, skin infections, abnormal discharge, bad breath, listlessness, lethargy, behavior change, weight loss, sudden lameness, diarrhea, vomiting, scaly red skin patches, loss of appetite, difficulty breathing, difficulty urinating, difficulty defecating', 'Contact a veterinarian immediately if symptoms appear to determine diagnostic and treatment options such as surgery, chemotherapy, radiation, or palliative care.', 'high', 1, 175, '2026-09-13 01:30:10', '2026-09-13 14:54:21', NULL),
(7, 'Diabetes', 'cat', 'A complex disease caused by either a lack of insulin production or an inadequate bodily response to insulin, leading to elevated blood sugar levels.', 'change in appetite, weight loss, excessive thirst, increased urination, urinating outside litter box, sweet-smelling breath, lethargy, dehydration, unkempt hair coat, urinary tract infection', 'Make an appointment to see a veterinarian immediately for blood work, urinalysis, and to establish appropriate insulin therapy or dietary management.', 'medium', 1, 176, '2026-09-13 01:32:18', '2026-09-13 01:32:18', NULL),
(9, 'Brucellosis', 'dog', 'A bacterial disease in dogs caused by Brucella canis, also known as contagious abortion, which can be transmitted to humans.', 'abortion during last trimester, infertility, testicular abnormalities, diskospondylitis, inflammation of eyes, inflammation of brain, poor semen quality', 'Practice good personal hygiene, use sanitization methods, wear PPE around pregnant dogs or birth products, promptly dispose of birth products, disinfect contaminated surfaces, test dogs before breeding, and neuter/euthanize/do not breed infected dogs.', 'medium', 1, 184, '2026-09-14 18:28:57', '2026-09-14 18:28:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cost_dataset_uploads`
--

CREATE TABLE `cost_dataset_uploads` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `row_count` int(11) NOT NULL DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_estimates_log`
--

CREATE TABLE `cost_estimates_log` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `classification_id` int(11) DEFAULT NULL,
  `species` varchar(50) NOT NULL,
  `condition_id` int(11) DEFAULT NULL,
  `disease_name` varchar(100) NOT NULL,
  `severity_level` varchar(50) NOT NULL,
  `pet_weight_kg` decimal(6,2) NOT NULL,
  `treatment_duration_days` int(11) NOT NULL,
  `clinic_tier` varchar(50) NOT NULL,
  `estimated_cost` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_estimates_log`
--

INSERT INTO `cost_estimates_log` (`id`, `session_id`, `classification_id`, `species`, `condition_id`, `disease_name`, `severity_level`, `pet_weight_kg`, `treatment_duration_days`, `clinic_tier`, `estimated_cost`, `created_at`) VALUES
(1, NULL, NULL, '', NULL, 'Dermatitis', '', 0.00, 0, '', 500.48, '2026-09-14 22:36:16'),
(2, NULL, NULL, '', NULL, 'Dermatitis', '', 0.00, 0, '', 500.48, '2026-09-14 23:20:10'),
(3, NULL, NULL, '', NULL, 'Fungal_infections', '', 0.00, 0, '', 653.25, '2026-09-15 13:32:50'),
(4, NULL, NULL, '', NULL, 'Fungal_infections', '', 0.00, 0, '', 653.25, '2026-09-15 13:38:45'),
(5, NULL, NULL, '', NULL, 'ringworm', '', 0.00, 0, '', 650.57, '2026-09-15 13:41:42'),
(6, NULL, NULL, '', NULL, 'ringworm', '', 0.00, 0, '', 650.57, '2026-09-15 13:43:55'),
(7, NULL, NULL, '', NULL, 'demodicosis', '', 0.00, 0, '', 849.15, '2026-09-15 13:45:31'),
(8, NULL, NULL, '', NULL, 'ringworm', '', 0.00, 0, '', 650.57, '2026-09-15 13:57:37'),
(9, NULL, NULL, '', NULL, 'ringworm', '', 0.00, 0, '', 650.57, '2026-09-15 14:42:12'),
(10, NULL, 60, 'dog', NULL, 'ringworm', 'Mild', 5.00, 7, 'Economy', 650.57, '2026-09-17 13:02:24');

-- --------------------------------------------------------

--
-- Table structure for table `dataset_images`
--

CREATE TABLE `dataset_images` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `proposed_category` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected','trash') DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dataset_images`
--

INSERT INTO `dataset_images` (`id`, `image_path`, `proposed_category`, `status`, `uploaded_by`, `deleted_at`, `created_at`) VALUES
(10, 'staging_6aa686feb55ad.jpg', 'Healthy/cat', 'pending', 1, NULL, '2026-09-13 11:20:30'),
(11, 'staging_6aa6871040e17.jpg', 'Diseased/cat/FLEA_ALLERGY', 'pending', 1, NULL, '2026-09-13 11:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `image_classifications`
--

CREATE TABLE `image_classifications` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `image_path` varchar(500) NOT NULL,
  `predicted_label` varchar(100) NOT NULL,
  `confidence_score` decimal(5,2) NOT NULL,
  `is_flagged_false_positive` tinyint(1) NOT NULL DEFAULT 0,
  `is_curated_for_retraining` tinyint(1) NOT NULL DEFAULT 0,
  `admin_verified_label` enum('healthy','diseased','uncertain') DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `corrected_label` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `image_classifications`
--

INSERT INTO `image_classifications` (`id`, `session_id`, `image_path`, `predicted_label`, `confidence_score`, `is_flagged_false_positive`, `is_curated_for_retraining`, `admin_verified_label`, `reviewed_by`, `created_at`, `corrected_label`) VALUES
(7, NULL, '/static/uploads\\heatmap_dog-11328.png', 'Healthy', 99.69, 0, 0, NULL, NULL, '2026-09-13 18:16:45', NULL),
(8, NULL, '/static/uploads\\heatmap_cami_canine-atopic-dermatitis-with-alopecia_fpc_1_720-900_40.jpg', 'Dermatitis', 37.84, 0, 0, NULL, NULL, '2026-09-13 18:17:03', NULL),
(9, NULL, '/static/uploads\\heatmap_1-6-_png_jpg.rf.5b1b0274c639d0ef954ca0ca6e5975c4.jpg', 'demodicosis', 94.02, 0, 0, NULL, NULL, '2026-09-13 18:18:05', NULL),
(10, NULL, '/static/uploads\\heatmap_1-28-_png_jpg.rf.554549a73b8161adea0e6e77ab4c8148.jpg', 'demodicosis', 81.05, 0, 0, NULL, NULL, '2026-09-13 18:18:20', NULL),
(11, NULL, '/static/uploads\\heatmap_1673_jpg.rf.87ca2be8fc106bc16edfa0cfc8814c02.jpg', 'Hypersensitivity', 99.62, 0, 0, NULL, NULL, '2026-09-13 18:18:37', NULL),
(12, NULL, '/static/uploads\\heatmap_B9781416056638000070_f007-013-9781416056638_jpg.rf.c9b1cd4136b9baee003e8c54cce0985e.jpg', 'Hypersensitivity', 94.80, 0, 0, NULL, NULL, '2026-09-13 18:18:53', NULL),
(13, NULL, '/static/uploads\\heatmap_1000010673_x16_jpg.rf.fa6dbd65df4998a3ae78df33e6de29f0.jpg', 'Dermatitis', 98.49, 0, 0, NULL, NULL, '2026-09-13 18:19:33', NULL),
(15, NULL, '/static/uploads\\heatmap_436685073_799772981646313_4606726835859676318_n_jpg.rf.939eecbd5ab17556cca103dc01a3214e.jpg', 'ringworm', 99.94, 0, 0, NULL, NULL, '2026-09-13 18:52:25', NULL),
(16, NULL, '/static/uploads\\heatmap_1e161cf3433aee7231b8b56333127e5c_jpg.rf.fda9a98f5c804ccf3d8b0df096609c52.jpg', 'FLEA_ALLERGY', 65.21, 0, 0, NULL, NULL, '2026-09-13 18:57:09', NULL),
(17, NULL, '/static/uploads\\heatmap_4b2234a47e698f2f38dfef71285ecf3a_jpg.rf.f211c69fcf3ce92592560c6d2cf34b60.jpg', 'RINGWORM', 76.38, 0, 0, NULL, NULL, '2026-09-13 18:58:01', NULL),
(18, NULL, '/static/uploads\\heatmap_78a8cfa08a4086616ef910ccd8614c94_jpg.rf.250cadd2dcfbe2013c5c165ac27a20ef.jpg', 'FLEA_ALLERGY', 61.82, 0, 0, NULL, NULL, '2026-09-13 18:58:21', NULL),
(19, NULL, '/static/uploads\\heatmap_4bc679ccb5f1bb621167110f74ecc7cc_jpg.rf.40ec018913aa7cafec006033f79f0303.jpg', 'RINGWORM', 83.48, 1, 0, NULL, NULL, '2026-09-13 19:01:15', 'Healthy/cat'),
(20, NULL, '/static/uploads\\heatmap_4bc679ccb5f1bb621167110f74ecc7cc_jpg.rf.40ec018913aa7cafec006033f79f0303.jpg', 'RINGWORM', 83.48, 1, 0, NULL, NULL, '2026-09-13 19:04:29', 'Healthy/cat'),
(21, NULL, '/static/uploads\\heatmap_cami_canine-atopic-dermatitis-with-alopecia_fpc_1_720-900_40.jpg', 'Dermatitis', 45.24, 0, 0, NULL, NULL, '2026-09-13 19:11:22', NULL),
(22, NULL, '/static/uploads\\heatmap_dog-11328.png', 'Healthy', 99.87, 0, 0, NULL, NULL, '2026-09-13 19:11:40', NULL),
(23, NULL, '/static/uploads\\heatmap_0f210d552b92c7204442138de5151b55_jpg.rf.c5ea130e7e7b87d5567ba7c3a2eea4bb.jpg', 'RINGWORM', 51.81, 1, 0, NULL, NULL, '2026-09-13 19:14:08', 'Healthy/cat'),
(24, NULL, '/static/uploads\\heatmap_3d1bc6285ea03f59e6aecefce1424aaf_jpg.rf.7531f17c796a6a0606e246728b7044ee.jpg', 'Healthy', 99.99, 0, 0, NULL, NULL, '2026-09-13 22:05:58', NULL),
(25, NULL, '/static/uploads\\heatmap_2fc10b855b8df21d7fd215521728b577_jpg.rf.1fd4358a91cc0b7ae4155360f005b71b.jpg', 'Healthy', 100.00, 0, 0, NULL, NULL, '2026-09-13 22:06:12', NULL),
(26, NULL, '/static/uploads\\heatmap_1-2-_png_jpg.rf.33d914f72c7abaa27fd5d26dcfd2bc3a.jpg', 'DEMODICOSIS', 98.21, 0, 0, NULL, NULL, '2026-09-13 22:06:27', NULL),
(27, NULL, '/static/uploads\\heatmap_002_jpg.rf.19a707d57ea7d67879969bf94ff94e36.jpg', 'Healthy', 99.32, 0, 0, NULL, NULL, '2026-09-13 22:09:47', NULL),
(28, NULL, '/static/uploads\\heatmap_021_jpg.rf.9915cc4874f6a3b692deb9a7990ac4bf.jpg', 'Healthy', 99.99, 0, 0, NULL, NULL, '2026-09-13 22:10:00', NULL),
(29, NULL, '/static/uploads\\heatmap_6D4F94F0-A463-4F97-9E69-32C7C4493054_jpeg_jpg.rf.965cddbbdd38ff0a5158267de439df1c.jpg', 'RINGWORM', 99.91, 0, 0, NULL, NULL, '2026-09-13 22:10:17', NULL),
(30, NULL, '/static/uploads\\heatmap_69869142_2813720881974689_7556977064478244864_n_jpg.rf.cde7f7e84486eaee7eb96551ebe6aca3.jpg', 'RINGWORM', 97.89, 0, 0, NULL, NULL, '2026-09-13 22:10:31', NULL),
(31, NULL, '/static/uploads\\heatmap_436642085_969088174572761_2046691789216935979_n_jpg.rf.0891642313ae59f5dd4a316bc90493ec.jpg', 'RINGWORM', 99.65, 0, 0, NULL, NULL, '2026-09-13 22:10:44', NULL),
(32, NULL, '/static/uploads\\heatmap_DSC_0156.JPG', 'Healthy', 93.29, 0, 0, NULL, NULL, '2026-09-13 22:11:04', NULL),
(33, NULL, '/static/uploads\\heatmap_DSC_0076.JPG', 'Healthy', 97.88, 0, 0, NULL, NULL, '2026-09-13 22:11:28', NULL),
(34, NULL, '/static/uploads\\heatmap_Untitled design_20240714_233544_0000.png', 'Healthy', 53.68, 0, 0, NULL, NULL, '2026-09-13 22:11:50', NULL),
(35, NULL, '/static/uploads\\heatmap_6D4F94F0-A463-4F97-9E69-32C7C4493054_jpeg_jpg.rf.965cddbbdd38ff0a5158267de439df1c.jpg', 'RINGWORM', 99.91, 0, 0, NULL, NULL, '2026-09-13 22:18:13', NULL),
(36, NULL, '/static/uploads\\heatmap_808189895_2290368578365323_729530456916329966_n.jpg', 'Healthy', 99.54, 0, 0, NULL, NULL, '2026-09-13 22:25:33', NULL),
(37, NULL, '/static/uploads\\heatmap_e2dec27e-735e-42e0-a3bf-33bfbe924e84.jpg', 'RINGWORM', 85.78, 1, 0, NULL, NULL, '2026-09-13 22:26:16', 'Healthy/dog'),
(38, NULL, '/static/uploads\\heatmap_bf31cbd3-5fd3-4cf1-872a-3008c246bd5b.jpg', 'RINGWORM', 98.02, 1, 0, NULL, NULL, '2026-09-13 22:26:30', 'Healthy/dog'),
(39, NULL, '/static/uploads\\heatmap_6c847604-d495-4bac-92f9-22860ce1d210.jpg', 'Healthy', 64.73, 0, 0, NULL, NULL, '2026-09-13 22:26:39', NULL),
(40, NULL, '/static/uploads\\heatmap_21e7c151-431a-4138-8b44-02ecf0c7cc97.jpg', 'DERMATITIS', 46.22, 1, 0, NULL, NULL, '2026-09-13 22:26:50', 'Healthy/dog'),
(41, NULL, '/static/uploads\\heatmap_134f7901-2615-496b-9208-6d9b0a04b95a.jpg', 'DERMATITIS', 43.94, 1, 0, NULL, NULL, '2026-09-13 22:27:01', 'Healthy/dog'),
(42, NULL, '/static/uploads\\heatmap_a78aab48-edf7-4601-afa8-51bda90dedd2.jpg', 'DERMATITIS', 89.28, 1, 0, NULL, NULL, '2026-09-13 22:27:12', 'Healthy/dog'),
(43, NULL, '/static/uploads\\heatmap_b29399ad-1267-4487-8fdb-b74c993eede5.jpg', 'RINGWORM', 85.20, 1, 0, NULL, NULL, '2026-09-13 22:27:21', 'Healthy/dog'),
(44, NULL, '/static/uploads\\heatmap_bce1ce18-7fce-44f5-b1ad-75bc8974a7b9.jpg', 'Healthy', 62.89, 0, 0, NULL, NULL, '2026-09-13 22:28:07', NULL),
(45, NULL, '/static/uploads\\heatmap_b2474236-ec42-4f93-8fdf-5c1cbd0d3763.jpg', 'DERMATITIS', 82.38, 1, 0, NULL, NULL, '2026-09-13 22:28:31', 'Healthy/dog'),
(46, NULL, '/static/uploads\\heatmap_805751534_2344101779669255_4353199906505687313_n.jpg', 'HYPERSENSITIVITY', 45.23, 1, 0, NULL, NULL, '2026-09-13 22:36:39', 'Healthy/dog'),
(47, NULL, '/static/uploads/heatmap_805751534_2344101779669255_4353199906505687313_n.jpg', 'HYPERSENSITIVITY', 45.23, 1, 0, NULL, NULL, '2026-09-14 18:32:03', 'Healthy/dog'),
(48, NULL, '/static/uploads/heatmap_808189895_2290368578365323_729530456916329966_n.jpg', 'Healthy', 99.54, 0, 0, NULL, NULL, '2026-09-14 18:32:12', NULL),
(49, NULL, '/static/uploads/heatmap_6D4F94F0-A463-4F97-9E69-32C7C4493054_jpeg_jpg.rf.965cddbbdd38ff0a5158267de439df1c.jpg', 'RINGWORM', 99.91, 0, 0, NULL, NULL, '2026-09-14 18:32:26', NULL),
(50, NULL, '/static/uploads/heatmap_69869142_2813720881974689_7556977064478244864_n_jpg.rf.60b2c610fb2caaff5e04d4092e6abc0c.jpg', 'RINGWORM', 86.19, 0, 0, NULL, NULL, '2026-09-14 18:32:48', NULL),
(51, NULL, '/static/uploads/heatmap_80b5eb_799a7e6d4c1444318ee3e4280_jpg.rf.3048fc30eea1238a1d2538eea98257be.jpg', 'RINGWORM', 74.82, 0, 0, NULL, NULL, '2026-09-14 23:19:35', NULL),
(52, NULL, '/static/uploads/heatmap_69779474_2813720978641346_3471018570912104448_n_jpg.rf.5e24746af07046a0689c06eee604dead.jpg', 'RINGWORM', 99.90, 0, 0, NULL, NULL, '2026-09-15 00:56:01', NULL),
(53, NULL, '/static/uploads/heatmap_try.jpg', 'DERMATITIS', 99.82, 0, 0, NULL, NULL, '2026-09-15 12:26:25', NULL),
(54, NULL, '/static/uploads/heatmap_cami_canine-atopic-dermatitis-with-alopecia_fpc_1_720-900_40.jpg', 'DERMATITIS', 37.85, 0, 0, NULL, NULL, '2026-09-15 13:31:43', NULL),
(55, NULL, '/static/uploads/heatmap_1005_jpg.rf.ba21cc62eec0a09d10bf02320ff42b95.jpg', 'FUNGAL INFECTIONS', 99.71, 0, 0, NULL, NULL, '2026-09-15 13:32:33', NULL),
(56, NULL, '/static/uploads/heatmap_1116_jpg.rf.529e17bb506fa655ae26075968ca6799.jpg', 'FUNGAL INFECTIONS', 91.05, 0, 0, NULL, NULL, '2026-09-15 13:38:36', NULL),
(57, NULL, '/static/uploads/heatmap_6D4F94F0-A463-4F97-9E69-32C7C4493054_jpeg_jpg.rf.7642a53724795c6adc922fadc13ad745.jpg', 'RINGWORM', 99.96, 0, 0, NULL, NULL, '2026-09-15 13:41:38', NULL),
(58, NULL, '/static/uploads/heatmap_436611250_417959844314512_1076972693256378968_n_jpg.rf.fcb7800f2e25caa8427e7ec6a1550984.jpg', 'RINGWORM', 95.41, 0, 0, NULL, NULL, '2026-09-15 14:41:40', NULL),
(59, NULL, '/static/uploads/heatmap_278372566_1115287789314455_1249966033354804477_n_jpg.rf.7b5675d4578636b04486563d882537d3.jpg', 'RINGWORM', 99.38, 0, 0, NULL, NULL, '2026-09-15 14:41:57', NULL),
(60, NULL, '/static/uploads/heatmap_278372566_1115287789314455_1249966033354804477_n_jpg.rf.1113d14f454121030c2b534e5f1ea2fe.jpg', 'RINGWORM', 99.50, 0, 0, NULL, NULL, '2026-09-17 13:02:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staging_queue`
--

CREATE TABLE `staging_queue` (
  `id` int(11) NOT NULL,
  `source_type` enum('url','pdf','manual') NOT NULL DEFAULT 'url',
  `source_reference` varchar(500) NOT NULL,
  `extracted_condition_name` varchar(150) DEFAULT NULL,
  `extracted_summary` text DEFAULT NULL,
  `extracted_symptoms` text DEFAULT NULL,
  `extracted_action` text DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewer_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `extracted_species` enum('dog','cat','both','other') DEFAULT 'both'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staging_queue`
--

INSERT INTO `staging_queue` (`id`, `source_type`, `source_reference`, `extracted_condition_name`, `extracted_summary`, `extracted_symptoms`, `extracted_action`, `confidence_score`, `status`, `submitted_by`, `reviewed_by`, `reviewer_notes`, `created_at`, `reviewed_at`, `extracted_species`) VALUES
(168, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Rabies', 'A fatal viral disease that affects the central nervous system of mammals and can be transmitted to humans.', 'hydrophobia, neurological impairment, fatal neurological signs', 'Vaccinate routinely and seek immediate veterinary and medical emergency care if exposed.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:28:34', NULL, 'both'),
(169, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Canine parvovirus', 'A highly contagious and sometimes fatal gastrointestinal viral infection primarily affecting puppies.', 'severe diarrhea, vomiting, lethargy, anorexia', 'Seek immediate veterinary supportive care and ensure puppies are properly vaccinated.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:28:34', NULL, 'dog'),
(170, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Canine distemper', 'An often fatal viral infectious disease causing systemic, respiratory, and neurological problems.', 'respiratory signs, neurological signs, fever', 'Ensure timely vaccination and consult a veterinarian for supportive care.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:28:34', NULL, 'dog'),
(171, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Lyme disease', 'A bacterial disease caused by Borrelia burgdorferi and spread by Ixodes ticks.', 'acute arthritis, anorexia, lethargy', 'Use tick prevention products and consult a veterinarian for antibiotic therapy if infected.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:28:34', NULL, 'both'),
(172, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Kennel cough', 'An infectious upper respiratory disease common in dogs held in close confinement.', 'persistent cough, nasal discharge', 'Isolate the affected dog and consult a veterinarian for proper medication and care.', NULL, 'approved', 1, 1, NULL, '2026-09-13 01:28:34', '2026-09-13 01:32:34', 'dog'),
(173, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Osteoarthritis', 'A degenerative joint disease involving progressive deterioration of joint cartilage.', 'joint pain, lameness, stiffness', 'Manage with veterinary-prescribed NSAIDs, joint fluid modifiers, weight management, or surgery.', NULL, 'rejected', 1, 1, NULL, '2026-09-13 01:28:34', '2026-09-14 18:29:16', 'both'),
(174, 'url', 'https://en.wikipedia.org/wiki/List_of_dog_diseases', 'Gastric dilatation volvulus', 'A severe, life-threatening condition where the stomach expands with gas and twists on its axis.', 'swollen stomach, abdominal pain, retching', 'Seek immediate emergency veterinary surgical intervention.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:28:34', NULL, 'dog'),
(175, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Cancer', 'A class of diseases in which cells grow uncontrollably, invade surrounding tissue, and may spread to other areas of the body.', 'lumps, swelling, persistent sores, skin infections, abnormal discharge, bad breath, listlessness, lethargy, behavior change, weight loss, sudden lameness, diarrhea, vomiting, scaly red skin patches, loss of appetite, difficulty breathing, difficulty urinating, difficulty defecating', 'Contact a veterinarian immediately if symptoms appear to determine diagnostic and treatment options such as surgery, chemotherapy, radiation, or palliative care.', NULL, 'approved', 1, 1, NULL, '2026-09-13 01:29:39', '2026-09-13 01:30:10', 'cat'),
(176, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Diabetes', 'A complex disease caused by either a lack of insulin production or an inadequate bodily response to insulin, leading to elevated blood sugar levels.', 'change in appetite, weight loss, excessive thirst, increased urination, urinating outside litter box, sweet-smelling breath, lethargy, dehydration, unkempt hair coat, urinary tract infection', 'Make an appointment to see a veterinarian immediately for blood work, urinalysis, and to establish appropriate insulin therapy or dietary management.', NULL, 'approved', 1, 1, NULL, '2026-09-13 01:29:39', '2026-09-13 01:32:18', 'cat'),
(177, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Feline Immunodeficiency Virus (FIV)', 'A slow-acting viral infection that severely weakens a cat\'s immune system, leaving them susceptible to secondary infections.', 'enlarged lymph nodes, fever, anemia, weight loss, disheveled coat, poor appetite, diarrhea, conjunctivitis, gingivitis, stomatitis, dental disease, skin redness, hair loss, non-healing wounds, sneezing, nasal discharge, frequent urination, straining to urinate, urinating outside litter box, behavior change', 'Have the cat examined and tested by a veterinarian immediately, keep them indoors away from other cats, and provide supportive medical care.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(178, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Feline Leukemia Virus (FeLV)', 'A transmittable retrovirus that suppresses the immune system and predisposes cats to infections, anemia, kidney disease, and lymphosarcoma.', 'loss of appetite, weight loss, pale gums, inflamed gums, poor coat condition, abscesses, fever, upper respiratory infections, diarrhea, vomiting, seizures, behavior changes, vision problems, enlarged lymph nodes, reproductive problems, jaundice, chronic skin disease, respiratory distress, lethargy', 'Test new or sick cats, keep cats indoors, discuss FeLV vaccination options with a vet, and provide regular wellness checkups.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(179, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Heartworm', 'A mosquito-borne parasitic infection that primarily causes severe inflammatory responses and lung disease in domestic pets.', 'persistent cough, breathing difficulties, panting, wheezing, rapid breathing, open-mouthed breathing, depression, loss of appetite, weight loss, sporadic vomiting, lethargy, sudden death', 'Screen pets with blood tests before administering vet-prescribed monthly preventative medications, and monitor infected pets with periodic radiographs.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'both'),
(180, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'High-Rise Syndrome', 'Physical injuries resulting from accidental falls from unscreened high-rise windows, terraces, or fire escapes.', 'shattered jaws, punctured lungs, broken limbs, broken pelvises, head injuries', 'Rush the animal immediately to the nearest animal hospital or veterinarian, and install snug, sturdy screens on all open windows.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(181, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Ringworm', 'A highly contagious fungal infection of the skin, hair, and nails that can easily spread to other pets and humans.', 'skin lesions, flaky bald patches, central red rings, localized skin redness, dandruff, hair loss', 'Quarantine the cat immediately, consult a vet for fungal diagnostic testing and medicated treatment (shampoos, ointments, or oral drugs), and thoroughly disinfect the environment.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(182, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Upper Respiratory Infections', 'Contagious viral or bacterial infections affecting the nose, throat, and sinus areas, commonly caused by calicivirus, herpesvirus, Chlamydia, or Bordetella.', 'sneezing, congestion, runny nose, cough, nasal discharge, gagging, drooling, fever, loss of appetite, rapid breathing, nasal ulcers, oral ulcers, squinting, rubbing eyes, open-mouth breathing, depression', 'Bring the cat to a veterinarian for proper diagnosis and prescribed treatment, isolate infected pets, keep vaccines updated, and minimize stress.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(183, 'url', 'https://www.aspca.org/pet-care/cat-care/common-cat-diseases', 'Worms', 'Intestinal parasite infestations, such as roundworms, hookworms, tapeworms, or lungworms, that can compromise overall health.', 'diarrhea, worms visible in stool, worm segments near anus, bloody stool, bloating, potbellied abdomen, weight loss, vomiting, constipation, anemia, coughing, trouble breathing', 'Bring the cat to a veterinarian for stool analysis and prescribed targeted deworming medication, avoid self-diagnosis, and keep cats indoors.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:29:39', NULL, 'cat'),
(184, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Brucellosis', 'A bacterial disease in dogs caused by Brucella canis, also known as contagious abortion, which can be transmitted to humans.', 'abortion during last trimester, infertility, testicular abnormalities, diskospondylitis, inflammation of eyes, inflammation of brain, poor semen quality', 'Practice good personal hygiene, use sanitization methods, wear PPE around pregnant dogs or birth products, promptly dispose of birth products, disinfect contaminated surfaces, test dogs before breeding, and neuter/euthanize/do not breed infected dogs.', NULL, 'approved', 1, 1, NULL, '2026-09-13 01:34:56', '2026-09-14 18:28:57', 'dog'),
(185, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Campylobacteriosis', 'A bacterial infection caused by Campylobacter species that can cause diarrheal disease in animals and humans.', 'diarrhea, bloody diarrhea', 'Wear personal protective equipment including gloves when handling dogs or cats to prevent fecal contamination and oral ingestion.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(186, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Capnocytophaga Infection', 'A bacterial organism carried harmlessly in the mouths of dogs and cats that can cause severe local and systemic infections in humans via bites or scratches.', 'asymptomatic in animals', 'Wear personal protective equipment including gloves when handling dogs or cats, and have all bite wounds evaluated by medical personnel.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(187, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Cat-Scratch Fever (Bartonellosis)', 'A bacterial infection caused by Bartonella henselae, typically asymptomatic or mild in cats but potentially more severe in dogs and humans.', 'fever, vomiting, red eyes, swollen lymph nodes, lethargy, inappetence', 'Eliminate fleas, practice proper cat-handling techniques, wear protective clothing, and thoroughly cleanse wounds.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(188, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Leptospirosis', 'A bacterial infection caused by Leptospira spirochetes affecting kidney and liver function in dogs and potentially linked to chronic kidney disease in cats.', 'fever, tiredness, vomiting, diarrhea, anorexia, conjunctivitis, jaundice, polyuria, oliguria, anuria', 'Maintain good sanitation and waste control, wear appropriate PPE when handling animals, and vaccinate dogs against Leptospira.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(189, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Methicillin-Resistant Staphylococcus aureus (MRSA)', 'An opportunistic, antibiotic-resistant bacterial strain that can colonize dogs and cats and cause secondary skin, wound, or respiratory infections.', 'catheter or surgical site infections, abscesses, dermatitis, pneumonia, urinary tract infections', 'Practice frequent hand washing, wear gloves at all times, cover skin breaks, and follow appropriate disinfection protocols.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(190, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Pasteurellosis', 'A bacterial infection caused by Pasteurella spp. commonly present in the respiratory tract and mouth of dogs and cats that can lead to local or systemic infections.', 'ear infections, nose/sinus infections, eye infections, joint infections, meninges infections, spinal cord infections, fatal pneumonia', 'Wear personal protective equipment including gloves when handling dogs or cats, and have all bite wounds evaluated by medical personnel.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(191, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Ringworm (Dermatophytosis)', 'A fungal skin infection caused by Microsporum species leading to hair, skin, and nail lesions in animals and humans.', 'scaly alopecia, crusty alopecia, erythema, broken hairs, itchy lesions', 'Use appropriate PPE (gloves and protective clothing), ensure environmental sanitation, screen newly acquired animals, isolate, and treat affected animals.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(192, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Cryptosporidiosis', 'A protozoan parasitic infection caused by Cryptosporidium spp. that can cause diarrheal illness.', 'diarrhea', 'Wear personal protective equipment including gloves when handling animals, and wash hands thoroughly with soap and water.', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'both'),
(193, 'pdf', 'Uploaded PDF: dogs_cats.pdf', 'Toxoplasmosis', 'A disease caused by the intracellular protozoan parasite Toxoplasma gondii, with cats acting as the definitive host capable of shedding infective oocysts.', 'vomiting, diarrhea, difficulty breathing, loss of appetite, eye lesions, abdominal pain', 'Dispose of cat litter and feces promptly; wear gloves when handling cat feces or litter (especially pregnant or immunocompromised individuals).', NULL, 'pending', 1, NULL, NULL, '2026-09-13 01:34:56', NULL, 'cat');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_activity_logs_admin` (`admin_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `conditions`
--
ALTER TABLE `conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conditions_source_staging` (`source_staging_id`);
ALTER TABLE `conditions` ADD FULLTEXT KEY `ft_search` (`condition_name`,`symptoms`,`summary`);

--
-- Indexes for table `cost_dataset_uploads`
--
ALTER TABLE `cost_dataset_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `cost_estimates_log`
--
ALTER TABLE `cost_estimates_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`session_id`),
  ADD KEY `fk_cost_condition` (`condition_id`),
  ADD KEY `fk_cost_image_scan` (`classification_id`);

--
-- Indexes for table `dataset_images`
--
ALTER TABLE `dataset_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dataset_images_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `image_classifications`
--
ALTER TABLE `image_classifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`session_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `staging_queue`
--
ALTER TABLE `staging_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `conditions`
--
ALTER TABLE `conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cost_dataset_uploads`
--
ALTER TABLE `cost_dataset_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_estimates_log`
--
ALTER TABLE `cost_estimates_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dataset_images`
--
ALTER TABLE `dataset_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `image_classifications`
--
ALTER TABLE `image_classifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `staging_queue`
--
ALTER TABLE `staging_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `conditions`
--
ALTER TABLE `conditions`
  ADD CONSTRAINT `fk_conditions_source_staging` FOREIGN KEY (`source_staging_id`) REFERENCES `staging_queue` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cost_dataset_uploads`
--
ALTER TABLE `cost_dataset_uploads`
  ADD CONSTRAINT `cost_dataset_uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cost_estimates_log`
--
ALTER TABLE `cost_estimates_log`
  ADD CONSTRAINT `fk_cost_condition` FOREIGN KEY (`condition_id`) REFERENCES `conditions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cost_image_scan` FOREIGN KEY (`classification_id`) REFERENCES `image_classifications` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dataset_images`
--
ALTER TABLE `dataset_images`
  ADD CONSTRAINT `fk_dataset_images_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `image_classifications`
--
ALTER TABLE `image_classifications`
  ADD CONSTRAINT `image_classifications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staging_queue`
--
ALTER TABLE `staging_queue`
  ADD CONSTRAINT `staging_queue_ibfk_1` FOREIGN KEY (`submitted_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staging_queue_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2026 at 02:14 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ncs_appointment_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_appointment_number` (OUT `app_number` VARCHAR(20))   BEGIN
    DECLARE year_part VARCHAR(4);
    DECLARE seq INT;
    
    SET year_part = DATE_FORMAT(CURDATE(), '%Y');
    
    SELECT IFNULL(MAX(CAST(SUBSTRING(appointment_number, -5) AS UNSIGNED)), 0) + 1
    INTO seq
    FROM appointments
    WHERE appointment_number LIKE CONCAT('NCS-', year_part, '-%');
    
    SET app_number = CONCAT('NCS-', year_part, '-', LPAD(seq, 5, '0'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_audit` (IN `p_user_id` INT, IN `p_action` VARCHAR(100), IN `p_entity_type` VARCHAR(50), IN `p_entity_id` INT, IN `p_details` JSON)   BEGIN
    INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
    VALUES (p_user_id, p_action, p_entity_type, p_entity_id, p_details, 
            SUBSTRING_INDEX(SUBSTRING_INDEX(USER(), '@', 1), '.', 1),
            'system');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `officer_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `purpose` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed','no_show') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `has_document` tinyint(1) DEFAULT 0,
  `document_path` varchar(255) DEFAULT NULL,
  `document_uploaded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `rescheduled_from` int(11) DEFAULT NULL,
  `is_rescheduled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_number`, `user_id`, `office_id`, `officer_id`, `appointment_date`, `appointment_time`, `duration_minutes`, `purpose`, `description`, `status`, `rejection_reason`, `has_document`, `document_path`, `document_uploaded_at`, `created_at`, `updated_at`, `reviewed_at`, `reviewed_by`, `rescheduled_from`, `is_rescheduled`) VALUES
(1, 'NCS-2026-00001', 5, 1, NULL, '2026-07-11', '09:00:00', 30, 'Customs Clearance', 'Need to clear imported goods', 'approved', NULL, 0, NULL, NULL, '2026-07-08 23:33:37', '2026-07-09 23:35:24', '2026-07-09 23:35:24', 1, NULL, 0),
(2, 'NCS-2026-00002', 7, 2, NULL, '2026-07-12', '10:30:00', 30, 'Import Documentation', 'Documentation for imported machinery', 'rejected', 'nothign', 0, NULL, NULL, '2026-07-07 23:33:37', '2026-07-10 00:17:49', '2026-07-10 00:17:49', 2, NULL, 0),
(3, 'NCS-2026-00003', 9, 3, NULL, '2026-07-10', '11:00:00', 30, 'Export Declaration', 'Export of agricultural products', 'approved', NULL, 0, NULL, NULL, '2026-07-06 23:33:37', '2026-07-10 00:07:48', '2026-07-10 00:07:48', 2, NULL, 0),
(4, 'NCS-2026-00004', 11, 4, NULL, '2026-07-13', '09:30:00', 30, 'Customs Verification', 'Verification of goods', 'approved', NULL, 0, NULL, NULL, '2026-07-08 23:33:37', '2026-07-10 00:16:53', '2026-07-10 00:16:53', 2, NULL, 0),
(5, 'NCS-2026-00005', 13, 5, NULL, '2026-07-14', '14:00:00', 30, 'Import Duty Payment', 'Payment of import duties', 'approved', NULL, 0, NULL, NULL, '2026-07-05 23:33:37', '2026-07-10 00:07:58', '2026-07-10 00:07:58', 2, NULL, 0),
(6, 'NCS-2026-00006', 16, 6, NULL, '2026-07-11', '15:00:00', 30, 'Customs Clearance', 'Clearing of personal effects', 'approved', NULL, 0, NULL, NULL, '2026-07-08 23:33:37', '2026-07-10 00:10:19', '2026-07-10 00:10:19', 2, NULL, 0),
(7, 'NCS-2026-00007', 18, 1, NULL, '2026-07-15', '10:00:00', 30, 'Export Documentation', 'Export of manufactured goods', 'approved', NULL, 0, NULL, NULL, '2026-07-04 23:33:37', '2026-07-10 00:12:05', '2026-07-10 00:12:05', 2, NULL, 0),
(8, 'NCS-2026-00008', 6, 2, NULL, '2026-07-12', '13:00:00', 30, 'Customs Enquiry', 'General customs enquiry', 'approved', NULL, 0, NULL, NULL, '2026-07-07 23:33:37', '2026-07-10 00:20:56', '2026-07-10 00:20:56', 2, NULL, 0),
(9, 'NCS-2026-00009', 5, 1, NULL, '2026-07-04', '09:00:00', 30, 'Customs Clearance', 'Cleared successfully', 'approved', NULL, 0, NULL, NULL, '2026-06-29 23:33:37', '2026-07-09 23:33:37', '2026-07-05 23:33:37', 2, NULL, 0),
(10, 'NCS-2026-00010', 6, 2, NULL, '2026-06-29', '10:30:00', 30, 'Import Documentation', 'Documentation completed', 'approved', NULL, 0, NULL, NULL, '2026-06-24 23:33:37', '2026-07-09 23:33:37', '2026-06-30 23:33:37', 3, NULL, 0),
(11, 'NCS-2026-00011', 8, 3, NULL, '2026-06-24', '11:00:00', 30, 'Export Declaration', 'Export approved', 'approved', NULL, 0, NULL, NULL, '2026-06-19 23:33:37', '2026-07-09 23:33:37', '2026-06-25 23:33:37', 4, NULL, 0),
(12, 'NCS-2026-00012', 10, 4, NULL, '2026-07-01', '09:30:00', 30, 'Customs Verification', 'Verification completed', 'approved', NULL, 0, NULL, NULL, '2026-06-27 23:33:37', '2026-07-09 23:33:37', '2026-07-02 23:33:37', 2, NULL, 0),
(13, 'NCS-2026-00013', 12, 5, NULL, '2026-06-27', '14:00:00', 30, 'Import Duty Payment', 'Payment confirmed', 'approved', NULL, 0, NULL, NULL, '2026-06-23 23:33:37', '2026-07-09 23:33:37', '2026-06-28 23:33:37', 3, NULL, 0),
(14, 'NCS-2026-00014', 14, 6, NULL, '2026-06-19', '15:00:00', 30, 'Customs Clearance', 'Cleared successfully', 'approved', NULL, 0, NULL, NULL, '2026-06-14 23:33:37', '2026-07-09 23:33:37', '2026-06-20 23:33:37', 4, NULL, 0),
(15, 'NCS-2026-00015', 17, 1, NULL, '2026-07-06', '10:00:00', 30, 'Export Documentation', 'Export processed', 'approved', NULL, 0, NULL, NULL, '2026-07-02 23:33:37', '2026-07-09 23:33:37', '2026-07-07 23:33:37', 2, NULL, 0),
(16, 'NCS-2026-00016', 5, 2, NULL, '2026-06-14', '13:00:00', 30, 'Customs Enquiry', 'Enquiry resolved', 'pending', NULL, 0, NULL, NULL, '2026-06-09 23:33:37', '2026-07-10 00:21:19', '2026-06-15 23:33:37', 3, NULL, 0),
(17, 'NCS-2026-00017', 7, 3, NULL, '2026-06-21', '09:00:00', 30, 'Customs Clearance', 'Cleared', 'approved', NULL, 0, NULL, NULL, '2026-06-17 23:33:37', '2026-07-09 23:33:37', '2026-06-22 23:33:37', 4, NULL, 0),
(18, 'NCS-2026-00018', 9, 4, NULL, '2026-06-17', '10:30:00', 30, 'Import Documentation', 'Documentation verified', 'pending', NULL, 0, NULL, NULL, '2026-06-11 23:33:37', '2026-07-10 00:21:10', '2026-06-18 23:33:37', 2, NULL, 0),
(19, 'NCS-2026-00019', 8, 1, NULL, '2026-07-02', '09:30:00', 30, 'Customs Clearance', 'Incomplete documentation', 'rejected', NULL, 0, NULL, NULL, '2026-07-01 23:33:37', '2026-07-09 23:33:37', '2026-07-03 23:33:37', 2, NULL, 0),
(20, 'NCS-2026-00020', 10, 2, NULL, '2026-06-25', '11:00:00', 30, 'Import Documentation', 'Missing required documents', 'rejected', NULL, 0, NULL, NULL, '2026-06-23 23:33:37', '2026-07-09 23:33:37', '2026-06-26 23:33:37', 3, NULL, 0),
(21, 'NCS-2026-00021', 12, 3, NULL, '2026-06-30', '14:30:00', 30, 'Export Declaration', 'Invalid export license', 'approved', NULL, 0, NULL, NULL, '2026-06-29 23:33:37', '2026-07-10 00:20:56', '2026-07-10 00:20:56', 2, NULL, 0),
(22, 'NCS-2026-00022', 15, 4, NULL, '2026-07-03', '15:30:00', 30, 'Customs Verification', 'Goods not found', 'rejected', NULL, 0, NULL, NULL, '2026-07-02 23:33:37', '2026-07-09 23:33:37', '2026-07-04 23:33:37', 2, NULL, 0),
(23, 'NCS-2026-00023', 17, 5, NULL, '2026-06-28', '09:00:00', 30, 'Import Duty Payment', 'Insufficient funds', 'approved', NULL, 0, NULL, NULL, '2026-06-26 23:33:37', '2026-07-10 00:20:55', '2026-07-10 00:20:55', 2, NULL, 0),
(24, 'NCS-2026-00024', 5, 1, NULL, '2026-06-09', '09:00:00', 30, 'Customs Clearance', 'Completed successfully', 'completed', NULL, 0, NULL, NULL, '2026-06-04 00:00:00', '2026-07-09 23:33:37', '2026-06-09 00:00:00', 2, NULL, 0),
(25, 'NCS-2026-00025', 6, 2, NULL, '2026-06-04', '10:30:00', 30, 'Import Documentation', 'Completed', 'completed', NULL, 0, NULL, NULL, '2026-05-30 00:00:00', '2026-07-09 23:33:37', '2026-06-04 00:00:00', 3, NULL, 0),
(26, 'NCS-2026-00026', 8, 3, NULL, '2026-05-30', '11:00:00', 30, 'Export Declaration', 'Completed', 'completed', NULL, 0, NULL, NULL, '2026-05-25 00:00:00', '2026-07-09 23:33:37', '2026-05-30 00:00:00', 4, NULL, 0),
(27, 'NCS-2026-00027', 10, 4, NULL, '2026-05-25', '09:30:00', 30, 'Customs Verification', 'Completed', 'completed', NULL, 0, NULL, NULL, '2026-05-20 00:00:00', '2026-07-09 23:33:37', '2026-05-25 00:00:00', 2, NULL, 0),
(28, 'NCS-2026-00028', 12, 5, NULL, '2026-05-20', '14:00:00', 30, 'Import Duty Payment', 'Completed', 'completed', NULL, 0, NULL, NULL, '2026-05-15 00:00:00', '2026-07-09 23:33:37', '2026-05-20 00:00:00', 3, NULL, 0),
(29, 'NCS-2026-00029', 11, 1, NULL, '2026-07-16', '09:00:00', 30, 'Customs Clearance', 'Cancelled by user', 'cancelled', NULL, 0, NULL, NULL, '2026-07-06 23:33:37', '2026-07-09 23:33:37', NULL, NULL, NULL, 0),
(31, 'NCS-2026-00031', 16, 3, NULL, '2026-07-18', '11:00:00', 30, 'Export Declaration', 'Cancelled by user', 'cancelled', NULL, 0, NULL, NULL, '2026-07-08 23:33:37', '2026-07-09 23:33:37', NULL, NULL, NULL, 0),
(32, 'NCS-2026-00032', 14, 4, NULL, '2026-07-05', '09:00:00', 30, 'Customs Clearance', 'Did not show up', 'no_show', NULL, 0, NULL, NULL, '2026-07-03 23:33:37', '2026-07-09 23:33:37', '2026-07-06 23:33:37', 2, NULL, 0),
(33, 'NCS-2026-00033', 18, 5, NULL, '2026-07-07', '10:30:00', 30, 'Import Documentation', 'Did not show up', 'no_show', NULL, 0, NULL, NULL, '2026-07-04 23:33:37', '2026-07-09 23:33:37', '2026-07-08 23:33:37', 3, NULL, 0),
(34, 'NCS-2026-00034', 6, 2, NULL, '2026-07-16', '09:30:00', 30, 'Import Documentation', '', 'cancelled', NULL, 0, NULL, NULL, '2026-07-10 00:39:58', '2026-07-10 00:48:53', NULL, NULL, NULL, 0),
(35, 'NCS-2026-00035', 6, 4, NULL, '2026-07-16', '09:30:00', 30, 'Import Documentation', '', 'pending', NULL, 1, 'uploads/documents/6a503569a97ab_1783641449.png', '2026-07-10 00:57:31', '2026-07-10 00:53:25', '2026-07-10 00:57:31', NULL, NULL, NULL, 0);

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `trg_appointment_audit_insert` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    CALL log_audit(
        NEW.user_id,
        'appointment_created',
        'appointment',
        NEW.id,
        JSON_OBJECT('appointment_number', NEW.appointment_number, 'date', NEW.appointment_date, 'time', NEW.appointment_time)
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_appointment_audit_update` AFTER UPDATE ON `appointments` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        CALL log_audit(
            NEW.reviewed_by,
            CONCAT('appointment_', NEW.status),
            'appointment',
            NEW.id,
            JSON_OBJECT('old_status', OLD.status, 'new_status', NEW.status, 'appointment_number', NEW.appointment_number)
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_appointment_number` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    IF NEW.appointment_number IS NULL OR NEW.appointment_number = '' THEN
        CALL generate_appointment_number(NEW.appointment_number);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_history`
--

CREATE TABLE `appointment_history` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `ip_address`, `user_agent`, `details`, `created_at`) VALUES
(1, 5, 'appointment_created', 'appointment', 1, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00001\", \"date\": \"2026-07-11\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(2, 7, 'appointment_created', 'appointment', 2, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00002\", \"date\": \"2026-07-12\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(3, 9, 'appointment_created', 'appointment', 3, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00003\", \"date\": \"2026-07-10\", \"time\": \"11:00:00\"}', '2026-07-09 23:33:37'),
(4, 11, 'appointment_created', 'appointment', 4, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00004\", \"date\": \"2026-07-13\", \"time\": \"09:30:00\"}', '2026-07-09 23:33:37'),
(5, 13, 'appointment_created', 'appointment', 5, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00005\", \"date\": \"2026-07-14\", \"time\": \"14:00:00\"}', '2026-07-09 23:33:37'),
(6, 16, 'appointment_created', 'appointment', 6, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00006\", \"date\": \"2026-07-11\", \"time\": \"15:00:00\"}', '2026-07-09 23:33:37'),
(7, 18, 'appointment_created', 'appointment', 7, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00007\", \"date\": \"2026-07-15\", \"time\": \"10:00:00\"}', '2026-07-09 23:33:37'),
(8, 6, 'appointment_created', 'appointment', 8, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00008\", \"date\": \"2026-07-12\", \"time\": \"13:00:00\"}', '2026-07-09 23:33:37'),
(9, 5, 'appointment_created', 'appointment', 9, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00009\", \"date\": \"2026-07-04\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(10, 6, 'appointment_created', 'appointment', 10, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00010\", \"date\": \"2026-06-29\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(11, 8, 'appointment_created', 'appointment', 11, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00011\", \"date\": \"2026-06-24\", \"time\": \"11:00:00\"}', '2026-07-09 23:33:37'),
(12, 10, 'appointment_created', 'appointment', 12, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00012\", \"date\": \"2026-07-01\", \"time\": \"09:30:00\"}', '2026-07-09 23:33:37'),
(13, 12, 'appointment_created', 'appointment', 13, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00013\", \"date\": \"2026-06-27\", \"time\": \"14:00:00\"}', '2026-07-09 23:33:37'),
(14, 14, 'appointment_created', 'appointment', 14, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00014\", \"date\": \"2026-06-19\", \"time\": \"15:00:00\"}', '2026-07-09 23:33:37'),
(15, 17, 'appointment_created', 'appointment', 15, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00015\", \"date\": \"2026-07-06\", \"time\": \"10:00:00\"}', '2026-07-09 23:33:37'),
(16, 5, 'appointment_created', 'appointment', 16, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00016\", \"date\": \"2026-06-14\", \"time\": \"13:00:00\"}', '2026-07-09 23:33:37'),
(17, 7, 'appointment_created', 'appointment', 17, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00017\", \"date\": \"2026-06-21\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(18, 9, 'appointment_created', 'appointment', 18, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00018\", \"date\": \"2026-06-17\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(19, 8, 'appointment_created', 'appointment', 19, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00019\", \"date\": \"2026-07-02\", \"time\": \"09:30:00\"}', '2026-07-09 23:33:37'),
(20, 10, 'appointment_created', 'appointment', 20, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00020\", \"date\": \"2026-06-25\", \"time\": \"11:00:00\"}', '2026-07-09 23:33:37'),
(21, 12, 'appointment_created', 'appointment', 21, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00021\", \"date\": \"2026-06-30\", \"time\": \"14:30:00\"}', '2026-07-09 23:33:37'),
(22, 15, 'appointment_created', 'appointment', 22, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00022\", \"date\": \"2026-07-03\", \"time\": \"15:30:00\"}', '2026-07-09 23:33:37'),
(23, 17, 'appointment_created', 'appointment', 23, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00023\", \"date\": \"2026-06-28\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(24, 5, 'appointment_created', 'appointment', 24, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00024\", \"date\": \"2026-06-09\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(25, 6, 'appointment_created', 'appointment', 25, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00025\", \"date\": \"2026-06-04\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(26, 8, 'appointment_created', 'appointment', 26, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00026\", \"date\": \"2026-05-30\", \"time\": \"11:00:00\"}', '2026-07-09 23:33:37'),
(27, 10, 'appointment_created', 'appointment', 27, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00027\", \"date\": \"2026-05-25\", \"time\": \"09:30:00\"}', '2026-07-09 23:33:37'),
(28, 12, 'appointment_created', 'appointment', 28, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00028\", \"date\": \"2026-05-20\", \"time\": \"14:00:00\"}', '2026-07-09 23:33:37'),
(29, 11, 'appointment_created', 'appointment', 29, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00029\", \"date\": \"2026-07-16\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(30, 13, 'appointment_created', 'appointment', 30, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00030\", \"date\": \"2026-07-17\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(31, 16, 'appointment_created', 'appointment', 31, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00031\", \"date\": \"2026-07-18\", \"time\": \"11:00:00\"}', '2026-07-09 23:33:37'),
(32, 14, 'appointment_created', 'appointment', 32, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00032\", \"date\": \"2026-07-05\", \"time\": \"09:00:00\"}', '2026-07-09 23:33:37'),
(33, 18, 'appointment_created', 'appointment', 33, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00033\", \"date\": \"2026-07-07\", \"time\": \"10:30:00\"}', '2026-07-09 23:33:37'),
(34, 1, 'admin_delete_appointment', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":30,\"appointment_number\":\"NCS-2026-00030\",\"by_user\":1}', '2026-07-09 23:35:07'),
(35, 1, 'appointment_approved', 'appointment', 1, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00001\"}', '2026-07-09 23:35:24'),
(36, 1, 'admin_approve_appointment', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":1,\"appointment_number\":\"NCS-2026-00001\",\"by_user\":1}', '2026-07-09 23:35:25'),
(37, 1, 'profile_update', 'user', NULL, '::1', NULL, '{\"updated_fields\":[\"first_name\",\"last_name\",\"email\",\"phone\"],\"by_user\":1}', '2026-07-09 23:41:52'),
(38, 1, 'logout_all', 'user', NULL, '::1', NULL, '{\"action\":\"logout_all_devices\",\"by_user\":1}', '2026-07-09 23:42:35'),
(39, 1, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"admin@customs.gov.ng\",\"role\":\"admin\",\"timestamp\":\"2026-07-09 23:42:41\"}', '2026-07-09 23:42:41'),
(40, 1, 'settings_updated', 'system', NULL, '::1', NULL, '{\"updated_settings\":[\"site_name\",\"timezone\",\"appointment_max_per_day\",\"appointment_advance_days\",\"appointment_cancel_hours\",\"auto_approve_appointments\",\"enable_notifications\",\"enable_appointment_reminders\",\"reminder_days_before\",\"max_login_attempts\",\"lockout_duration\",\"session_timeout\",\"maintenance_mode\",\"debug_mode\",\"enable_document_upload\",\"max_upload_size\"],\"by_user\":1}', '2026-07-09 23:57:35'),
(41, 1, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"admin@customs.gov.ng\",\"role\":\"admin\",\"timestamp\":\"2026-07-10 00:02:00\"}', '2026-07-10 00:02:00'),
(42, 2, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"neph@gmail.com\",\"role\":\"officer\",\"timestamp\":\"2026-07-10 00:04:52\"}', '2026-07-10 00:04:52'),
(43, 2, 'appointment_approved', 'appointment', 3, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00003\"}', '2026-07-10 00:07:48'),
(44, 2, 'admin_approve_appointment', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":3,\"appointment_number\":\"NCS-2026-00003\",\"by_user\":2}', '2026-07-10 00:07:48'),
(45, 2, 'appointment_approved', 'appointment', 5, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00005\"}', '2026-07-10 00:07:58'),
(46, 2, 'admin_approve_appointment', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":5,\"appointment_number\":\"NCS-2026-00005\",\"by_user\":2}', '2026-07-10 00:07:58'),
(47, 2, 'appointment_approved', 'appointment', 6, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00006\"}', '2026-07-10 00:10:19'),
(48, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":6,\"appointment_number\":\"NCS-2026-00006\",\"by_user\":2}', '2026-07-10 00:10:19'),
(49, 2, 'appointment_approved', 'appointment', 7, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00007\"}', '2026-07-10 00:12:05'),
(50, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":7,\"appointment_number\":\"NCS-2026-00007\",\"by_user\":2}', '2026-07-10 00:12:05'),
(51, 2, 'appointment_approved', 'appointment', 4, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00004\"}', '2026-07-10 00:16:53'),
(52, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":4,\"appointment_number\":\"NCS-2026-00004\",\"by_user\":2}', '2026-07-10 00:16:53'),
(53, 2, 'appointment_rejected', 'appointment', 2, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"rejected\", \"appointment_number\": \"NCS-2026-00002\"}', '2026-07-10 00:17:49'),
(54, 2, 'appointment_rejected', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":2,\"appointment_number\":\"NCS-2026-00002\",\"reason\":\"nothign\",\"by_user\":2}', '2026-07-10 00:17:50'),
(55, 3, 'appointment_pending', 'appointment', 23, 'root', 'system', '{\"old_status\": \"rejected\", \"new_status\": \"pending\", \"appointment_number\": \"NCS-2026-00023\"}', '2026-07-10 00:18:53'),
(56, 4, 'appointment_pending', 'appointment', 21, 'root', 'system', '{\"old_status\": \"rejected\", \"new_status\": \"pending\", \"appointment_number\": \"NCS-2026-00021\"}', '2026-07-10 00:19:00'),
(57, 2, 'appointment_approved', 'appointment', 23, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00023\"}', '2026-07-10 00:20:55'),
(58, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":23,\"appointment_number\":\"NCS-2026-00023\",\"by_user\":2}', '2026-07-10 00:20:55'),
(59, 2, 'appointment_approved', 'appointment', 8, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00008\"}', '2026-07-10 00:20:56'),
(60, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":8,\"appointment_number\":\"NCS-2026-00008\",\"by_user\":2}', '2026-07-10 00:20:56'),
(61, 2, 'appointment_approved', 'appointment', 21, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"approved\", \"appointment_number\": \"NCS-2026-00021\"}', '2026-07-10 00:20:56'),
(62, 2, 'appointment_approved', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":21,\"appointment_number\":\"NCS-2026-00021\",\"by_user\":2}', '2026-07-10 00:20:56'),
(63, 2, 'appointment_pending', 'appointment', 18, 'root', 'system', '{\"old_status\": \"approved\", \"new_status\": \"pending\", \"appointment_number\": \"NCS-2026-00018\"}', '2026-07-10 00:21:10'),
(64, 3, 'appointment_pending', 'appointment', 16, 'root', 'system', '{\"old_status\": \"approved\", \"new_status\": \"pending\", \"appointment_number\": \"NCS-2026-00016\"}', '2026-07-10 00:21:19'),
(65, 2, 'profile_update', 'user', NULL, '::1', NULL, '{\"updated_fields\":[\"first_name\",\"last_name\",\"email\",\"phone\"],\"by_user\":2}', '2026-07-10 00:27:42'),
(66, 6, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"jcee@gmail.com\",\"role\":\"applicant\",\"timestamp\":\"2026-07-10 00:30:20\"}', '2026-07-10 00:30:20'),
(67, 6, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"john@gmail.com\",\"role\":\"applicant\",\"timestamp\":\"2026-07-10 00:33:03\"}', '2026-07-10 00:33:03'),
(68, 6, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"john@gmail.com\",\"role\":\"applicant\",\"timestamp\":\"2026-07-10 00:33:25\"}', '2026-07-10 00:33:25'),
(69, 6, 'appointment_created', 'appointment', 34, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00034\", \"date\": \"2026-07-16\", \"time\": \"09:30:00\"}', '2026-07-10 00:39:58'),
(70, 6, 'appointment_booked', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":34,\"office_id\":2,\"date\":\"2026-07-16\",\"time\":\"09:30:00\",\"by_user\":6}', '2026-07-10 00:39:59'),
(71, 6, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"john@gmail.com\",\"role\":\"applicant\",\"timestamp\":\"2026-07-10 00:44:03\"}', '2026-07-10 00:44:03'),
(72, NULL, 'appointment_cancelled', 'appointment', 34, 'root', 'system', '{\"old_status\": \"pending\", \"new_status\": \"cancelled\", \"appointment_number\": \"NCS-2026-00034\"}', '2026-07-10 00:48:53'),
(73, 6, 'appointment_cancelled', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":34,\"appointment_number\":\"NCS-2026-00034\",\"by_user\":6}', '2026-07-10 00:48:53'),
(74, 6, 'appointment_created', 'appointment', 35, 'root', 'system', '{\"appointment_number\": \"NCS-2026-00035\", \"date\": \"2026-07-16\", \"time\": \"09:30:00\"}', '2026-07-10 00:53:25'),
(75, 6, 'appointment_booked', 'appointment', NULL, '::1', NULL, '{\"appointment_id\":35,\"office_id\":4,\"date\":\"2026-07-16\",\"time\":\"09:30:00\",\"by_user\":6}', '2026-07-10 00:53:26'),
(76, 6, 'document_uploaded', 'document', NULL, '::1', NULL, '{\"document_id\":1,\"document_type\":\"passport_photo\",\"file_name\":\"Frame 41.png\",\"by_user\":6}', '2026-07-10 00:57:31'),
(77, 6, 'profile_update', 'user', NULL, '::1', NULL, '{\"updated_fields\":[\"first_name\",\"last_name\",\"email\",\"phone\"],\"by_user\":6}', '2026-07-10 01:00:19'),
(78, 6, 'profile_update', 'user', NULL, '::1', NULL, '{\"updated_fields\":[\"first_name\",\"last_name\",\"email\",\"phone\",\"date_of_birth\",\"gender\",\"address\",\"city\",\"state\",\"nin\"],\"by_user\":6}', '2026-07-10 01:01:50'),
(79, 2, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"neph@gmail.com\",\"role\":\"officer\",\"timestamp\":\"2026-07-10 01:03:05\"}', '2026-07-10 01:03:05'),
(80, 1, 'login', 'user', NULL, '::1', NULL, '{\"email\":\"admin@customs.gov.ng\",\"role\":\"admin\",\"timestamp\":\"2026-07-10 01:08:34\"}', '2026-07-10 01:08:34');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `document_type` enum('identification','passport_photo','nin_slip','proof_of_address','letter_of_introduction','customs_clearance','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `appointment_id`, `document_type`, `file_name`, `file_path`, `file_size`, `mime_type`, `is_verified`, `verified_by`, `verified_at`, `verification_notes`, `uploaded_at`) VALUES
(1, 6, 35, 'passport_photo', 'Frame 41.png', 'uploads/documents/6a503569a97ab_1783641449.png', 79621, 'image/png', 0, NULL, NULL, NULL, '2026-07-10 00:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `is_national` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `office_id`, `holiday_date`, `holiday_name`, `is_national`, `description`, `created_at`, `updated_at`) VALUES
(1, NULL, '2026-01-01', 'New Year\'s Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(2, NULL, '2026-01-02', 'New Year\'s Day (Observed)', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(3, NULL, '2026-04-02', 'Good Friday', 1, 'Christian Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(4, NULL, '2026-04-05', 'Easter Monday', 1, 'Christian Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(5, NULL, '2026-05-01', 'Workers\' Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(6, NULL, '2026-05-29', 'Democracy Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(7, NULL, '2026-10-01', 'Independence Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(8, NULL, '2026-12-25', 'Christmas Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(9, NULL, '2026-12-26', 'Boxing Day', 1, 'National Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(10, 2, '2026-06-12', 'Lagos State Holiday', 0, 'State Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(11, 4, '2026-06-12', 'Rivers State Holiday', 0, 'State Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38'),
(12, 3, '2026-06-12', 'Kano State Holiday', 0, 'State Public Holiday', '2026-07-09 23:33:38', '2026-07-09 23:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('email','sms','in_app') DEFAULT 'in_app',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `is_sent` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `entity_type`, `entity_id`, `is_read`, `read_at`, `is_sent`, `sent_at`, `created_at`) VALUES
(1, 5, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00001 has been approved.', 'appointment', 1, 1, NULL, 0, NULL, '2026-07-05 23:33:38'),
(2, 6, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00002 has been approved.', 'appointment', 2, 1, NULL, 0, NULL, '2026-06-30 23:33:38'),
(3, 8, 'in_app', 'Appointment Rejected', 'Your appointment #NCS-2026-00011 has been rejected. Reason: Incomplete documentation.', 'appointment', 11, 0, NULL, 0, NULL, '2026-07-03 23:33:38'),
(4, 5, 'in_app', 'Appointment Reminder', 'Reminder: Your appointment at NCS HQ is tomorrow at 9:00 AM.', 'appointment', 1, 0, NULL, 0, NULL, '2026-07-08 23:33:38'),
(5, 7, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00003 has been approved.', 'appointment', 3, 1, NULL, 0, NULL, '2026-06-25 23:33:38'),
(6, 10, 'in_app', 'Appointment Confirmed', 'Your appointment has been confirmed. Please arrive 15 minutes early.', 'appointment', 4, 0, NULL, 0, NULL, '2026-07-02 23:33:38'),
(7, 12, 'in_app', 'Appointment Rejected', 'Your appointment #NCS-2026-00012 has been rejected.', 'appointment', 12, 0, NULL, 0, NULL, '2026-07-01 23:33:38'),
(8, 14, 'in_app', 'No Show Recorded', 'You were marked as no-show for your appointment. Please reschedule.', 'appointment', 19, 0, NULL, 0, NULL, '2026-07-06 23:33:38'),
(9, 18, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00007 has been approved.', 'appointment', 7, 0, NULL, 0, NULL, '2026-07-10 00:12:05'),
(10, 11, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00004 has been approved.', 'appointment', 4, 0, NULL, 0, NULL, '2026-07-10 00:16:53'),
(11, 7, 'in_app', 'Appointment Rejected', 'Your appointment #NCS-2026-00002 has been rejected. Reason: nothign', 'appointment', 2, 0, NULL, 0, NULL, '2026-07-10 00:17:50'),
(12, 17, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00023 has been approved.', 'appointment', 23, 0, NULL, 0, NULL, '2026-07-10 00:20:55'),
(13, 6, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00008 has been approved.', 'appointment', 8, 0, NULL, 0, NULL, '2026-07-10 00:20:56'),
(14, 12, 'in_app', 'Appointment Approved', 'Your appointment #NCS-2026-00021 has been approved.', 'appointment', 21, 0, NULL, 0, NULL, '2026-07-10 00:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `office_code` varchar(20) NOT NULL,
  `office_name` varchar(100) NOT NULL,
  `office_type` enum('headquarters','zonal','area_command','port','border') NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `opening_time` time DEFAULT '08:00:00',
  `closing_time` time DEFAULT '17:00:00',
  `lunch_start` time DEFAULT '12:00:00',
  `lunch_end` time DEFAULT '13:00:00',
  `max_appointments_per_day` int(11) DEFAULT 50,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `office_code`, `office_name`, `office_type`, `address`, `city`, `state`, `latitude`, `longitude`, `phone`, `email`, `opening_time`, `closing_time`, `lunch_start`, `lunch_end`, `max_appointments_per_day`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'NCS-HQ', 'NCS Headquarters', 'headquarters', 'Customs House, Abuja', 'Abuja', 'FCT', NULL, NULL, '08011111111', 'hq@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 60, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(2, 'LAG-PORT', 'Lagos Port Command', 'port', 'Apapa Port, Lagos', 'Lagos', 'Lagos', NULL, NULL, '08022222222', 'lagos.port@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 50, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(3, 'KANO-AREA', 'Kano Area Command', 'area_command', 'Kano City Centre', 'Kano', 'Kano', NULL, NULL, '08033333333', 'kano@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 40, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(4, 'PH-AREA', 'Port Harcourt Area Command', 'area_command', 'Port Harcourt Marine Base', 'Port Harcourt', 'Rivers', NULL, NULL, '08044444444', 'portharcourt@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 35, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(5, 'IBADAN-AREA', 'Ibadan Area Command', 'area_command', 'Ibadan Custom House', 'Ibadan', 'Oyo', NULL, NULL, '08055555555', 'ibadan@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 30, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(6, 'ENUGU-AREA', 'Enugu Area Command', 'area_command', 'Enugu City Centre', 'Enugu', 'Enugu', NULL, NULL, '08066666666', 'enugu@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 30, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(7, 'KAD-AREA', 'Kaduna Area Command', 'area_command', 'Kaduna Custom House', 'Kaduna', 'Kaduna', NULL, NULL, '08077777777', 'kaduna@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 25, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(8, 'MAID-AREA', 'Maiduguri Area Command', 'area_command', 'Maiduguri City Centre', 'Maiduguri', 'Borno', NULL, NULL, '08088888888', 'maiduguri@customs.gov.ng', '08:00:00', '16:00:00', '12:00:00', '13:00:00', 20, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(9, 'CAL-AREA', 'Calabar Area Command', 'area_command', 'Calabar Marine Base', 'Calabar', 'Cross River', NULL, NULL, '08099999999', 'calabar@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 25, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(10, 'WAR-AREA', 'Warri Area Command', 'area_command', 'Warri Marine Base', 'Warri', 'Delta', NULL, NULL, '08100000000', 'warri@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 25, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(11, 'ABE-AREA', 'Abeokuta Area Command', 'area_command', 'Abeokuta Custom House', 'Abeokuta', 'Ogun', NULL, NULL, '08111111111', 'abeokuta@customs.gov.ng', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 20, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(12, 'LAG-INT', 'Lagos International Airport Command', 'port', 'Murtala Muhammed Airport, Lagos', 'Lagos', 'Lagos', NULL, NULL, '08122222222', 'airport@customs.gov.ng', '08:00:00', '20:00:00', '12:00:00', '13:00:00', 45, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(13, 'ZAM-AREA', 'Zamfara Area Command', 'area_command', 'Zamfara Custom House', 'Gusau', 'Zamfara', NULL, NULL, '08133333333', 'zamfara@customs.gov.ng', '08:00:00', '16:00:00', '12:00:00', '13:00:00', 15, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(14, 'NASS-BORDER', 'Nassarawa Border Command', 'border', 'Nassarawa Border Post', 'Lafia', 'Nassarawa', NULL, NULL, '08144444444', 'nassarawa@customs.gov.ng', '07:00:00', '18:00:00', '12:00:00', '13:00:00', 20, 1, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(15, 'SOK-AREA', 'Sokoto Area Command', 'area_command', 'Sokoto Custom House', 'Sokoto', 'Sokoto', NULL, NULL, '08155555555', 'sokoto@customs.gov.ng', '08:00:00', '16:00:00', '12:00:00', '13:00:00', 15, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'appointment_max_per_day', '50', 'integer', 'Maximum appointments per day per office', '2026-07-09 23:57:35', 1),
(2, 'appointment_advance_days', '30', 'integer', 'How many days in advance appointments can be booked', '2026-07-09 23:57:35', 1),
(3, 'appointment_cancel_hours', '24', 'integer', 'Hours before appointment to allow cancellation', '2026-07-09 23:57:35', 1),
(4, 'enable_document_upload', 'true', 'boolean', 'Enable document upload feature', '2026-07-09 23:57:35', 1),
(5, 'maintenance_mode', 'false', 'boolean', 'Put system in maintenance mode', '2026-07-09 23:57:35', 1),
(6, 'site_name', 'NCS Appointment System', 'string', 'Site name displayed in browser tab', '2026-07-09 23:57:35', 1),
(7, 'timezone', 'Africa/Lagos', 'string', 'Default timezone for the system', '2026-07-09 23:57:35', 1),
(8, 'enable_notifications', 'true', 'boolean', 'Enable email and SMS notifications', '2026-07-09 23:57:35', 1),
(9, 'enable_appointment_reminders', 'true', 'boolean', 'Send reminder notifications for appointments', '2026-07-09 23:57:35', 1),
(10, 'auto_approve_appointments', 'false', 'boolean', 'Auto-approve appointments without officer review', '2026-07-09 23:57:35', 1),
(11, 'reminder_days_before', '1', 'integer', NULL, '2026-07-09 23:57:35', 1),
(12, 'max_login_attempts', '5', 'integer', NULL, '2026-07-09 23:57:35', 1),
(13, 'lockout_duration', '30', 'integer', NULL, '2026-07-09 23:57:35', 1),
(14, 'session_timeout', '60', 'integer', NULL, '2026-07-09 23:57:35', 1),
(15, 'debug_mode', 'false', 'boolean', NULL, '2026-07-09 23:57:35', 1),
(16, 'max_upload_size', '10', 'integer', NULL, '2026-07-09 23:57:35', 1);

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `slot_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `monday` tinyint(1) DEFAULT 1,
  `tuesday` tinyint(1) DEFAULT 1,
  `wednesday` tinyint(1) DEFAULT 1,
  `thursday` tinyint(1) DEFAULT 1,
  `friday` tinyint(1) DEFAULT 1,
  `saturday` tinyint(1) DEFAULT 0,
  `sunday` tinyint(1) DEFAULT 0,
  `max_appointments` int(11) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `office_id`, `slot_time`, `slot_duration`, `is_active`, `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`, `max_appointments`, `created_at`, `updated_at`) VALUES
(1, 1, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(2, 1, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(3, 1, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(4, 1, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(5, 1, '11:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(6, 1, '11:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(7, 1, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(8, 1, '14:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(9, 1, '15:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(10, 2, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(11, 2, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(12, 2, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(13, 2, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(14, 2, '11:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(15, 2, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(16, 2, '14:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(17, 2, '15:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(18, 3, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(19, 3, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(20, 3, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(21, 3, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(22, 3, '11:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(23, 3, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(24, 4, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(25, 4, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(26, 4, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(27, 4, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(28, 4, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(29, 4, '14:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(30, 5, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(31, 5, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(32, 5, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(33, 5, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(34, 5, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(35, 6, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(36, 6, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(37, 6, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(38, 6, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(39, 7, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(40, 7, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(41, 7, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(42, 8, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(43, 8, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(44, 8, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(45, 9, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(46, 9, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(47, 9, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(48, 10, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(49, 10, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(50, 10, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(51, 11, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(52, 11, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(53, 12, '09:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(54, 12, '09:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(55, 12, '10:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(56, 12, '10:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(57, 12, '11:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(58, 12, '14:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(59, 12, '14:30:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37'),
(60, 12, '15:00:00', 30, 1, 1, 1, 1, 1, 1, 0, 0, 1, '2026-07-09 23:33:37', '2026-07-09 23:33:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('applicant','officer','admin') DEFAULT 'applicant',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'Nigeria',
  `postal_code` varchar(20) DEFAULT NULL,
  `nin` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uuid`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `date_of_birth`, `gender`, `address`, `city`, `state`, `country`, `postal_code`, `nin`, `is_verified`, `is_active`, `last_login`, `login_attempts`, `locked_until`, `created_at`, `updated_at`) VALUES
(1, '39618493-7be6-11f1-bb30-1c750845758c', 'System', 'Administrator', 'admin@customs.gov.ng', '08011111111', '$2y$10$JAkGRQgbJzdsA2aX5San.eRxSl8.LXADcMOZQdmJSQJpRz1MuKjZ6', 'admin', NULL, NULL, NULL, NULL, NULL, 'Nigeria', NULL, NULL, 1, 1, '2026-07-10 01:08:34', 0, NULL, '2026-07-09 23:33:36', '2026-07-10 01:08:34'),
(2, '398638c6-7be6-11f1-bb30-1c750845758c', 'James', 'Okonkwo', 'neph@gmail.com', '08022222222', '$2y$10$OJdLyweWI.MpwH99Ks3Sqe95c6XsrogcEU7C74jToricS1PeeyeWa', 'officer', NULL, NULL, NULL, NULL, NULL, 'Nigeria', NULL, NULL, 1, 1, '2026-07-10 01:03:05', 0, NULL, '2026-07-09 23:33:36', '2026-07-10 01:03:05'),
(3, '398affd1-7be6-11f1-bb30-1c750845758c', 'Fatima', 'Bello', 'fatima.bello@customs.gov.ng', '08033333333', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'officer', NULL, NULL, NULL, NULL, NULL, 'Nigeria', NULL, NULL, 1, 1, '2026-07-09 23:33:39', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:39'),
(4, '398b0c05-7be6-11f1-bb30-1c750845758c', 'Chidi', 'Eze', 'chidi.eze@customs.gov.ng', '08044444444', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'officer', NULL, NULL, NULL, NULL, NULL, 'Nigeria', NULL, NULL, 1, 1, '2026-07-09 23:33:39', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:39'),
(5, '398b0e0e-7be6-11f1-bb30-1c750845758c', 'Amara', 'Nwachukwu', 'amara.nwachukwu@customs.gov.ng', '08055555555', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'officer', NULL, NULL, NULL, NULL, NULL, 'Nigeria', NULL, NULL, 1, 1, '2026-07-09 23:33:39', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:39'),
(6, '39cb59d5-7be6-11f1-bb30-1c750845758c', 'Adeola', 'Ogunleye', 'john@gmail.com', '08012345678', '$2y$10$koVQrsdQ6F/3V.tPlsuRDehmaWT/EBnHjpD1X11uWwpmqMb9pDP8O', 'applicant', '1985-03-15', 'male', '15 Adebayo Street, Surulere', 'Lagos', 'Lagos', 'Nigeria', NULL, '12345678901', 1, 1, '2026-07-10 00:44:03', 0, NULL, '2026-07-09 23:33:36', '2026-07-10 00:44:03'),
(7, '39cbb3e0-7be6-11f1-bb30-1c750845758c', 'Ngozi', 'Eze', 'ngozi.eze@yahoo.com', '08023456789', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1990-07-22', 'female', '22 Murtala Mohammed Way, Kano', 'Kano', 'Kano', 'Nigeria', NULL, '23456789012', 1, 1, '2026-07-09 23:33:39', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:39'),
(8, '39cbb79d-7be6-11f1-bb30-1c750845758c', 'Tunde', 'Bakare', 'tunde.bakare@gmail.com', '08034567890', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1988-11-10', 'male', '8 Awolowo Road, Ikoyi', 'Lagos', 'Lagos', 'Nigeria', NULL, '34567890123', 1, 1, '2026-07-04 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(9, '39cbb956-7be6-11f1-bb30-1c750845758c', 'Chioma', 'Okonkwo', 'chioma.okonkwo@outlook.com', '08045678901', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1992-05-05', 'female', '45 Zaria Road, Kaduna', 'Kaduna', 'Kaduna', 'Nigeria', NULL, '45678901234', 1, 1, '2026-07-04 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(10, '39cbbae0-7be6-11f1-bb30-1c750845758c', 'Emeka', 'Okafor', 'emeka.okafor@gmail.com', '08056789012', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1983-09-20', 'male', '10 Herbert Macaulay Way, Port Harcourt', 'Port Harcourt', 'Rivers', 'Nigeria', NULL, '56789012345', 1, 1, '2026-07-04 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(11, '39cbbc7b-7be6-11f1-bb30-1c750845758c', 'Folake', 'Adebayo', 'folake.adebayo@yahoo.com', '08067890123', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1995-12-12', 'female', '30 Benin Road, Warri', 'Warri', 'Delta', 'Nigeria', NULL, '67890123456', 0, 1, '2026-06-29 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(12, '39cbbdf9-7be6-11f1-bb30-1c750845758c', 'Oluwaseun', 'Akinlade', 'seun.akinlade@gmail.com', '08078901234', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1987-06-18', 'male', '12 Ibadan Road, Oyo', 'Ibadan', 'Oyo', 'Nigeria', NULL, '78901234567', 1, 1, '2026-06-29 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(13, '39cbbf8e-7be6-11f1-bb30-1c750845758c', 'Ifeoma', 'Nwosu', 'ifeoma.nwosu@outlook.com', '08089012345', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1991-08-25', 'female', '25 Enugu Road, Enugu', 'Enugu', 'Enugu', 'Nigeria', NULL, '89012345678', 0, 1, '2026-06-29 23:33:40', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:40'),
(14, '39cbc111-7be6-11f1-bb30-1c750845758c', 'Ayodele', 'Ogunbiyi', 'ayodele.ogunbiyi@gmail.com', '08090123456', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1984-04-30', 'male', '18 Calabar Road, Calabar', 'Calabar', 'Cross River', 'Nigeria', NULL, '90123456789', 1, 1, '2026-06-19 23:33:41', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:41'),
(15, '39cbc28f-7be6-11f1-bb30-1c750845758c', 'Temilade', 'Adeyemi', 'temilade.adeyemi@yahoo.com', '08101234567', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1993-02-14', 'female', '5 Jos Road, Plateau', 'Jos', 'Plateau', 'Nigeria', NULL, '01234567890', 0, 1, '2026-06-19 23:33:41', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:41'),
(16, '39cbc408-7be6-11f1-bb30-1c750845758c', 'Segun', 'Alabi', 'segun.alabi@gmail.com', '08112345678', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1986-10-08', 'male', '42 Ondo Road, Akure', 'Akure', 'Ondo', 'Nigeria', NULL, '12345678909', 1, 1, '2026-06-19 23:33:41', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:41'),
(17, '39cbc5c2-7be6-11f1-bb30-1c750845758c', 'Bolanle', 'Oladipo', 'bolanle.oladipo@outlook.com', '08123456789', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1989-07-19', 'female', '28 Ilorin Road, Ilorin', 'Ilorin', 'Kwara', 'Nigeria', NULL, '23456789018', 1, 1, '2026-06-19 23:33:41', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:41'),
(18, '39cbc834-7be6-11f1-bb30-1c750845758c', 'Chukwudi', 'Okafor', 'chukwudi.okafor@gmail.com', '08134567890', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1982-05-03', 'male', '55 Owerri Road, Owerri', 'Owerri', 'Imo', 'Nigeria', NULL, '34567890127', 0, 1, '2026-06-19 23:33:41', 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:41'),
(19, '39cbc9b1-7be6-11f1-bb30-1c750845758c', 'Kemi', 'Adeleke', 'kemi.adeleke@yahoo.com', '08145678901', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1994-11-28', 'female', '20 Abeokuta Road, Abeokuta', 'Abeokuta', 'Ogun', 'Nigeria', NULL, '45678901236', 1, 1, NULL, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(20, '39cbcb2d-7be6-11f1-bb30-1c750845758c', 'Damilola', 'Akinwale', 'damilola.akinwale@gmail.com', '08156789012', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1990-09-17', 'male', '35 Makurdi Road, Makurdi', 'Makurdi', 'Benue', 'Nigeria', NULL, '56789012345', 1, 0, NULL, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(21, '39cbccb5-7be6-11f1-bb30-1c750845758c', 'Zainab', 'Abubakar', 'zainab.abubakar@outlook.com', '08167890123', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1988-06-22', 'female', '15 Bauchi Road, Bauchi', 'Bauchi', 'Bauchi', 'Nigeria', NULL, '67890123454', 0, 1, NULL, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36'),
(22, '39cbce3e-7be6-11f1-bb30-1c750845758c', 'Tayo', 'Ogunleye', 'tayo.ogunleye@gmail.com', '08178901234', '$2y$10$9fBf.mw1Ym3IKwX.w2h.KurnTt9PVmKjauAuCVMWDYLGG3kYO9S4e', 'applicant', '1985-12-01', 'male', '8 Sokoto Road, Sokoto', 'Sokoto', 'Sokoto', 'Nigeria', NULL, '78901234563', 1, 1, NULL, 0, NULL, '2026-07-09 23:33:36', '2026-07-09 23:33:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_appointments_full`
-- (See below for the actual view)
--
CREATE TABLE `vw_appointments_full` (
`id` int(11)
,`appointment_number` varchar(20)
,`user_id` int(11)
,`office_id` int(11)
,`officer_id` int(11)
,`appointment_date` date
,`appointment_time` time
,`duration_minutes` int(11)
,`purpose` varchar(255)
,`description` text
,`status` enum('pending','approved','rejected','cancelled','completed','no_show')
,`rejection_reason` text
,`has_document` tinyint(1)
,`document_path` varchar(255)
,`document_uploaded_at` datetime
,`created_at` datetime
,`updated_at` datetime
,`reviewed_at` datetime
,`reviewed_by` int(11)
,`rescheduled_from` int(11)
,`is_rescheduled` tinyint(1)
,`applicant_name` varchar(101)
,`applicant_email` varchar(100)
,`applicant_phone` varchar(20)
,`office_name` varchar(100)
,`office_code` varchar(20)
,`office_city` varchar(50)
,`office_state` varchar(50)
,`reviewed_by_name` varchar(101)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_daily_appointments`
-- (See below for the actual view)
--
CREATE TABLE `vw_daily_appointments` (
`office_id` int(11)
,`appointment_date` date
,`total_appointments` bigint(21)
,`pending_count` decimal(22,0)
,`approved_count` decimal(22,0)
,`rejected_count` decimal(22,0)
,`completed_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_appointments_full`
--
DROP TABLE IF EXISTS `vw_appointments_full`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_appointments_full`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_number` AS `appointment_number`, `a`.`user_id` AS `user_id`, `a`.`office_id` AS `office_id`, `a`.`officer_id` AS `officer_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`duration_minutes` AS `duration_minutes`, `a`.`purpose` AS `purpose`, `a`.`description` AS `description`, `a`.`status` AS `status`, `a`.`rejection_reason` AS `rejection_reason`, `a`.`has_document` AS `has_document`, `a`.`document_path` AS `document_path`, `a`.`document_uploaded_at` AS `document_uploaded_at`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `a`.`reviewed_at` AS `reviewed_at`, `a`.`reviewed_by` AS `reviewed_by`, `a`.`rescheduled_from` AS `rescheduled_from`, `a`.`is_rescheduled` AS `is_rescheduled`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `applicant_name`, `u`.`email` AS `applicant_email`, `u`.`phone` AS `applicant_phone`, `o`.`office_name` AS `office_name`, `o`.`office_code` AS `office_code`, `o`.`city` AS `office_city`, `o`.`state` AS `office_state`, concat(`r`.`first_name`,' ',`r`.`last_name`) AS `reviewed_by_name` FROM (((`appointments` `a` left join `users` `u` on(`a`.`user_id` = `u`.`id`)) left join `offices` `o` on(`a`.`office_id` = `o`.`id`)) left join `users` `r` on(`a`.`reviewed_by` = `r`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_daily_appointments`
--
DROP TABLE IF EXISTS `vw_daily_appointments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_daily_appointments`  AS SELECT `appointments`.`office_id` AS `office_id`, `appointments`.`appointment_date` AS `appointment_date`, count(0) AS `total_appointments`, sum(case when `appointments`.`status` = 'pending' then 1 else 0 end) AS `pending_count`, sum(case when `appointments`.`status` = 'approved' then 1 else 0 end) AS `approved_count`, sum(case when `appointments`.`status` = 'rejected' then 1 else 0 end) AS `rejected_count`, sum(case when `appointments`.`status` = 'completed' then 1 else 0 end) AS `completed_count` FROM `appointments` GROUP BY `appointments`.`office_id`, `appointments`.`appointment_date` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_number` (`appointment_number`),
  ADD UNIQUE KEY `unique_appointment_slot` (`office_id`,`appointment_date`,`appointment_time`,`status`),
  ADD KEY `officer_id` (`officer_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `rescheduled_from` (`rescheduled_from`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_appointment_number` (`appointment_number`),
  ADD KEY `idx_appointments_user_status` (`user_id`,`status`),
  ADD KEY `idx_appointments_date_status` (`appointment_date`,`status`);

--
-- Indexes for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity_type` (`entity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_audit_logs_user_action` (`user_id`,`action`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_holiday` (`office_id`,`holiday_date`),
  ADD KEY `idx_holiday_date` (`holiday_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `office_code` (`office_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_office_code` (`office_code`),
  ADD KEY `idx_city_state` (`city`,`state`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_office_slot` (`office_id`,`slot_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `appointment_history`
--
ALTER TABLE `appointment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_5` FOREIGN KEY (`rescheduled_from`) REFERENCES `appointments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD CONSTRAINT `appointment_history_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `time_slots_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: hoperise
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.22.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `hs_admin`
--

DROP TABLE IF EXISTS `hs_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `admin_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `admin_password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `admin_thumb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `unique_admin_email` (`admin_email`),
  KEY `idx_admin_role` (`role_id`),
  CONSTRAINT `fk_admin_role` FOREIGN KEY (`role_id`) REFERENCES `hs_role` (`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_ai_chats`
--

DROP TABLE IF EXISTS `hs_ai_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_ai_chats` (
  `chat_id` int NOT NULL AUTO_INCREMENT,
  `session_key` varchar(255) NOT NULL,
  `patient_id` int DEFAULT NULL,
  `sender` varchar(10) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_id`),
  KEY `idx_session` (`session_key`),
  KEY `idx_patient` (`patient_id`),
  CONSTRAINT `fk_chat_patient` FOREIGN KEY (`patient_id`) REFERENCES `hs_patient` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_appointment`
--

DROP TABLE IF EXISTS `hs_appointment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_appointment` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `category_id` int NOT NULL,
  `sub_category_id` int NOT NULL,
  `service_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_description` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `appointment_status` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `appointment_payment_status` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `idx_appt_patient` (`patient_id`),
  KEY `idx_appt_doctor` (`doctor_id`),
  KEY `idx_appt_category` (`category_id`),
  KEY `idx_appt_sub_category` (`sub_category_id`),
  KEY `idx_appt_service` (`service_id`),
  KEY `idx_appt_date` (`appointment_date`),
  CONSTRAINT `fk_appt_category` FOREIGN KEY (`category_id`) REFERENCES `hs_category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `hs_doctor` (`doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_patient` FOREIGN KEY (`patient_id`) REFERENCES `hs_patient` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_id`) REFERENCES `hs_service` (`service_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `hs_sub_category` (`sub_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_appointment_timeline`
--

DROP TABLE IF EXISTS `hs_appointment_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_appointment_timeline` (
  `timeline_id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int NOT NULL,
  `status_action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action_by` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`timeline_id`),
  KEY `idx_timeline_appt` (`appointment_id`),
  CONSTRAINT `fk_timeline_appt` FOREIGN KEY (`appointment_id`) REFERENCES `hs_appointment` (`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_category`
--

DROP TABLE IF EXISTS `hs_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_category` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_title` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `category_description` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `category_thumb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_contact`
--

DROP TABLE IF EXISTS `hs_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_contact` (
  `hs_id` int NOT NULL AUTO_INCREMENT,
  `hs_name` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `hs_phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `hs_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `hs_message` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_data` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_data` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`hs_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_doctor`
--

DROP TABLE IF EXISTS `hs_doctor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_doctor` (
  `doctor_id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `category_id` int NOT NULL,
  `sub_category_id` int NOT NULL,
  `service_id` int NOT NULL,
  `doctor_fname` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_mname` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_lname` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_address` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_dob` date NOT NULL,
  `doctor_doj` date NOT NULL,
  `doctor_gender` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `doctor_thumb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`doctor_id`),
  UNIQUE KEY `unique_doctor_email` (`doctor_email`),
  KEY `idx_doc_role` (`role_id`),
  KEY `idx_doc_category` (`category_id`),
  KEY `idx_doc_sub_category` (`sub_category_id`),
  KEY `idx_doc_service` (`service_id`),
  CONSTRAINT `fk_doc_category` FOREIGN KEY (`category_id`) REFERENCES `hs_category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_role` FOREIGN KEY (`role_id`) REFERENCES `hs_role` (`role_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_doc_service` FOREIGN KEY (`service_id`) REFERENCES `hs_service` (`service_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `hs_sub_category` (`sub_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_doctor_schedule`
--

DROP TABLE IF EXISTS `hs_doctor_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_doctor_schedule` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `doctor_id` int NOT NULL,
  `day_of_week` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int NOT NULL DEFAULT '30',
  `status` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `doc_day` (`doctor_id`,`day_of_week`),
  KEY `idx_schedule_doc` (`doctor_id`),
  CONSTRAINT `fk_schedule_doc` FOREIGN KEY (`doctor_id`) REFERENCES `hs_doctor` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_email_log`
--

DROP TABLE IF EXISTS `hs_email_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_email_log` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_feedback`
--

DROP TABLE IF EXISTS `hs_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_feedback` (
  `feedback_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `fd_message` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  KEY `idx_feedback_patient` (`patient_id`),
  CONSTRAINT `fk_feedback_patient` FOREIGN KEY (`patient_id`) REFERENCES `hs_patient` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_patient`
--

DROP TABLE IF EXISTS `hs_patient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_patient` (
  `patient_id` int NOT NULL AUTO_INCREMENT,
  `patient_fname` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_lname` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_mname` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_gender` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_address` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `patient_phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `patient_dob` date NOT NULL,
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `unique_patient_email` (`patient_email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_role`
--

DROP TABLE IF EXISTS `hs_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_role` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_title` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_service`
--

DROP TABLE IF EXISTS `hs_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_service` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `sub_category_id` int NOT NULL,
  `service_title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `service_description` text COLLATE utf8mb4_general_ci NOT NULL,
  `service_thumb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`),
  KEY `idx_service_category` (`category_id`),
  KEY `idx_service_sub_category` (`sub_category_id`),
  CONSTRAINT `fk_service_category` FOREIGN KEY (`category_id`) REFERENCES `hs_category` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_service_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `hs_sub_category` (`sub_category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hs_sub_category`
--

DROP TABLE IF EXISTS `hs_sub_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_sub_category` (
  `sub_category_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `sub_category_title` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_category_description` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_category_thumb` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sub_category_id`),
  KEY `idx_subcat_category` (`category_id`),
  CONSTRAINT `fk_subcat_category` FOREIGN KEY (`category_id`) REFERENCES `hs_category` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed

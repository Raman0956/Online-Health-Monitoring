-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2024 at 09:40 PM
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
-- Database: `health_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `adminID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doctorID` int(11) NOT NULL,
  `workingID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doctorID`, `workingID`) VALUES
(12, '13243567');

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `examID` int(11) NOT NULL,
  `examName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam`
--

INSERT INTO `exam` (`examID`, `examName`) VALUES
(1, 'Blood Test'),
(2, 'Urine Test'),
(3, 'Ultrasound'),
(4, 'X-ray'),
(5, 'CT Scan'),
(6, 'ECG');

-- --------------------------------------------------------

--
-- Table structure for table `exam_item`
--

CREATE TABLE `exam_item` (
  `itemID` int(11) NOT NULL,
  `examID` int(11) NOT NULL,
  `itemName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_item`
--

INSERT INTO `exam_item` (`itemID`, `examID`, `itemName`) VALUES
(1, 1, 'Routine Hematology'),
(2, 1, 'Coagulation'),
(3, 1, 'Routine Chemistry'),
(4, 1, 'Renal Function'),
(5, 1, 'Liver Function'),
(6, 1, 'Pancreas Function'),
(7, 1, 'Endocrinology'),
(8, 1, 'Tumor Markers');

-- --------------------------------------------------------

--
-- Table structure for table `monitoring`
--

CREATE TABLE `monitoring` (
  `monitoringID` int(11) NOT NULL,
  `doctorID` int(11) NOT NULL,
  `patientID` int(11) NOT NULL,
  `examID` int(11) NOT NULL,
  `itemID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monitoring`
--

INSERT INTO `monitoring` (`monitoringID`, `doctorID`, `patientID`, `examID`, `itemID`) VALUES
(2, 12, 2, 6, 5),
(3, 12, 3, 1, 4),
(5, 12, 3, 5, NULL),
(6, 12, 11, 6, NULL),
(7, 12, 11, 2, NULL),
(8, 12, 11, 3, NULL),
(9, 12, 11, 4, NULL),
(11, 12, 3, 1, 6),
(12, 12, 11, 1, 1),
(13, 12, 11, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `patientID` int(11) NOT NULL,
  `healthID` varchar(50) NOT NULL,
  `dateOfBirth` date NOT NULL,
  `isApproved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`patientID`, `healthID`, `dateOfBirth`, `isApproved`) VALUES
(2, '2123', '2024-10-08', 1),
(3, '1234', '2024-10-01', 1),
(7, '564894', '2024-10-08', 1),
(11, '2432432', '2024-10-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `prescribed_exam`
--

CREATE TABLE `prescribed_exam` (
  `prescriptionID` int(11) NOT NULL,
  `patientID` int(11) NOT NULL,
  `doctorID` int(11) NOT NULL,
  `examID` int(11) NOT NULL,
  `itemID` int(11) DEFAULT NULL,
  `prescriptionDate` date NOT NULL,
  `result` varchar(50) DEFAULT NULL,
  `isAbnormal` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescribed_exam`
--

INSERT INTO `prescribed_exam` (`prescriptionID`, `patientID`, `doctorID`, `examID`, `itemID`, `prescriptionDate`, `result`, `isAbnormal`, `status`) VALUES
(40, 3, 12, 4, NULL, '2024-10-30', '8', 1, 'Completed'),
(41, 3, 12, 5, NULL, '2024-10-30', NULL, 0, 'Pending'),
(42, 2, 12, 2, NULL, '2024-10-30', NULL, 0, 'Pending'),
(43, 2, 12, 4, NULL, '2024-10-30', NULL, 0, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffID` int(11) NOT NULL,
  `workingID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffID`, `workingID`) VALUES
(5, '12345'),
(10, 'q232141');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `userType` enum('Patient','Doctor','Staff','Administrator') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `name`, `email`, `phoneNumber`, `password`, `userType`) VALUES
(2, 'Ramandeep kaur', 'ramandeep@gmail.com', '6476872672', '$2y$10$4KB111QpwHmKwB/pT5pC8.L2Js0QlV4VU/gjlkwuO.fv4zzWT577C', 'Patient'),
(3, 'Raman', 'ramandeep09@gmail.com', '6476872672', '$2y$10$jSW8tqOjlfPLgiculcwBSuqy0gHeFRo9WSuhFVcVg3GL/mbPnfQDK', 'Patient'),
(5, 'Jack', 'jack@clinic.net', '668946846', '$2y$10$9.BszATqbvhyWa4Lw08/Lug4yGyn7ndVaziCfyoe0og9JSyX7Ssh.', 'Staff'),
(6, 'Ramandeep kaur', 'ramandeep0956@gmail.com', '6476872672', '$2y$10$gIx1CT87ln557cjDSpiCYubCyHKK0ee7MNLb4Mu55EX4Vq8QhvlyK', 'Administrator'),
(7, 'Ramandeep', 'ramap0956@gmail.com', '6476872672', '$2y$10$..PxYsoLrYY5LKPhHYS4Aega4/VIgyZzy1CyKrKraugMX2uCkmdJ.', 'Patient'),
(9, 'Naman', 'naman@clinic.net', '1234234324', '$2y$10$ojWGjzuG0y7ZsbgtXldnceQfDyzaxdnY0AJjoYFU4MDxaeZe173T.', 'Doctor'),
(10, 'Rahat', 'rahat@clinic.net', '1231342323', '$2y$10$GIhYuUuZpsh3Rsr51yw5gujLgcdR5a5lybNOYG4GiHAZPgcjruu4u', 'Staff'),
(11, 'Ram', 'Ram@clinic.net', '3434534534', '$2y$10$JOMzrqBWxdnGSZ9S/iOBPefRv/obJ7m3QNG.Rf1rCYe2p0dHMnMsa', 'Patient'),
(12, 'd', 'ramandeep2672@gmail.com', '1231243415', '$2y$10$1NFZdNf/4j2hdAINwKSIS.0lCq02tp98iOo73P721LHUSXZr5AZOi', 'Doctor');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doctorID`),
  ADD UNIQUE KEY `workingID` (`workingID`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`examID`);

--
-- Indexes for table `exam_item`
--
ALTER TABLE `exam_item`
  ADD PRIMARY KEY (`itemID`),
  ADD KEY `examID` (`examID`);

--
-- Indexes for table `monitoring`
--
ALTER TABLE `monitoring`
  ADD PRIMARY KEY (`monitoringID`),
  ADD KEY `doctorID` (`doctorID`),
  ADD KEY `patientID` (`patientID`),
  ADD KEY `examID` (`examID`),
  ADD KEY `itemID` (`itemID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`patientID`),
  ADD UNIQUE KEY `healthID` (`healthID`);

--
-- Indexes for table `prescribed_exam`
--
ALTER TABLE `prescribed_exam`
  ADD PRIMARY KEY (`prescriptionID`),
  ADD KEY `examID` (`examID`),
  ADD KEY `itemID` (`itemID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffID`),
  ADD UNIQUE KEY `workingID` (`workingID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `examID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_item`
--
ALTER TABLE `exam_item`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `monitoring`
--
ALTER TABLE `monitoring`
  MODIFY `monitoringID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `prescribed_exam`
--
ALTER TABLE `prescribed_exam`
  MODIFY `prescriptionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administrator`
--
ALTER TABLE `administrator`
  ADD CONSTRAINT `administrator_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `doctor_ibfk_1` FOREIGN KEY (`doctorID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `exam_item`
--
ALTER TABLE `exam_item`
  ADD CONSTRAINT `exam_item_ibfk_1` FOREIGN KEY (`examID`) REFERENCES `exam` (`examID`) ON DELETE CASCADE;

--
-- Constraints for table `monitoring`
--
ALTER TABLE `monitoring`
  ADD CONSTRAINT `monitoring_ibfk_1` FOREIGN KEY (`doctorID`) REFERENCES `doctor` (`doctorID`) ON DELETE CASCADE,
  ADD CONSTRAINT `monitoring_ibfk_2` FOREIGN KEY (`patientID`) REFERENCES `patient` (`patientID`) ON DELETE CASCADE,
  ADD CONSTRAINT `monitoring_ibfk_3` FOREIGN KEY (`examID`) REFERENCES `exam` (`examID`) ON DELETE CASCADE,
  ADD CONSTRAINT `monitoring_ibfk_4` FOREIGN KEY (`itemID`) REFERENCES `exam_item` (`itemID`) ON DELETE CASCADE;

--
-- Constraints for table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`patientID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `prescribed_exam`
--
ALTER TABLE `prescribed_exam`
  ADD CONSTRAINT `prescribed_exam_ibfk_1` FOREIGN KEY (`examID`) REFERENCES `exam` (`examID`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescribed_exam_ibfk_2` FOREIGN KEY (`itemID`) REFERENCES `exam_item` (`itemID`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`staffID`) REFERENCES `user` (`userID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 26, 2024 at 04:00 AM
-- Server version: 5.7.39
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sushi`
--

-- --------------------------------------------------------

--
-- Table structure for table `Customer`
--

CREATE TABLE `Customer` (
  `MemberID` varchar(10) NOT NULL,
  `PhoneNumber` varchar(255) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `DOB` varchar(255) DEFAULT NULL,
  `Points` int(11) DEFAULT '0',
  `TableNO` int(11) DEFAULT NULL,
  `EncryptionKey` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Customer`
--

INSERT INTO `Customer` (`MemberID`, `PhoneNumber`, `FirstName`, `LastName`, `Email`, `DOB`, `Points`, `TableNO`, `EncryptionKey`) VALUES
('4122063608', 'IQMAvmGR0jgcOiGiaxhsilErR1ZpUnVYVW5yR2lWdzNLZm1zdmc9PQ==', 'smith', 'sam', 'DsFWxu37OmSOrzmXlt9A10VqalVQbFlSbDJIbURJNXQ2bFBydGc9PQ==', '1qzF3kk1wIeT8+BWia+YkGJmYkhEd0lXNk1IWFJmNnpDVFpDN0E9PQ==', 62, NULL, 'rCMaFE4lD/ztVKjJUaCKzTOBJSlKDfSrUZbsYtwhuUk='),
('5697807118', 'jufpUF08lwWSttC6FKayUjViaVZ1OHNxUWd5a2JWQVdSOGsvMXc9PQ==', 'Tanat', 'addf', 'BYELswHZOQR39o7xr/fTKHRzN3JXem01anZ1aCtIdmRQZ0dDVkE9PQ==', 'cEjRgNFkCYyWfDdMxJE8kmlZK0h2aTBiN0FJNUVSbnpja0Z2U0E9PQ==', 0, NULL, '0d60SdOSEqmjaGWYFdoGxqvFQtnMlUDvGbTYJ3mIjbc='),
('6632994101', '3SBIV5fbgYbB6rNpCFnOHHlBTTFDaTBqejlNcGhhRTVDdmtBTUE9PQ==', 'suisui', 'jurissui', 'T6DXu+U7i2UfL5kSQxvU3Vk1dGpLakNGZCtXa1YrTmg2SXBuKzJrellvNUtpYWMwZkpocmxyeDdocWc9', '4nlzQXtc8ZRjFOhhlPIBh0hhMjFEN1NrZjZOdGhWbnF1OWhJeVE9PQ==', 372, NULL, '8acyLY6N9d3K03kRXRdbltGRr3svwarJPdMToyTkUMc='),
('9151792930', 'LNcxqtnxEVL9k9osIllKGFd2TDhzQ0hqUlpSRHMyOU1Hd2Y4b1E9PQ==', 'Punna', 'Wit', 'LxwVAjFGdGJ/dztp95dYNnJKRUUrSjJUbi9XNXhPZThiUFFZRkE9PQ==', 'QxvVkLlZdQNUDxwgre/+40dyZGNuUlZIOFpycUExNFM4M2FQSEE9PQ==', 66, NULL, 'IW1DG5GhZV5PvxQsqR++tnsP3d0AXdNzj5PCZyVBnUA=');

-- --------------------------------------------------------

--
-- Table structure for table `MenuItem`
--

CREATE TABLE `MenuItem` (
  `MenuItemName` varchar(50) NOT NULL,
  `PlateColor` varchar(20) NOT NULL,
  `MenuCategory` varchar(50) NOT NULL,
  `Price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `MenuItem`
--

INSERT INTO `MenuItem` (`MenuItemName`, `PlateColor`, `MenuCategory`, `Price`) VALUES
('Akagai Nigiri', 'Gold', 'Nigiri', 80),
('Asahi (600ml.)', 'Other', 'Desserts/Beverages', 100),
('Crema catalana', 'Other', 'Desserts/Beverages', 80),
('Ebi Nigiri', 'Gold', 'Nigiri', 80),
('Ebi Tempura Nigiri', 'Silver', 'Nigiri', 60),
('Engawa Nigiri', 'Silver', 'Nigiri', 60),
('French fry', 'Other', 'Appetizers', 600),
('Hamachi Nigiri', 'Silver', 'Nigiri', 60),
('Hotate Nigiri', 'Silver', 'Nigiri', 60),
('Ika Nigiri', 'Red', 'Nigiri', 40),
('Ikura Gunkan', 'Silver', 'Gunkan', 60),
('Karaage', 'Other', 'Appetizers', 120),
('Madai Nigiri', 'Silver', 'Nigiri', 60),
('Maguro Negi Gunkan', 'Red', 'Gunkan', 40),
('Maguro Nigiri', 'Red', 'Nigiri', 40),
('Maguro Tori Shoyu Ramen', 'Other', 'Noodles/Soup', 120),
('Miso Clam Seaweed Soup', 'Other', 'Noodles/Soup', 70),
('Niigata Miso Ramen', 'Other', 'Noodles/Soup', 160),
('Otoro Nigiri', 'Black', 'Nigiri', 120),
('Salmon Ikura Gunkan', 'Black', 'Gunkan', 120),
('Salmon Nigiri', 'Red', 'Nigiri', 40),
('Salmon salad', 'Other', 'Appetizers', 100),
('Steamed egg', 'Other', 'Appetizers', 70),
('Taiwan milk tea', 'Other', 'Desserts/Beverages', 60),
('Tarako Gunkan', 'Red', 'Gunkan', 40),
('Udon Mentaiko Mochi', 'Other', 'Noodles/Soup', 120),
('Warabi mochi', 'Other', 'Desserts/Beverages', 40);

-- --------------------------------------------------------

--
-- Table structure for table `OrderItem`
--

CREATE TABLE `OrderItem` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `MenuItemName` varchar(50) NOT NULL,
  `ItemAmount` int(11) NOT NULL,
  `Status` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `OrderItem`
--

INSERT INTO `OrderItem` (`OrderItemID`, `OrderID`, `MenuItemName`, `ItemAmount`, `Status`) VALUES
(177, 83, 'Ebi Nigiri', 1, 1),
(178, 83, 'Ika Nigiri', 1, 1),
(179, 83, 'Madai Nigiri', 1, 1),
(180, 83, 'Otoro Nigiri', 1, 1),
(195, 90, 'Asahi (600ml.)', 1, 1),
(196, 90, 'Crema catalana', 1, 1),
(197, 90, 'Taiwan milk tea', 1, 1),
(198, 90, 'Warabi mochi', 1, 1),
(199, 92, 'Madai Nigiri', 1, 1),
(200, 92, 'Ika Nigiri', 1, 1),
(201, 92, 'Ebi Nigiri', 1, 1),
(202, 92, 'Salmon Ikura Gunkan', 1, 1),
(203, 92, 'Tarako Gunkan', 1, 1),
(204, 92, 'Udon Mentaiko Mochi', 1, 1),
(205, 92, 'Niigata Miso Ramen', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `Order_`
--

CREATE TABLE `Order_` (
  `OrderID` int(11) NOT NULL,
  `MemberID` varchar(10) NOT NULL,
  `TableNO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Order_`
--

INSERT INTO `Order_` (`OrderID`, `MemberID`, `TableNO`) VALUES
(83, '6632994101', 2),
(86, '9151792930', 1),
(87, '9151792930', 4),
(89, '9151792930', 7),
(90, '9151792930', 5),
(91, '9151792930', 7),
(92, '4122063608', 3),
(93, '4122063608', 3),
(94, '4122063608', 6),
(95, '4122063608', 7);

-- --------------------------------------------------------

--
-- Table structure for table `Payment`
--

CREATE TABLE `Payment` (
  `PaymentNo.` int(11) NOT NULL,
  `MemberID` varchar(10) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `PhoneNumber` varchar(255) DEFAULT NULL,
  `TableNO` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `PaymentTotalPrice` float NOT NULL,
  `DateTime` datetime NOT NULL,
  `PointsEarned` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Payment`
--

INSERT INTO `Payment` (`PaymentNo.`, `MemberID`, `FirstName`, `PhoneNumber`, `TableNO`, `OrderID`, `PaymentTotalPrice`, `DateTime`, `PointsEarned`) VALUES
(1, '6632994101', 'suisui', '3SBIV5fbgYbB6rNpCFnOHHlBTTFDaTBqejlNcGhhRTVDdmtBTUE9PQ==', 1, 80, 300, '2024-11-25 23:29:41', 30),
(2, '6632994101', 'suisui', '3SBIV5fbgYbB6rNpCFnOHHlBTTFDaTBqejlNcGhhRTVDdmtBTUE9PQ==', 7, 81, 300, '2024-11-25 23:30:12', 30),
(3, '9151792930', 'Punna', 'LNcxqtnxEVL9k9osIllKGFd2TDhzQ0hqUlpSRHMyOU1Hd2Y4b1E9PQ==', 5, 90, 280, '2024-11-26 10:16:22', 28),
(4, '4122063608', 'smith', 'IQMAvmGR0jgcOiGiaxhsilErR1ZpUnVYVW5yR2lWdzNLZm1zdmc9PQ==', 3, 92, 620, '2024-11-26 10:27:18', 62);

-- --------------------------------------------------------

--
-- Table structure for table `Seat`
--

CREATE TABLE `Seat` (
  `TableNO` int(11) NOT NULL,
  `TableType` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Seat`
--

INSERT INTO `Seat` (`TableNO`, `TableType`) VALUES
(1, 'Table'),
(2, 'Table'),
(3, 'Table'),
(4, 'Table'),
(5, 'Bar'),
(6, 'Bar'),
(7, 'Bar'),
(8, 'Bar'),
(9, 'Bar'),
(10, 'Bar');

-- --------------------------------------------------------

--
-- Table structure for table `Tablet`
--

CREATE TABLE `Tablet` (
  `TabletNo.` int(11) NOT NULL,
  `TableNO` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `Tablet`
--

INSERT INTO `Tablet` (`TabletNo.`, `TableNO`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Customer`
--
ALTER TABLE `Customer`
  ADD PRIMARY KEY (`MemberID`,`PhoneNumber`),
  ADD UNIQUE KEY `MemberID` (`MemberID`),
  ADD UNIQUE KEY `PhoneNumber` (`PhoneNumber`),
  ADD UNIQUE KEY `FirstName` (`FirstName`),
  ADD KEY `FK_Customer_TableNO` (`TableNO`);

--
-- Indexes for table `MenuItem`
--
ALTER TABLE `MenuItem`
  ADD PRIMARY KEY (`MenuItemName`),
  ADD UNIQUE KEY `MenuItemName` (`MenuItemName`);

--
-- Indexes for table `OrderItem`
--
ALTER TABLE `OrderItem`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `FK_MenuItemName_MenuItem` (`MenuItemName`),
  ADD KEY `FK_OrderID_ORDER_` (`OrderID`);

--
-- Indexes for table `Order_`
--
ALTER TABLE `Order_`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `FK_MemberID_Order` (`MemberID`),
  ADD KEY `FK_Order_TableNO` (`TableNO`);

--
-- Indexes for table `Payment`
--
ALTER TABLE `Payment`
  ADD PRIMARY KEY (`PaymentNo.`),
  ADD KEY `FK_MemberID_PhoneNumber_Customer` (`MemberID`,`PhoneNumber`),
  ADD KEY `FK_OrderID_Payment` (`OrderID`),
  ADD KEY `FK_TableNO` (`TableNO`);

--
-- Indexes for table `Seat`
--
ALTER TABLE `Seat`
  ADD PRIMARY KEY (`TableNO`),
  ADD UNIQUE KEY `TableNo.` (`TableNO`);

--
-- Indexes for table `Tablet`
--
ALTER TABLE `Tablet`
  ADD PRIMARY KEY (`TabletNo.`),
  ADD KEY `TableNo.` (`TableNO`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `OrderItem`
--
ALTER TABLE `OrderItem`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `Order_`
--
ALTER TABLE `Order_`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Customer`
--
ALTER TABLE `Customer`
  ADD CONSTRAINT `FK_Customer_TableNO` FOREIGN KEY (`TableNO`) REFERENCES `Seat` (`TableNO`) ON DELETE SET NULL;

--
-- Constraints for table `OrderItem`
--
ALTER TABLE `OrderItem`
  ADD CONSTRAINT `FK_MenuItemName_MenuItem` FOREIGN KEY (`MenuItemName`) REFERENCES `MenuItem` (`MenuItemName`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_OrderID_ORDER_` FOREIGN KEY (`OrderID`) REFERENCES `Order_` (`OrderID`) ON UPDATE CASCADE;

--
-- Constraints for table `Order_`
--
ALTER TABLE `Order_`
  ADD CONSTRAINT `FK_MemberID_Order` FOREIGN KEY (`MemberID`) REFERENCES `Customer` (`MemberID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Order_TableNO` FOREIGN KEY (`TableNO`) REFERENCES `Seat` (`TableNO`);

--
-- Constraints for table `Payment`
--
ALTER TABLE `Payment`
  ADD CONSTRAINT `FK_MemberID_PhoneNumber_Customer` FOREIGN KEY (`MemberID`,`PhoneNumber`) REFERENCES `Customer` (`MemberID`, `PhoneNumber`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_TableNO` FOREIGN KEY (`TableNO`) REFERENCES `Seat` (`TableNO`);

--
-- Constraints for table `Tablet`
--
ALTER TABLE `Tablet`
  ADD CONSTRAINT `FK_Tablet_TableNO` FOREIGN KEY (`TableNO`) REFERENCES `Seat` (`TableNO`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

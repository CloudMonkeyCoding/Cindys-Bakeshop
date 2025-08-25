-- Table structure for table `notification`
CREATE TABLE `notification` (
  `Notification_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Type` enum('order','low_stock') NOT NULL,
  `Reference_ID` int(11) DEFAULT NULL,
  `Message` varchar(255) NOT NULL,
  `Is_Read` tinyint(1) DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`Notification_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

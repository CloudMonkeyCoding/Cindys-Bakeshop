--
-- Shift schedule tracking for store staff
--

CREATE TABLE IF NOT EXISTS `shift_schedule` (
  `Shift_ID` INT NOT NULL AUTO_INCREMENT,
  `User_ID` INT NOT NULL,
  `Shift_Date` DATE NOT NULL,
  `Scheduled_Start` TIME NOT NULL,
  `Scheduled_End` TIME NOT NULL,
  `Actual_Start` DATETIME DEFAULT NULL,
  `Actual_End` DATETIME DEFAULT NULL,
  `Status` ENUM('scheduled', 'in_progress', 'completed', 'missed') DEFAULT 'scheduled',
  `Notes` VARCHAR(255) DEFAULT NULL,
  `Created_At` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated_At` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Shift_ID`),
  KEY `idx_shift_user_date` (`User_ID`, `Shift_Date`),
  CONSTRAINT `fk_shift_schedule_user` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

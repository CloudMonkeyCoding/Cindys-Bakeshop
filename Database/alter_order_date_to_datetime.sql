-- Upgrades existing installations so order timestamps preserve time of day
ALTER TABLE `order`
    MODIFY COLUMN `Order_Date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

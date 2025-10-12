-- Adds the inventory_stock_log table for tracking stock adjustments.
CREATE TABLE IF NOT EXISTS `inventory_stock_log` (
  `Log_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Product_ID` int(11) NOT NULL,
  `Change_Amount` int(11) DEFAULT NULL,
  `Previous_Quantity` int(11) DEFAULT NULL,
  `New_Quantity` int(11) DEFAULT NULL,
  `Change_Source` varchar(50) NOT NULL,
  `Reference_Type` varchar(50) DEFAULT NULL,
  `Reference_ID` int(11) DEFAULT NULL,
  `Note` varchar(255) DEFAULT NULL,
  `Created_At` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Log_ID`),
  KEY `idx_inventory_stock_log_product` (`Product_ID`),
  KEY `idx_inventory_stock_log_created_at` (`Created_At`),
  CONSTRAINT `inventory_stock_log_product_fk`
    FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
    ON DELETE CASCADE
);

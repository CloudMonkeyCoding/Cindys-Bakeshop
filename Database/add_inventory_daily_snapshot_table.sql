-- Creates a table that stores end-of-day inventory quantities for each product.
CREATE TABLE IF NOT EXISTS `inventory_daily_snapshot` (
  `Snapshot_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Snapshot_Date` date NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Created_At` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated_At` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Snapshot_ID`),
  UNIQUE KEY `uq_inventory_snapshot_date_product` (`Snapshot_Date`, `Product_ID`),
  KEY `idx_inventory_snapshot_product` (`Product_ID`),
  CONSTRAINT `inventory_daily_snapshot_product_fk`
    FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`)
    ON DELETE CASCADE
);

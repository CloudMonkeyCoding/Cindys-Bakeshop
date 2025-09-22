-- Adds Source and Fulfillment_Type tracking to the order table for walk-in support
ALTER TABLE `order`
    ADD COLUMN IF NOT EXISTS `Source` ENUM('online','walk-in') NULL DEFAULT 'online' AFTER `Order_Date`,
    ADD COLUMN IF NOT EXISTS `Fulfillment_Type` ENUM('Delivery','Pick up') NULL DEFAULT 'Delivery' AFTER `Source`,
    ADD INDEX IF NOT EXISTS `idx_order_source` (`Source`),
    ADD INDEX IF NOT EXISTS `idx_order_fulfillment` (`Fulfillment_Type`);
